<?php

namespace Tests\Feature;

use App\Models\Incidencia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidenciaEdicionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $socio = Usuario::where('id_rol', Usuario::ROL_SOCIO)->firstOrFail();
        $this->withSession(['usuario' => ['id' => $socio->id, 'id_rol' => Usuario::ROL_SOCIO, 'rol' => 'Socio']]);
    }

    public function test_guardar_crea_un_borrador_y_confirmarlo_lo_pasa_a_pendiente(): void
    {
        $incidencia = $this->crearBorrador();

        $this->assertTrue($incidencia->estaBorrador());
        $this->assertDatabaseCount('historial_estado_incidencia', 1);
        $this->get(route('incidencias.confirmar-alta', $incidencia))
            ->assertOk()
            ->assertSee('Volver a editar')
            ->assertSee('Confirmar envío');

        $this->patchJson(route('incidencias.update', $incidencia), [
            ...$this->datos(),
            'descripcion' => 'Descripción corregida',
        ])->assertOk();

        $this->assertTrue($incidencia->fresh()->estaBorrador());
        $this->post(route('incidencias.confirmar', $incidencia))
            ->assertRedirect(route('incidencias.index'));

        $this->assertSame(Incidencia::ESTADO_PENDIENTE, $incidencia->fresh()->id_estado_incidencia);
        $this->assertDatabaseCount('historial_estado_incidencia', 2);
    }

    public function test_no_permite_editar_un_borrador_de_otro_socio_ni_despues_de_enviarlo(): void
    {
        $incidencia = $this->crearBorrador();
        $otro = Usuario::where('id_rol', Usuario::ROL_SOCIO)->where('id', '!=', $incidencia->id_usuario)->firstOrFail();
        $this->withSession(['usuario' => ['id' => $otro->id, 'id_rol' => Usuario::ROL_SOCIO, 'rol' => 'Socio']]);

        $this->get(route('incidencias.edit', $incidencia))->assertForbidden();
        $this->patchJson(route('incidencias.update', $incidencia), $this->datos())->assertForbidden();

        $this->withSession(['usuario' => ['id' => $incidencia->id_usuario, 'id_rol' => Usuario::ROL_SOCIO, 'rol' => 'Socio']]);
        $this->post(route('incidencias.confirmar', $incidencia))->assertRedirect();
        $this->get(route('incidencias.edit', $incidencia))->assertRedirect(route('incidencias.index'));
        $this->patchJson(route('incidencias.update', $incidencia), $this->datos())->assertUnprocessable();
    }

    public function test_mis_incidencias_permite_confirmar_borradores_sin_cancelacion_manual(): void
    {
        $incidencia = $this->crearBorrador();

        $this->get(route('incidencias.index', ['estado' => [Incidencia::ESTADO_BORRADOR]]))
            ->assertOk()
            ->assertSee('href="'.route('incidencias.confirmar-alta', $incidencia).'"', false)
            ->assertDontSee('Cambiar a Cancelada')
            ->assertDontSee('data-accion=', false);

        $this->get(route('incidencias.show', $incidencia))
            ->assertOk()
            ->assertSee('Confirmar envío')
            ->assertDontSee('Cambiar a Cancelada');
    }

    public function test_el_socio_puede_cancelar_una_incidencia_en_proceso_con_un_motivo_obligatorio(): void
    {
        $incidencia = $this->crearBorrador();
        $incidencia->update(['id_estado_incidencia' => Incidencia::ESTADO_EN_PROCESO]);

        $this->post(route('incidencias.cancelar', $incidencia), [])
            ->assertSessionHasErrors('comentario_cancelacion');
        $this->assertSame(Incidencia::ESTADO_EN_PROCESO, $incidencia->fresh()->id_estado_incidencia);

        $this->post(route('incidencias.cancelar', $incidencia), [
            'comentario_cancelacion' => 'Ya no necesito que se continúe con este reclamo.',
        ])->assertRedirect(route('incidencias.index'));

        $this->assertSame(Incidencia::ESTADO_CANCELADA, $incidencia->fresh()->id_estado_incidencia);
        $this->assertDatabaseHas('historial_estado_incidencia', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_estado_anterior' => Incidencia::ESTADO_EN_PROCESO,
            'id_estado_nuevo' => Incidencia::ESTADO_CANCELADA,
            'comentario' => 'Cancelada por el socio. Motivo: Ya no necesito que se continúe con este reclamo.',
        ]);
    }

    private function crearBorrador(): Incidencia
    {
        $this->postJson(route('incidencias.store'), $this->datos())->assertOk();

        return Incidencia::firstOrFail();
    }

    /** @return array{id_tipo_incidencia: int, id_ubicacion: int, descripcion: string, fecha_hora_evento: string} */
    private function datos(): array
    {
        return [
            'id_tipo_incidencia' => 1,
            'id_ubicacion' => 1,
            'descripcion' => 'Problema original',
            'fecha_hora_evento' => '2026-01-10T10:30',
        ];
    }
}
