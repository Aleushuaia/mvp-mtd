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
 * cualquiera —no necesariamente el que asignó el responsable— y decide si
 * la marca como «Resuelta» o la deja «En proceso»; la decisión se guarda y
 * no vuelve a sortearse. En vez de una moneda 50/50, la probabilidad se
 * inclina hacia el estado (Resuelta / En proceso) que tenga MENOS
 * incidencias en toda la base —no sólo las del socio de este reclamo— para
 * que ambos caminos terminen con cantidades parejas en el panorama general.
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
            ->whereNull('resolucion_simulada_evaluada_at')
            ->whereHas('responsables')
            ->get();

        $resueltas = 0;

        foreach ($enProceso as $incidencia) {
            if ($this->resolver($incidencia)) {
                $resueltas++;
            }
        }

        return $resueltas;
    }

    /**
     * Evalúa una sola vez si resuelve, conservando la decisión incluso
     * cuando el estado sigue siendo «En proceso».
     */
    public function resolver(Incidencia $incidencia): bool
    {
        return DB::transaction(function () use ($incidencia) {
            $incidencia = Incidencia::whereKey($incidencia->getKey())->lockForUpdate()->first();
            if ($incidencia === null || (int) $incidencia->id_estado_incidencia !== Incidencia::ESTADO_EN_PROCESO
                || $incidencia->resolucion_simulada_evaluada_at !== null
                || ! $incidencia->tieneResponsable() || ! $this->maduraParaResolver($incidencia)) {
                return false;
            }

            $operador = Operador::where('disponible', true)->inRandomOrder()->first();
            if ($operador === null) {
                return false;
            }

            $ahora = now();
            $incidencia->update(['resolucion_simulada_evaluada_at' => $ahora]);
            if (! $this->debeResolver()) {
                return false;
            }

            $comentario = self::COMENTARIOS[array_rand(self::COMENTARIOS)];
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

            return true;
        });
    }

    /**
     * Se inclina por el estado (Resuelta / En proceso) menos representado en
     * toda la base de incidencias, para emparejar ambos caminos en el
     * panorama general en vez de por socio individual.
     */
    protected function debeResolver(): bool
    {
        $resueltas = Incidencia::where('id_estado_incidencia', Incidencia::ESTADO_RESUELTA)->count();
        $enProceso = Incidencia::where('id_estado_incidencia', Incidencia::ESTADO_EN_PROCESO)->count();

        if ($resueltas === $enProceso) {
            return random_int(0, 1) === 1;
        }

        return $resueltas < $enProceso;
    }

    /** Cuenta desde la entrada a En proceso, sin reiniciar por cambios de criticidad. */
    private function maduraParaResolver(Incidencia $incidencia): bool
    {
        $ultimo = $incidencia->historial()
            ->where('id_estado_nuevo', Incidencia::ESTADO_EN_PROCESO)
            ->where(function ($query) {
                $query->whereNull('id_estado_anterior')
                    ->orWhere('id_estado_anterior', '!=', Incidencia::ESTADO_EN_PROCESO);
            })
            ->reorder()
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->first();

        return $ultimo !== null
            && $ultimo->fecha_hora->clone()->addSeconds(self::DEMORA_RESOLUCION_SEGUNDOS)->lessThanOrEqualTo(now());
    }
}
