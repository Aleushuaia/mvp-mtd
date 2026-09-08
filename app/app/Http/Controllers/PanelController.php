<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Paneles principales según el rol del usuario autenticado.
 */
class PanelController extends Controller
{
    /**
     * Vista Panel de Socio: sólo dos acciones — "Nueva Incidencia" y
     * "Mis incidencias" (flujo principal, TI Etapa 2).
     */
    public function socio(): View
    {
        return view('panel.socio', [
            'usuario' => session('usuario'),
        ]);
    }

    /**
     * Panel general para roles no-socio (Operador, Responsable, Admin).
     */
    public function general(): View
    {
        return view('panel.general', [
            'usuario' => session('usuario'),
        ]);
    }
}
