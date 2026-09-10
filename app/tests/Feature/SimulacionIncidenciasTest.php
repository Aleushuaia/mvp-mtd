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
