<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Catálogo de roles del sistema EcoVoz Urbana.
     * Ver modelo "Roles" y Anexo 11.2 (Características Obligatorias) de TI Etapa 2.
     *
     * Se fijan IDs explícitos (1-4) para poder referenciarlos de forma estable
     * desde otros seeders (por ejemplo, al asignar id_rol en UsuarioSeeder).
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'nombre_rol' => 'Administrador'],
            ['id' => 2, 'nombre_rol' => 'Operador'],
            ['id' => 3, 'nombre_rol' => 'Responsable'],
            ['id' => 4, 'nombre_rol' => 'Socio'],
        ];

        foreach ($roles as $rol) {
            DB::table('roles')->updateOrInsert(
                ['id' => $rol['id']],
                ['nombre_rol' => $rol['nombre_rol']]
            );
        }
    }
}