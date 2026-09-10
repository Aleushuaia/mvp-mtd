<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OperadorSeeder extends Seeder
{
    /**
     * Convierte en Operador a los usuarios que UsuarioSeeder creó con
     * rol "Operador" y sin número de socio (personal del club, no
     * socios). Depende de que UsuarioSeeder ya se haya ejecutado.
     *
     * Usa updateOrInsert por id_usuario, así es seguro volver a correrlo
     * sin duplicar filas.
     */
    public function run(): void
    {
        $idRolOperador = DB::table('roles')->where('nombre_rol', 'Operador')->value('id');

        if (! $idRolOperador) {
            throw new RuntimeException('Falta el rol "Operador". Corré primero RoleSeeder.');
        }

        $idsUsuarios = DB::table('usuarios')
            ->where('id_rol', $idRolOperador)
            ->whereNull('id_socio')
            ->pluck('id');

        if ($idsUsuarios->isEmpty()) {
            throw new RuntimeException(
                'No hay usuarios con rol "Operador" y sin socio. Corré primero UsuarioSeeder.'
            );
        }

        foreach ($idsUsuarios as $idUsuario) {
            DB::table('operadores')->updateOrInsert(
                ['id_usuario' => $idUsuario],
                ['disponible' => true]
            );
        }
    }
}
