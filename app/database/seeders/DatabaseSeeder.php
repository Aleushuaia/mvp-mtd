<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Catálogos independientes
            RoleSeeder::class,
            TipoIncidenciaSeeder::class,
            UbicacionSeeder::class,
            CriticidadIncidenciaSeeder::class,
            EstadoIncidenciaSeeder::class,

            // Depende de RoleSeeder
            UsuarioSeeder::class,

            // Dependen de UsuarioSeeder + RoleSeeder
            ResponsableSeeder::class,
            OperadorSeeder::class,
        ]);
    }
}
