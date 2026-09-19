<?php

namespace App\Jobs;

use App\Models\Incidencia;
use App\Simulacion\SimuladorAsignaciones;
use App\Simulacion\SimuladorRevisionPendientes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Evalúa una sola vez, al madurar, si la revisión automática confirma o
 * cancela esta incidencia puntual. Se dispara al confirmarse el alta
 * (Pendiente) en vez de sondear periódicamente toda la tabla.
 */
class EvaluarRevisionIncidencia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Incidencia $incidencia) {}

    public function handle(SimuladorRevisionPendientes $simulador): void
    {
        $simulador->revisar($this->incidencia);

        if ($this->incidencia->fresh()?->id_estado_incidencia === Incidencia::ESTADO_CONFIRMADA) {
            EvaluarAsignacionIncidencia::dispatch($this->incidencia)
                ->delay(now()->addSeconds(SimuladorAsignaciones::DEMORA_ASIGNACION_SEGUNDOS));
        }
    }
}
