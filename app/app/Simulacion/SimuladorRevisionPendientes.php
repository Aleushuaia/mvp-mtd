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
    public const DEMORA_REVISION_SEGUNDOS = 6;

    /** De cada 100 revisiones, cuántas cancelan el reclamo en vez de confirmarlo. */
    private const PROBABILIDAD_CANCELACION = 10;

    /** Tope de incidencias Canceladas, en % del total de incidencias de la base. */
    private const TOPE_CANCELADAS_PORCENTAJE = 10;

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

    /**
     * Casi todas las revisiones confirman: sólo se cancela con una
     * probabilidad baja y, además, nunca si esa cancelación llevaría a las
     * Canceladas por encima del tope sobre el total de incidencias.
     */
    protected function debeConfirmar(): bool
    {
        if (! $this->admiteUnaCancelacionMas()) {
            return true;
        }

        return random_int(1, 100) > self::PROBABILIDAD_CANCELACION;
    }

    /** Cuenta toda la base (también las canceladas a mano por socios u operadores). */
    private function admiteUnaCancelacionMas(): bool
    {
        $total = Incidencia::count();
        $canceladas = Incidencia::where('id_estado_incidencia', Incidencia::ESTADO_CANCELADA)->count();

        return ($canceladas + 1) * 100 <= $total * self::TOPE_CANCELADAS_PORCENTAJE;
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
