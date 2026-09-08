<?php

namespace App\Simulacion;

use Illuminate\Contracts\Container\Container;

/**
 * Coordina todas las simulaciones del MVP y las ejecuta en orden.
 *
 * Al agregar una nueva simulación basta con implementar `Simulacion` y
 * sumarla a `$simulaciones`.
 */
class Simulador
{
    /** @var list<class-string<Simulacion>> */
    private array $simulaciones = [
        SimuladorAsignaciones::class,  // Confirmada -> En proceso (asigna responsable)
        SimuladorResoluciones::class,  // En proceso -> Resuelta (cierra la incidencia)
    ];

    public function __construct(private readonly Container $app) {}

    /** Ejecuta todas las simulaciones. Devuelve la cantidad total de incidencias afectadas. */
    public function ejecutarTodas(): int
    {
        $total = 0;

        foreach ($this->simulaciones as $clase) {
            $total += $this->app->make($clase)->ejecutar();
        }

        return $total;
    }
}
