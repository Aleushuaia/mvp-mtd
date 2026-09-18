<?php

namespace Tests\Feature;

use App\Models\Incidencia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiltrosIncidenciasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $socio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->socio = Usuario::where('id_rol', Usuario::ROL_SOCIO)->firstOrFail();
        $this->withSession(['usuario' => [
            'id' => $this->socio->id,
            'id_rol' => Usuario::ROL_SOCIO,
            'rol' => 'Socio',
        ]]);
    }

    public function test_por_defecto_muestra_todas_las_incidencias_con_todos_tildado(): void
    {
        $this->crearIncidencia(Incidencia::ESTADO_EN_PROCESO, 'En proceso visible');
        $this->crearIncidencia(Incidencia::ESTADO_PENDIENTE, 'Pendiente visible');

        $this->get(route('incidencias.index'))
            ->assertOk()
            ->assertSee('En proceso visible')
            ->assertSee('Pendiente visible')
            ->assertSee('name="estado[]"', false)
            ->assertSee('value="4"', false)
            ->assertSee('checked', false)
            ->assertSee('data-estado-todos', false)
            ->assertSee('estado-selector__opcion--todos', false)
            ->assertDontSee('name="tipo"', false)
            ->assertDontSee('name="ubicacion"', false)
            ->assertSee('estado-selector__opcion--cancelada', false)
            ->assertSee('estado-selector__opcion--borrador', false)
            ->assertDontSee('Elegí uno o más estados.', false);
    }

    public function test_permite_elegir_mas_de_un_estado(): void
    {
        $this->crearIncidencia(Incidencia::ESTADO_EN_PROCESO, 'En proceso visible');
        $this->crearIncidencia(Incidencia::ESTADO_CONFIRMADA, 'Confirmada visible');
        $this->crearIncidencia(Incidencia::ESTADO_CANCELADA, 'Cancelada oculta');

        $this->get(route('incidencias.index', ['estado' => [
            Incidencia::ESTADO_CONFIRMADA,
            Incidencia::ESTADO_EN_PROCESO,
        ]]))
            ->assertOk()
            ->assertSee('En proceso visible')
            ->assertSee('Confirmada visible')
            ->assertDontSee('Cancelada oculta')
            ->assertViewHas('incidencias', fn ($incidencias) => $incidencias->count() === 2);
    }

    public function test_permite_filtrar_borradores(): void
    {
        $this->crearIncidencia(Incidencia::ESTADO_BORRADOR, 'Borrador visible');
        $this->crearIncidencia(Incidencia::ESTADO_EN_PROCESO, 'En proceso oculto');

        $this->get(route('incidencias.index', ['estado' => [Incidencia::ESTADO_BORRADOR]]))
            ->assertOk()
            ->assertSee('Borrador visible')
            ->assertDontSee('En proceso oculto')
            ->assertSee('estado-selector__opcion--borrador', false);
    }

    public function test_tras_filtrar_solo_quedan_tildados_los_estados_elegidos(): void
    {
        $this->crearIncidencia(Incidencia::ESTADO_EN_PROCESO, 'En proceso visible');

        $html = $this->get(route('incidencias.index', ['estado' => [Incidencia::ESTADO_EN_PROCESO]]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/name="estado\[\]" value="'.Incidencia::ESTADO_EN_PROCESO.'"\s*checked>/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/name="estado\[\]" value="'.Incidencia::ESTADO_CANCELADA.'"\s*checked>/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/data-estado-todos\s+checked/',
            $html
        );
    }

    public function test_el_contador_totaliza_solo_las_incidencias_del_socio_logueado(): void
    {
        $otroSocio = Usuario::where('id_rol', Usuario::ROL_SOCIO)->skip(1)->firstOrFail();

        $this->crearIncidencia(Incidencia::ESTADO_EN_PROCESO, 'En proceso visible');
        $this->crearIncidencia(Incidencia::ESTADO_CANCELADA, 'Cancelada oculta');
        Incidencia::create([
            'id_usuario' => $otroSocio->id,
            'id_usuario_alta' => $otroSocio->id,
            'id_tipo_incidencia' => 1,
            'id_ubicacion' => 1,
            'id_estado_incidencia' => Incidencia::ESTADO_EN_PROCESO,
            'id_criticidad' => Incidencia::CRITICIDAD_NORMAL,
            'descripcion' => 'De otro socio',
            'fecha_hora_evento' => now()->subHour(),
            'fecha_hora_alta' => now(),
        ]);

        // El total del contador es 2 (las del socio logueado), no 3: la
        // incidencia de "otroSocio" no debe sumar aunque exista en la base.
        $this->get(route('incidencias.index', ['estado' => [Incidencia::ESTADO_EN_PROCESO]]))
            ->assertOk()
            ->assertSee('1 de 2 incidencia(s) en total');
    }

    private function crearIncidencia(int $estado, string $descripcion): Incidencia
    {
        return Incidencia::create([
            'id_usuario' => $this->socio->id,
            'id_usuario_alta' => $this->socio->id,
            'id_tipo_incidencia' => 1,
            'id_ubicacion' => 1,
            'id_estado_incidencia' => $estado,
            'id_criticidad' => Incidencia::CRITICIDAD_NORMAL,
            'descripcion' => $descripcion,
            'fecha_hora_evento' => now()->subHour(),
            'fecha_hora_alta' => now(),
        ]);
    }
}
