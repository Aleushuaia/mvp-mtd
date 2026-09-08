<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Acceso al sistema (Vista Login).
 *
 * MVP: NO se valida contraseña. Se pide únicamente el nombre de usuario;
 * si el registro existe en `usuarios`, se inicia sesión y se bifurca
 * según el rol (Socio -> Panel de Socio; resto -> panel general).
 */
class AuthController extends Controller
{
    public function mostrarLogin(): View|RedirectResponse
    {
        if (session()->has('usuario')) {
            return redirect()->route('post-login.destino');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre_usuario' => ['required', 'string', 'max:50'],
            'acepta' => ['accepted'],
        ], [
            'nombre_usuario.required' => 'Ingrese su nombre de usuario.',
            'acepta.accepted' => 'Debe aceptar los términos y condiciones de uso.',
        ]);

        $nombreUsuario = strtolower(trim($datos['nombre_usuario']));

        $usuario = Usuario::with('rol')
            ->whereRaw('LOWER(nombre_usuario) = ?', [$nombreUsuario])
            ->first();

        if ($usuario === null) {
            return back()
                ->withInput()
                ->withErrors(['nombre_usuario' => 'El usuario ingresado no existe en el sistema.']);
        }

        $usuario->forceFill(['fecha_ultimo_acceso' => now()])->save();

        session([
            'usuario' => [
                'id' => $usuario->id,
                'nombre_usuario' => $usuario->nombre_usuario,
                'apellido_nombres' => $usuario->apellido_nombres,
                'id_socio' => $usuario->id_socio,
                'id_rol' => (int) $usuario->id_rol,
                'rol' => $usuario->rol?->nombre_rol ?? 'Sin rol',
            ],
        ]);

        return redirect()->route('post-login.destino');
    }

    /**
     * Bifurca al panel que corresponde según el rol del usuario en sesión.
     */
    public function destino(): RedirectResponse
    {
        $usuario = session('usuario');

        if ($usuario === null) {
            return redirect()->route('login');
        }

        return match ((int) $usuario['id_rol']) {
            Usuario::ROL_SOCIO => redirect()->route('panel.socio'),
            Usuario::ROL_OPERADOR => redirect()->route('operador.incidencias.index'),
            default => redirect()->route('panel.general'),
        };
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('usuario');

        return redirect()->route('login');
    }
}
