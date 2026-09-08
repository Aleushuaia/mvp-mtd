<?php

namespace App\Simulacion;

use App\Models\HistorialEstadoIncidencia;
use App\Models\Incidencia;
use App\Models\Operador;
use App\Models\Responsable;
use Illuminate\Support\Facades\DB;

/**
 * Simula la asignación de responsables que en producción haría un operador.
 *
 * Regla del MVP: unos segundos después de que el socio confirma una
 * incidencia, "el sistema en uso" le asigna automáticamente UN responsable
 * (y deja registrado qué operador lo hizo). La incidencia pasa a «En proceso».
 *
 * Como no hay un worker de colas corriendo, la simulación es perezosa: se
 * dispara al navegar por las pantallas de incidencias (ver
 * IncidenciaController). `ejecutar()` recorre las incidencias confirmadas sin
 * responsable cuya confirmación ya tiene la antigüedad mínima y las asigna.
 */
class SimuladorAsignaciones implements Simulacion
{
    /** Demora simulada entre la confirmación y la asignación automática. */
    public const DEMORA_ASIGNACION_SEGUNDOS = 10;

    public function ejecutar(): int
    {
        $pendientes = Incidencia::query()
            ->where('id_estado_incidencia', Incidencia::ESTADO_CONFIRMADA)
            ->whereDoesntHave('responsables')
            ->get();

        $asignadas = 0;

        foreach ($pendientes as $incidencia) {
            if ($this->confirmacionMaduro($incidencia)) {
                $this->asignar($incidencia);
                $asignadas++;
            }
        }

        return $asignadas;
    }

    /**
     * Asigna a una incidencia un operador (quien "hizo" la asignación) y un
     * responsable, ambos elegidos al azar. Un único responsable por incidencia.
     */
    public function asignar(Incidencia $incidencia): void
    {
        if ($incidencia->tieneResponsable()) {
            return;
        }

        $operador = Operador::where('disponible', true)->inRandomOrder()->first();
        $responsable = Responsable::where('disponible', true)->inRandomOrder()->first();

        if ($operador === null || $responsable === null) {
            return;
        }

        $ahora = now();

        DB::transaction(function () use ($incidencia, $operador, $responsable, $ahora) {
            $incidencia->responsables()->attach($responsable->id, ['fecha_asignacion' => $ahora]);

            $estadoAnterior = (int) $incidencia->id_estado_incidencia;
            $incidencia->update(['id_estado_incidencia' => Incidencia::ESTADO_EN_PROCESO]);

            HistorialEstadoIncidencia::create([
                'id_incidencia' => $incidencia->id_incidencia,
                'id_estado_anterior' => $estadoAnterior,
                'id_estado_nuevo' => Incidencia::ESTADO_EN_PROCESO,
                'id_usuario' => $operador->id_usuario,
                'fecha_hora' => $ahora,
                'comentario' => sprintf(
                    'Asignación automática del sistema. Operador: %s. Responsable asignado: %s.',
                    $operador->usuario->apellido_nombres ?? 'N/D',
                    $responsable->usuario->apellido_nombres ?? 'N/D',
                ),
            ]);
        });
    }

    /** ¿La confirmación de esta incidencia ya tiene la antigüedad mínima? */
    private function confirmacionMaduro(Incidencia $incidencia): bool
    {
        $confirmacion = $incidencia->historial()
            ->where('id_estado_nuevo', Incidencia::ESTADO_CONFIRMADA)
            ->reorder('fecha_hora', 'desc')
            ->first();

        return $confirmacion !== null
            && $confirmacion->fecha_hora->clone()->addSeconds(self::DEMORA_ASIGNACION_SEGUNDOS)->isPast();
    }
}
