<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class UsuarioSeeder extends Seeder
{
    /**
     * Crea 40 usuarios de prueba para el MVP de EcoVoz Urbana, con nombre
     * de usuario simple y predecible (sin depender de Faker):
     *
     *  - 20 "socios": socio1..socio20, rol "Socio".
     *  - 10 "operadores": operador1..operador10, rol "Operador"
     *    (id_socio = NULL) — OperadorSeeder los convierte luego en filas
     *    de la tabla `operadores`.
     *  - 10 "responsables": responsable1..responsable10, rol "Responsable"
     *    (id_socio = NULL) — ResponsableSeeder los convierte luego en
     *    filas de la tabla `responsables`.
     *
     * La contraseña de los 40 es genérica ("123456"), guardada con el
     * hash bcrypt de Laravel (Hash::make): no se valida en este MVP.
     *
     * `apellido_nombres` se genera al azar con Faker (locale es_AR),
     * alternando género par/impar para una mezcla pareja de hombres y
     * mujeres con nombres típicos argentinos.
     *
     * Antes de crearlos, se borra todo el contenido de `usuarios` (el
     * borrado hace cascade sobre `operadores`/`responsables`), así el
     * seeder puede volver a correrse para repoblar la tabla desde cero.
     *
     * Los roles se buscan por nombre en la tabla `roles` (no se
     * hardcodean IDs), así que este seeder depende de RoleSeeder.
     */
    public function run(): void
    {
        $idRolSocio = DB::table('roles')->where('nombre_rol', 'Socio')->value('id');
        $idRolResponsable = DB::table('roles')->where('nombre_rol', 'Responsable')->value('id');
        $idRolOperador = DB::table('roles')->where('nombre_rol', 'Operador')->value('id');

        if (! $idRolSocio || ! $idRolResponsable || ! $idRolOperador) {
            throw new RuntimeException(
                'Faltan roles base (Socio, Responsable, Operador). Corré primero RoleSeeder.'
            );
        }

        // `incidencias` tiene FK RESTRICT hacia `usuarios`: hay que borrarlas
        // primero (esto además hace cascade sobre `historial_estado_incidencia`
        // e `incidencias_responsables`) para poder vaciar `usuarios` sin chocar
        // con esa restricción. El delete de `usuarios` hace cascade sobre
        // `operadores`/`responsables`.
        DB::table('incidencias')->delete();
        DB::table('usuarios')->delete();

        $contrasena = Hash::make('123456');
        $ahora = now();
        $faker = fake('es_AR');

        for ($i = 1; $i <= 20; $i++) {
            $this->crearUsuario($idRolSocio, "socio{$i}", $this->nombreAleatorio($faker, $i), $contrasena, $ahora, $i);
        }

        for ($i = 1; $i <= 10; $i++) {
            $this->crearUsuario($idRolOperador, "operador{$i}", $this->nombreAleatorio($faker, $i), $contrasena, $ahora, null);
        }

        for ($i = 1; $i <= 10; $i++) {
            $this->crearUsuario($idRolResponsable, "responsable{$i}", $this->nombreAleatorio($faker, $i), $contrasena, $ahora, null);
        }
    }

    /**
     * Nombre y apellido argentinos al azar, alternando géneros para
     * lograr una mezcla pareja de hombres y mujeres.
     */
    private function nombreAleatorio(\Faker\Generator $faker, int $indice): string
    {
        $nombre = $indice % 2 === 0 ? $faker->firstNameFemale() : $faker->firstNameMale();
        $apellido = $faker->lastName();

        return Str::title($apellido).', '.Str::title($nombre);
    }

    private function crearUsuario(
        int $idRol,
        string $nombreUsuario,
        string $apellidoNombres,
        string $contrasena,
        $fechaCreacion,
        ?int $idSocio
    ): void {
        DB::table('usuarios')->insert([
            'id_socio' => $idSocio,
            'id_rol' => $idRol,
            'nombre_usuario' => $nombreUsuario,
            'apellido_nombres' => $apellidoNombres,
            'contrasena' => $contrasena,
            'fecha_creacion' => $fechaCreacion,
            'fecha_ultimo_acceso' => null,
        ]);
    }
}
