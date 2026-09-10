<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbicacionSeeder extends Seeder
{
    /**
     * Catálogo de sectores/ubicaciones del club (ver modelo "Ubicacion"
     * y TI Etapa 1 / Etapa 3).
     *
     * IDs explícitos (1-6) para poder referenciarlos de forma estable
     * desde el seeder de Incidencia.
     */
    public function run(): void
    {
        $ubicaciones = [
            ['id' => 1, 'nombre' => 'Quinchos'],
            ['id' => 2, 'nombre' => 'Parrilleros'],
            ['id' => 3, 'nombre' => 'Piletones'],
            ['id' => 4, 'nombre' => 'Vestuarios'],
            ['id' => 5, 'nombre' => 'Exterior del Club'],
            ['id' => 6, 'nombre' => 'Otros'],
        ];

        foreach ($ubicaciones as $ubicacion) {
            DB::table('ubicaciones')->updateOrInsert(
                ['id' => $ubicacion['id']],
                ['nombre' => $ubicacion['nombre']]
            );
        }
    }
}
