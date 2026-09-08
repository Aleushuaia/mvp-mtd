<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResponsableSeeder extends Seeder
{
    /**
     * Convierte en Responsable a los usuarios que UsuarioSeeder creó con
     * rol "Responsable" y sin número de socio (personal del club, no
     * socios). Depende de que UsuarioSeeder ya se haya ejecutado.
     *
     * Usa updateOrInsert por id_usuario, así es seguro volver a correrlo
     * sin duplicar filas.
     */
    public function run(): void
    {
        $idRolResponsable = DB::table('roles')->where('nombre_rol', 'Responsable')->value('id');

        if (! $idRolResponsable) {
            throw new RuntimeException('Falta el rol "Responsable". Corré primero RoleSeeder.');
        }

        $idsUsuarios = DB::table('usuarios')
            ->where('id_rol', $idRolResponsable)
            ->whereNull('id_socio')
            ->pluck('id');

        if ($idsUsuarios->isEmpty()) {
            throw new RuntimeException(
                'No hay usuarios con rol "Responsable" y sin socio. Corré primero UsuarioSeeder.'
            );
        }

        foreach ($idsUsuarios as $idUsuario) {
            DB::table('responsables')->updateOrInsert(
                ['id_usuario' => $idUsuario],
                ['disponible' => true]
            );
        }
    }
}