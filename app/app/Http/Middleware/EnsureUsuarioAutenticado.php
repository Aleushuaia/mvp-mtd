<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que exista un usuario en sesión (login del MVP, sin contraseña).
 */
class EnsureUsuarioAutenticado
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('usuario')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
