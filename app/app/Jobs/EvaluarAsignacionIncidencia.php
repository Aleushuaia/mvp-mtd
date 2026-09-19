<?php

namespace App\Jobs;

use App\Models\Incidencia;
use App\Simulacion\SimuladorAsignaciones;
use App\Simulacion\SimuladorResoluciones;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Evalúa una sola vez, al madurar, si corresponde asignar responsable a
 * esta incidencia confirmada. Encadenado desde EvaluarRevisionIncidencia.
 */
class EvaluarAsignacionIncidencia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Incidencia $incidencia) {}

    public function handle(SimuladorAsignaciones $simulador): void
    {
        $simulador->asignar($this->incidencia);

        if ($this->incidencia->fresh()?->id_estado_incidencia === Incidencia::ESTADO_EN_PROCESO) {
            EvaluarResolucionIncidencia::dispatch($this->incidencia)
                ->delay(now()->addSeconds(SimuladorResoluciones::DEMORA_RESOLUCION_SEGUNDOS));
        }
    }
}
