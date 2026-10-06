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

    public function test_por_defecto_muestra_solo_las_incidencias_en_proceso(): void
    {
        $this->crearIncidencia(Incidencia::ESTADO_EN_PROCESO, 'En proceso visible');
        $this->crearIncidencia(Incidencia::ESTADO_PENDIENTE, 'Pendiente oculta');
        $this->crearIncidencia(Incidencia::ESTADO_CANCELADA, 'Cancelada oculta');

        $this->get(route('incidencias.index'))
            ->assertOk()
            ->assertSee('En proceso visible')
            ->assertDontSee('Pendiente oculta')
            ->assertDontSee('Cancelada oculta')
            ->assertViewHas('incidencias', fn ($incidencias) => $incidencias->count() === 1)
            ->assertSee('1 de 3 incidencia(s) en total')
            ->assertSee('estado-selector__opcion--cancelada', false)
            ->assertSee('estado-selector__opcion--borrador', false)
            ->assertDontSee('name="tipo"', false)
            ->assertDontSee('name="ubicacion"', false);
    }

    public function test_por_defecto_solo_queda_tildado_el_estado_en_proceso(): void
    {
        $html = $this->get(route('incidencias.index'))->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/name="estado\[\]"[^>]*\schecked[\s>]/', $html));
        $this->assertMatchesRegularExpression(
            '/name="estado\[\]" value="'.Incidencia::ESTADO_EN_PROCESO.'"\s*checked\b/',
            $html
        );
    }

    public function test_no_existe_el_boton_todos(): void
    {
        $this->get(route('incidencias.index'))
            ->assertOk()
            ->assertDontSee('<span>Todos</span>', false)
            ->assertDontSee('estado-selector__opcion--todos', false)
            ->assertDontSee('Seleccionar todos los estados');
    }

    public function test_limpiar_campos_restablece_estados_y_avisa(): void
    {
        $html = $this->get(route('incidencias.index'))
            ->assertOk()
            ->assertSee('data-limpiar-campos', false)
            ->assertSee('data-restablecer-estados', false)
            ->assertSee('data-aviso-restablecido', false)
            ->assertSee('filtros-aviso__caja', false)
            ->assertSee('Los campos de búsqueda fueron restablecidos.')
            ->getContent();

        // Sólo "En proceso" es el estado predeterminado al que se vuelve.
        $this->assertSame(1, preg_match_all('/\sdata-predeterminado[\s>]/', $html));
        $this->assertMatchesRegularExpression(
            '/name="estado\[\]" value="'.Incidencia::ESTADO_EN_PROCESO.'"[^>]*data-predeterminado/',
            $html
        );
    }

    public function test_sin_incidencias_en_proceso_no_dice_que_no_tiene_incidencias(): void
    {
        $this->crearIncidencia(Incidencia::ESTADO_PENDIENTE, 'Pendiente oculta');

        $this->get(route('incidencias.index'))
            ->assertOk()
            ->assertSee('Sin resultados')
            ->assertDontSee('Todavía no tiene incidencias registradas');
    }

    public function test_la_busqueda_por_descripcion_ignora_mayusculas_y_acentos(): void
    {
        $this->crearIncidencia(Incidencia::ESTADO_EN_PROCESO, 'Pérdida de agua en el vestuario');
        $this->crearIncidencia(Incidencia::ESTADO_EN_PROCESO, 'Luz quemada en el pasillo');

        foreach (['perdida', 'PERDIDA', 'pérdida', 'PÉRDIDA', 'Perdida de AGUA', 'vestuário'] as $busqueda) {
            $this->get(route('incidencias.index', ['descripcion' => $busqueda]))
                ->assertOk()
                ->assertSee('Pérdida de agua en el vestuario')
                ->assertDontSee('Luz quemada en el pasillo');
        }

        // Y a la inversa: el texto guardado sin acento se encuentra buscando con acento.
        $this->crearIncidencia(Incidencia::ESTADO_EN_PROCESO, 'Reparacion del tecnico');

        $this->get(route('incidencias.index', ['descripcion' => 'reparación del técnico']))
            ->assertOk()
            ->assertSee('Reparacion del tecnico')
            ->assertDontSee('Luz quemada en el pasillo');
    }

    public function test_la_busqueda_por_descripcion_conserva_la_enie_como_letra_distinta(): void
    {
        $this->crearIncidencia(Incidencia::ESTADO_EN_PROCESO, 'Cartel roto en la cabaña');

        $this->get(route('incidencias.index', ['descripcion' => 'cabana']))
            ->assertOk()
            ->assertDontSee('Cartel roto en la cabaña');

        $this->get(route('incidencias.index', ['descripcion' => 'cabaña']))
            ->assertOk()
            ->assertSee('Cartel roto en la cabaña');
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
            '/name="estado\[\]" value="'.Incidencia::ESTADO_EN_PROCESO.'"\s*checked\b/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/name="estado\[\]" value="'.Incidencia::ESTADO_CANCELADA.'"\s*checked\b/',
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
