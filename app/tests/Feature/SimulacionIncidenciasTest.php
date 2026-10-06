<?php

namespace Tests\Feature;

use App\Models\Incidencia;
use App\Models\Usuario;
use App\Simulacion\SimuladorAsignaciones;
use App\Simulacion\SimuladorResoluciones;
use App\Simulacion\SimuladorRevisionPendientes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SimulacionIncidenciasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->seed();
        $socio = Usuario::where('id_rol', Usuario::ROL_SOCIO)->firstOrFail();
        $this->withSession(['usuario' => ['id' => $socio->id, 'id_rol' => Usuario::ROL_SOCIO, 'rol' => 'Socio']]);
    }

    public function test_completa_el_circuito_automatico_en_tres_etapas_de_diez_segundos(): void
    {
        $incidencia = $this->crearPendiente();

        $this->travel(9)->seconds();
        $this->assertSame(0, app(SimuladorRevisionPendientes::class)->ejecutar());
        $this->travel(1)->seconds();
        $this->assertSame(1, $this->revision(true)->ejecutar());
        $this->assertSame(Incidencia::ESTADO_CONFIRMADA, $incidencia->fresh()->id_estado_incidencia);

        $this->travel(9)->seconds();
        $this->assertSame(0, app(SimuladorAsignaciones::class)->ejecutar());
        $this->travel(1)->seconds();
        $this->assertSame(1, app(SimuladorAsignaciones::class)->ejecutar());
        $this->assertSame(Incidencia::ESTADO_EN_PROCESO, $incidencia->fresh()->id_estado_incidencia);
        $this->assertSame(1, $incidencia->fresh()->responsables()->count());

        $this->travel(9)->seconds();
        $this->assertSame(0, app(SimuladorResoluciones::class)->ejecutar());
        $this->travel(1)->seconds();
        $this->assertSame(1, $this->resolucion(true)->ejecutar());
        $this->assertSame(Incidencia::ESTADO_RESUELTA, $incidencia->fresh()->id_estado_incidencia);
        $this->assertDatabaseCount('historial_estado_incidencia', 5);
    }

    public function test_la_revision_puede_cancelar_una_pendiente(): void
    {
        $incidencia = $this->crearPendiente();

        $this->travel(10)->seconds();
        $this->assertSame(1, $this->revision(false)->ejecutar());

        $this->assertSame(Incidencia::ESTADO_CANCELADA, $incidencia->fresh()->id_estado_incidencia);
        $this->assertFalse($incidencia->fresh()->tieneResponsable());
        $this->assertDatabaseCount('historial_estado_incidencia', 3);
    }

    public function test_la_revision_nunca_cancela_si_las_canceladas_superarian_el_10_por_ciento(): void
    {
        // 9 en proceso + 1 cancelada = 10 en total: una cancelación más daría 2/10 = 20 %.
        $this->crearIncidenciasEnEstado(Incidencia::ESTADO_EN_PROCESO, 9);
        $this->crearIncidenciasEnEstado(Incidencia::ESTADO_CANCELADA, 1);

        $simulador = $this->sorteoDeRevision();

        for ($i = 0; $i < 300; $i++) {
            $this->assertTrue($simulador->sortear(), 'Con el tope superado la revisión sólo puede confirmar.');
        }
    }

    public function test_la_revision_cancela_pocas_veces_cuando_hay_margen_bajo_el_tope(): void
    {
        // 20 incidencias y ninguna cancelada: cancelar una daría 1/20 = 5 % (<= 10 %).
        $this->crearIncidenciasEnEstado(Incidencia::ESTADO_EN_PROCESO, 20);

        $simulador = $this->sorteoDeRevision();
        $cancelaciones = 0;

        for ($i = 0; $i < 1000; $i++) {
            if (! $simulador->sortear()) {
                $cancelaciones++;
            }
        }

        // Esperado ~10 % (100 de 1000): se admite cualquier valor lejos del 30 % anterior.
        $this->assertGreaterThan(0, $cancelaciones);
        $this->assertLessThan(200, $cancelaciones);
    }

    public function test_una_incidencia_no_resuelta_permanece_en_proceso(): void
    {
        $incidencia = $this->crearEnProceso();

        $this->travel(10)->seconds();
        $this->assertSame(0, $this->resolucion(false)->ejecutar());
        $this->assertSame(Incidencia::ESTADO_EN_PROCESO, $incidencia->fresh()->id_estado_incidencia);
        $this->assertNotNull($incidencia->fresh()->resolucion_simulada_evaluada_at);

        $this->travel(1)->days();
        $this->assertSame(0, app(SimuladorResoluciones::class)->ejecutar());
        $this->assertDatabaseCount('historial_estado_incidencia', 4);
    }

    private function crearPendiente(): Incidencia
    {
        $this->postJson(route('incidencias.store'), [
            'id_tipo_incidencia' => 1,
            'id_ubicacion' => 1,
            'descripcion' => 'Incidencia para prueba',
            'fecha_hora_evento' => now()->subHour()->format('Y-m-d\\TH:i'),
        ])->assertOk();
        $incidencia = Incidencia::latest('id_incidencia')->firstOrFail();
        $this->post(route('incidencias.confirmar', $incidencia))->assertRedirect();

        return $incidencia->fresh();
    }

    private function crearEnProceso(): Incidencia
    {
        $incidencia = $this->crearPendiente();
        $this->travel(10)->seconds();
        $this->assertSame(1, $this->revision(true)->ejecutar());
        $this->travel(10)->seconds();
        $this->assertSame(1, app(SimuladorAsignaciones::class)->ejecutar());

        return $incidencia->fresh();
    }

    private function crearIncidenciasEnEstado(int $estado, int $cantidad): void
    {
        $socio = Usuario::where('id_rol', Usuario::ROL_SOCIO)->firstOrFail();

        for ($i = 0; $i < $cantidad; $i++) {
            Incidencia::create([
                'id_usuario' => $socio->id,
                'id_usuario_alta' => $socio->id,
                'id_tipo_incidencia' => 1,
                'id_ubicacion' => 1,
                'id_estado_incidencia' => $estado,
                'id_criticidad' => Incidencia::CRITICIDAD_NORMAL,
                'descripcion' => 'Incidencia de prueba '.$i,
                'fecha_hora_evento' => now()->subHour(),
                'fecha_hora_alta' => now(),
            ]);
        }
    }

    /** Expone el sorteo real (protegido) de la revisión, sin mockearlo. */
    private function sorteoDeRevision(): SimuladorRevisionPendientes
    {
        return new class extends SimuladorRevisionPendientes
        {
            public function sortear(): bool
            {
                return $this->debeConfirmar();
            }
        };
    }

    private function revision(bool $confirmar): SimuladorRevisionPendientes
    {
        $simulador = Mockery::mock(SimuladorRevisionPendientes::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $simulador->shouldReceive('debeConfirmar')->once()->andReturn($confirmar);

        return $simulador;
    }

    private function resolucion(bool $resolver): SimuladorResoluciones
    {
        $simulador = Mockery::mock(SimuladorResoluciones::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $simulador->shouldReceive('debeResolver')->once()->andReturn($resolver);

        return $simulador;
    }
}
