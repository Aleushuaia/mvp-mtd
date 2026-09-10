<?php

namespace App\Simulacion;

use App\Models\HistorialEstadoIncidencia;
use App\Models\Incidencia;
use App\Models\Operador;
use Illuminate\Support\Facades\DB;

/**
 * Simula la revisión inicial de un reclamo por parte de un operador.
 */
class SimuladorRevisionPendientes implements Simulacion
{
    public const DEMORA_REVISION_SEGUNDOS = 10;

    public function ejecutar(): int
    {
        $pendientes = Incidencia::query()
            ->where('id_estado_incidencia', Incidencia::ESTADO_PENDIENTE)
            ->get();

        $revisadas = 0;

        foreach ($pendientes as $incidencia) {
            if ($this->revisar($incidencia)) {
                $revisadas++;
            }
        }

        return $revisadas;
    }

    public function revisar(Incidencia $incidencia): bool
    {
        return DB::transaction(function () use ($incidencia) {
            $incidencia = Incidencia::whereKey($incidencia->getKey())->lockForUpdate()->first();
            if ($incidencia === null || (int) $incidencia->id_estado_incidencia !== Incidencia::ESTADO_PENDIENTE
                || ! $this->pendienteMaduro($incidencia)) {
                return false;
            }

            $operador = Operador::where('disponible', true)->inRandomOrder()->first();
            if ($operador === null) {
                return false;
            }

            $confirmada = $this->debeConfirmar();
            $estadoNuevo = $confirmada ? Incidencia::ESTADO_CONFIRMADA : Incidencia::ESTADO_CANCELADA;
            $ahora = now();

            $incidencia->update(['id_estado_incidencia' => $estadoNuevo]);

            HistorialEstadoIncidencia::create([
                'id_incidencia' => $incidencia->id_incidencia,
                'id_estado_anterior' => Incidencia::ESTADO_PENDIENTE,
                'id_estado_nuevo' => $estadoNuevo,
                'id_usuario' => $operador->id_usuario,
                'fecha_hora' => $ahora,
                'comentario' => $confirmada
                    ? 'Revisión automática: el operador aprobó la continuación del reclamo.'
                    : 'Revisión automática: el operador canceló el reclamo.',
            ]);

            return true;
        });
    }

    protected function debeConfirmar(): bool
    {
        return random_int(0, 1) === 1;
    }

    private function pendienteMaduro(Incidencia $incidencia): bool
    {
        $pendiente = $incidencia->historial()
            ->where('id_estado_nuevo', Incidencia::ESTADO_PENDIENTE)
            ->reorder('fecha_hora', 'desc')
            ->first();

        return $pendiente !== null
            && $pendiente->fecha_hora->clone()->addSeconds(self::DEMORA_REVISION_SEGUNDOS)->lessThanOrEqualTo(now());
    }
}
