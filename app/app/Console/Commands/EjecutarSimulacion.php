<?php

namespace App\Console\Commands;

use App\Simulacion\Simulador;
use Illuminate\Console\Command;

/**
 * "El sistema en uso": procesa las revisiones, asignaciones y resoluciones
 * automáticas maduras. Se ejecuta periódicamente vía el scheduler en lugar
 * de dispararse desde las pantallas de incidencias.
 */
class EjecutarSimulacion extends Command
{
    protected $signature = 'simulacion:ejecutar';

    protected $description = 'Procesa las incidencias cuyas transiciones automáticas ya maduraron.';

    public function handle(Simulador $simulador): int
    {
        $afectadas = $simulador->ejecutarTodas();

        if ($afectadas > 0) {
            $this->info("Simulación: {$afectadas} incidencia(s) actualizada(s).");
        }

        return self::SUCCESS;
    }
}
