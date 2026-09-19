<?php

namespace Tests\Feature;

use App\Jobs\EvaluarAsignacionIncidencia;
use App\Jobs\EvaluarResolucionIncidencia;
use App\Jobs\EvaluarRevisionIncidencia;
use App\Models\Incidencia;
use App\Models\Usuario;
use App\Simulacion\SimuladorAsignaciones;
use App\Simulacion\SimuladorRevisionPendientes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class SimulacionJobsTest extends TestCase
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

    public function test_confirmar_despacha_el_job_de_revision(): void
    {
        Bus::fake();

        $incidencia = $this->crearPendiente();

        Bus::assertDispatched(
            EvaluarRevisionIncidencia::class,
            fn (EvaluarRevisionIncidencia $job) => $job->incidencia->is($incidencia)
        );
    }

    public function test_la_revision_confirmada_encadena_el_job_de_asignacion(): void
    {
        Bus::fake();
        $incidencia = $this->crearPendiente();
        $this->travel(10)->seconds();

        $simulador = Mockery::mock(SimuladorRevisionPendientes::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $simulador->shouldReceive('debeConfirmar')->once()->andReturn(true);

        (new EvaluarRevisionIncidencia($incidencia))->handle($simulador);

        $this->assertSame(Incidencia::ESTADO_CONFIRMADA, $incidencia->fresh()->id_estado_incidencia);
        Bus::assertDispatched(
            EvaluarAsignacionIncidencia::class,
            fn (EvaluarAsignacionIncidencia $job) => $job->incidencia->is($incidencia)
        );
    }

    public function test_la_revision_cancelada_no_encadena_ningun_job(): void
    {
        Bus::fake();
        $incidencia = $this->crearPendiente();
        $this->travel(10)->seconds();

        $simulador = Mockery::mock(SimuladorRevisionPendientes::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $simulador->shouldReceive('debeConfirmar')->once()->andReturn(false);

        (new EvaluarRevisionIncidencia($incidencia))->handle($simulador);

        $this->assertSame(Incidencia::ESTADO_CANCELADA, $incidencia->fresh()->id_estado_incidencia);
        Bus::assertNotDispatched(EvaluarAsignacionIncidencia::class);
    }

    public function test_la_asignacion_exitosa_encadena_el_job_de_resolucion(): void
    {
        Bus::fake();
        $incidencia = $this->crearPendiente();
        $this->travel(10)->seconds();

        $revision = Mockery::mock(SimuladorRevisionPendientes::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $revision->shouldReceive('debeConfirmar')->once()->andReturn(true);
        (new EvaluarRevisionIncidencia($incidencia))->handle($revision);

        $this->travel(10)->seconds();
        (new EvaluarAsignacionIncidencia($incidencia))->handle(new SimuladorAsignaciones);

        $this->assertSame(Incidencia::ESTADO_EN_PROCESO, $incidencia->fresh()->id_estado_incidencia);
        Bus::assertDispatched(
            EvaluarResolucionIncidencia::class,
            fn (EvaluarResolucionIncidencia $job) => $job->incidencia->is($incidencia)
        );
    }

    private function crearPendiente(): Incidencia
    {
        $this->postJson(route('incidencias.store'), [
            'id_tipo_incidencia' => 1,
            'id_ubicacion' => 1,
            'descripcion' => 'Incidencia para prueba de jobs',
            'fecha_hora_evento' => now()->subHour()->format('Y-m-d\\TH:i'),
        ])->assertOk();
        $incidencia = Incidencia::latest('id_incidencia')->firstOrFail();
        $this->post(route('incidencias.confirmar', $incidencia))->assertRedirect();

        return $incidencia->fresh();
    }
}
