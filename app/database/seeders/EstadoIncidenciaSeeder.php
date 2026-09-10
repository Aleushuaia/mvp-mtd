<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoIncidenciaSeeder extends Seeder
{
    /**
     * Catálogo de estados del ciclo de vida de una Incidencia (ver
     * modelo "Estado_incidencia" y el esquema de estados/transiciones
     * definido para TI Etapa 3).
     *
     * Nota: la PK de esta tabla se llama `id_estado` (no `id`), tal como
     * quedó definida en la migración.
     *
     * IDs explícitos (1-6) para poder referenciarlos de forma estable
     * desde el seeder de Incidencia / Historial_Estado_Incidencia.
     */
    public function run(): void
    {
        $estados = [
            ['id_estado' => 1, 'nombre' => 'Borrador'],
            ['id_estado' => 2, 'nombre' => 'Pendiente'],
            ['id_estado' => 3, 'nombre' => 'Confirmada'],
            ['id_estado' => 4, 'nombre' => 'En proceso'],
            ['id_estado' => 5, 'nombre' => 'Resuelta'],
            ['id_estado' => 6, 'nombre' => 'Cancelada'],
        ];

        foreach ($estados as $estado) {
            DB::table('estados_incidencia')->updateOrInsert(
                ['id_estado' => $estado['id_estado']],
                ['nombre' => $estado['nombre']]
            );
        }
    }
}
