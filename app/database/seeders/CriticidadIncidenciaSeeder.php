<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CriticidadIncidenciaSeeder extends Seeder
{
    /**
     * Catálogo de niveles de criticidad (ver modelo "Criticidad_incidencia"
     * y TI Etapa 1 / Etapa 3).
     *
     * IDs explícitos (1-3) para poder referenciarlos de forma estable
     * desde el seeder de Incidencia.
     */
    public function run(): void
    {
        $criticidades = [
            ['id' => 1, 'nombre' => 'Normal'],
            ['id' => 2, 'nombre' => 'Urgente'],
            ['id' => 3, 'nombre' => 'Muy Urgente'],
        ];

        foreach ($criticidades as $criticidad) {
            DB::table('criticidades_incidencia')->updateOrInsert(
                ['id' => $criticidad['id']],
                ['nombre' => $criticidad['nombre']]
            );
        }
    }
}