<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoIncidenciaSeeder extends Seeder
{
    /**
     * Catálogo de tipos de incidencia (ver modelo "Tipo_incidencia" y
     * TI Etapa 1 / Etapa 3).
     *
     * IDs explícitos (1-7) para poder referenciarlos de forma estable
     * desde el seeder de Incidencia.
     */
    public function run(): void
    {
        $tipos = [
            ['id' => 1, 'nombre' => 'Humo'],
            ['id' => 2, 'nombre' => 'Suciedad'],
            ['id' => 3, 'nombre' => 'Plagas'],
            ['id' => 4, 'nombre' => 'Malos olores'],
            ['id' => 5, 'nombre' => 'Desagües'],
            ['id' => 6, 'nombre' => 'Otros'],
        ];

        foreach ($tipos as $tipo) {
            DB::table('tipos_incidencia')->updateOrInsert(
                ['id' => $tipo['id']],
                ['nombre' => $tipo['nombre']]
            );
        }
    }
}
