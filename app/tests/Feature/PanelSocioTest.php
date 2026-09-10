<?php

namespace Tests\Feature;

use App\Models\Incidencia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelSocioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $socio = Usuario::where('id_rol', Usuario::ROL_SOCIO)->firstOrFail();
        $this->withSession(['usuario' => [
            'id' => $socio->id, 'id_rol' => Usuario::ROL_SOCIO,
            'rol' => 'Socio', 'nombre_usuario' => $socio->nombre_usuario,
        ]]);
    }

    public function test_el_badge_cuenta_solo_las_incidencias_en_proceso_del_socio(): void
    {
        $socioId = (int) session('usuario.id');
        $otraPersona = Usuario::where('id_rol', Usuario::ROL_SOCIO)->where('id', '!=', $socioId)->firstOrFail();
        $primera = $this->crearIncidencia($socioId, Incidencia::ESTADO_EN_PROCESO);
        $segunda = $this->crearIncidencia($socioId, Incidencia::ESTADO_EN_PROCESO);
        foreach ([Incidencia::ESTADO_BORRADOR, Incidencia::ESTADO_PENDIENTE, Incidencia::ESTADO_CONFIRMADA, Incidencia::ESTADO_RESUELTA, Incidencia::ESTADO_CANCELADA] as $estado) {
            $this->crearIncidencia($socioId, $estado);
        }
        $this->crearIncidencia($otraPersona->id, Incidencia::ESTADO_EN_PROCESO);

        $this->get(route('panel.socio'))->assertOk()
            ->assertViewHas('incidenciasEnProceso', 2)
            ->assertSee('<span class="accion-card__badge">2</span>', false)
            ->assertSee('2 incidencias en proceso');

        $primera->update(['id_estado_incidencia' => Incidencia::ESTADO_RESUELTA]);
        $this->get(route('panel.socio'))->assertOk()
            ->assertViewHas('incidenciasEnProceso', 1)->assertSee('1 incidencia en proceso');

        $segunda->update(['id_estado_incidencia' => Incidencia::ESTADO_RESUELTA]);
        $this->get(route('panel.socio'))->assertOk()
            ->assertViewHas('incidenciasEnProceso', 0)->assertDontSee('accion-card__badge');
    }

    public function test_el_panel_no_muestra_badge_si_no_hay_incidencias(): void
    {
        $this->get(route('panel.socio'))->assertOk()
            ->assertViewHas('incidenciasEnProceso', 0)->assertDontSee('accion-card__badge');
    }

    private function crearIncidencia(int $usuarioId, int $estado): Incidencia
    {
        return Incidencia::create([
            'id_usuario' => $usuarioId, 'id_usuario_alta' => $usuarioId,
            'id_tipo_incidencia' => 1, 'id_ubicacion' => 1,
            'id_estado_incidencia' => $estado, 'id_criticidad' => Incidencia::CRITICIDAD_NORMAL,
            'descripcion' => 'Reclamo de prueba',
            'fecha_hora_evento' => now()->subHour(), 'fecha_hora_alta' => now(),
        ]);
    }
}
