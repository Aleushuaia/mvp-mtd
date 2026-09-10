<?php

namespace Tests\Feature;

use App\Models\Incidencia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperadorIncidenciasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $operador = Usuario::where('id_rol', Usuario::ROL_OPERADOR)->firstOrFail();
        $this->withSession(['usuario' => [
            'id' => $operador->id,
            'id_rol' => Usuario::ROL_OPERADOR,
            'rol' => 'Operador',
        ]]);
    }

    public function test_el_operador_puede_cancelar_una_incidencia_en_proceso_con_motivo_obligatorio(): void
    {
        $incidencia = $this->crearIncidenciaEnProceso();

        $this->get(route('operador.incidencias.index', ['estado' => [Incidencia::ESTADO_EN_PROCESO]]))
            ->assertOk()
            ->assertSee('modalCancelarIncidencia', false)
            ->assertSee(route('operador.incidencias.cancelar', $incidencia), false);

        $this->post(route('operador.incidencias.cancelar', $incidencia), [])
            ->assertSessionHasErrors('comentario_cancelacion');
        $this->assertSame(Incidencia::ESTADO_EN_PROCESO, $incidencia->fresh()->id_estado_incidencia);

        $this->post(route('operador.incidencias.cancelar', $incidencia), [
            'comentario_cancelacion' => 'El reclamo fue solucionado por otra vía.',
        ])->assertRedirect(route('operador.incidencias.index'));

        $this->assertSame(Incidencia::ESTADO_CANCELADA, $incidencia->fresh()->id_estado_incidencia);
        $this->assertDatabaseHas('historial_estado_incidencia', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_estado_anterior' => Incidencia::ESTADO_EN_PROCESO,
            'id_estado_nuevo' => Incidencia::ESTADO_CANCELADA,
            'comentario' => 'Cancelada por el operador. Motivo: El reclamo fue solucionado por otra vía.',
        ]);
    }

    public function test_el_operador_puede_resolver_una_incidencia_en_proceso_con_mensaje_opcional(): void
    {
        $incidencia = $this->crearIncidenciaEnProceso();

        $this->get(route('operador.incidencias.index', ['estado' => [Incidencia::ESTADO_EN_PROCESO]]))
            ->assertOk()
            ->assertSee('modalResolverIncidencia', false)
            ->assertSee(route('operador.incidencias.resolver', $incidencia), false);

        $this->post(route('operador.incidencias.resolver', $incidencia), [
            'comentario_resolucion' => 'Se normalizó el servicio en el sector informado.',
        ])->assertRedirect(route('operador.incidencias.index'));

        $this->assertSame(Incidencia::ESTADO_RESUELTA, $incidencia->fresh()->id_estado_incidencia);
        $this->assertDatabaseHas('historial_estado_incidencia', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_estado_anterior' => Incidencia::ESTADO_EN_PROCESO,
            'id_estado_nuevo' => Incidencia::ESTADO_RESUELTA,
            'comentario' => 'Resuelta por el operador. Mensaje: Se normalizó el servicio en el sector informado.',
        ]);
    }

    private function crearIncidenciaEnProceso(): Incidencia
    {
        $socio = Usuario::where('id_rol', Usuario::ROL_SOCIO)->firstOrFail();

        return Incidencia::create([
            'id_usuario' => $socio->id,
            'id_usuario_alta' => $socio->id,
            'id_tipo_incidencia' => 1,
            'id_ubicacion' => 1,
            'id_estado_incidencia' => Incidencia::ESTADO_EN_PROCESO,
            'id_criticidad' => Incidencia::CRITICIDAD_NORMAL,
            'descripcion' => 'Reclamo para cancelar desde el operador.',
            'fecha_hora_evento' => now()->subHour(),
            'fecha_hora_alta' => now(),
        ]);
    }
}
