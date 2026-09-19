<?php

namespace App\Jobs;

use App\Models\Incidencia;
use App\Simulacion\SimuladorResoluciones;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Evalúa una sola vez, al madurar, si esta incidencia en proceso se
 * resuelve. Es el último eslabón de la cadena: la decisión no vuelve a
 * sortearse, por lo que este job no encadena ningún otro.
 */
class EvaluarResolucionIncidencia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Incidencia $incidencia) {}

    public function handle(SimuladorResoluciones $simulador): void
    {
        $simulador->resolver($this->incidencia);
    }
}
