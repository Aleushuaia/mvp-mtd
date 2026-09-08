<?php

namespace App\Simulacion;

use App\Models\HistorialEstadoIncidencia;
use App\Models\Incidencia;
use App\Models\Operador;
use Illuminate\Support\Facades\DB;

/**
 * Simula el cierre de una incidencia por parte del club.
 *
 * Regla del MVP: unos segundos después de que una incidencia entró en
 * «En proceso» (ya tiene responsable asignado), "ingresa" un operador
 * cualquiera —no necesariamente el que asignó el responsable— y la marca
 * como «Resuelta», dejando un comentario breve en español.
 *
 * Igual que SimuladorAsignaciones, es perezosa: se dispara al navegar por
 * las pantallas de incidencias.
 */
class SimuladorResoluciones implements Simulacion
{
    /** Demora simulada entre "En proceso" y la resolución. */
    public const DEMORA_RESOLUCION_SEGUNDOS = 10;

    /** @var list<string> Comentarios posibles al resolver. */
    private const COMENTARIOS = [
        'Se resolvió sin problemas.',
        'Gracias por informar, ya está solucionado.',
        'Tarea completada por el equipo de mantenimiento.',
        'Se realizaron las tareas correspondientes y quedó normalizado.',
        'Listo, se solucionó el inconveniente.',
        'Resuelto. Ante cualquier novedad, volvé a reportarlo.',
        'Se atendió el reclamo. Gracias por tu colaboración.',
        'Problema solucionado. Que tengas buen fin de semana.',
    ];

    public function ejecutar(): int
    {
        $enProceso = Incidencia::query()
            ->where('id_estado_incidencia', Incidencia::ESTADO_EN_PROCESO)
            ->whereHas('responsables')
            ->get();

        $resueltas = 0;

        foreach ($enProceso as $incidencia) {
            if ($this->maduraParaResolver($incidencia)) {
                $this->resolver($incidencia);
                $resueltas++;
            }
        }

        return $resueltas;
    }

    /**
     * Marca la incidencia como «Resuelta» a nombre de un operador aleatorio,
     * con un comentario breve elegido al azar.
     */
    public function resolver(Incidencia $incidencia): void
    {
        if ((int) $incidencia->id_estado_incidencia !== Incidencia::ESTADO_EN_PROCESO) {
            return;
        }

        $operador = Operador::where('disponible', true)->inRandomOrder()->first();

        if ($operador === null) {
            return;
        }

        $ahora = now();
        $comentario = self::COMENTARIOS[array_rand(self::COMENTARIOS)];

        DB::transaction(function () use ($incidencia, $operador, $ahora, $comentario) {
            $estadoAnterior = (int) $incidencia->id_estado_incidencia;
            $incidencia->update(['id_estado_incidencia' => Incidencia::ESTADO_RESUELTA]);

            HistorialEstadoIncidencia::create([
                'id_incidencia' => $incidencia->id_incidencia,
                'id_estado_anterior' => $estadoAnterior,
                'id_estado_nuevo' => Incidencia::ESTADO_RESUELTA,
                'id_usuario' => $operador->id_usuario,
                'fecha_hora' => $ahora,
                'comentario' => sprintf(
                    'Incidencia resuelta por el operador %s. %s',
                    $operador->usuario->apellido_nombres ?? 'N/D',
                    $comentario,
                ),
            ]);
        });
    }

    /** ¿El último movimiento de la incidencia ya tiene la antigüedad mínima? */
    private function maduraParaResolver(Incidencia $incidencia): bool
    {
        $ultimo = $incidencia->historial()
            ->reorder()
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->first();

        return $ultimo !== null
            && $ultimo->fecha_hora->clone()->addSeconds(self::DEMORA_RESOLUCION_SEGUNDOS)->isPast();
    }
}
