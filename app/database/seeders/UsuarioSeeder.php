<?php

namespace Database\Seeders;

use Faker\Generator as FakerGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class UsuarioSeeder extends Seeder
{
    /**
     * Crea 100 usuarios de prueba para el MVP de EcoVoz Urbana:
     *
     *  - 80 "socios": cada uno con un número de socio único, tomado del
     *    universo de ~300 socios del Club El Trébol (TI Etapa 1), y rol
     *    "Socio".
     *  - 10 usuarios SIN número de socio (id_socio = NULL) con rol
     *    "Responsable" — ResponsableSeeder los convierte luego en filas
     *    de la tabla `responsables`.
     *  - 10 usuarios SIN número de socio con rol "Operador" —
     *    OperadorSeeder los convierte luego en filas de `operadores`.
     *
     * El nombre de usuario (login) tiene forma de mail ficticio:
     * "nombre.apellido@mvp.mail". La contraseña de los 100 es "123456",
     * guardada con el hash bcrypt de Laravel (Hash::make).
     *
     * Cada usuario recibe además `apellido_nombres` ("Apellido, Nombre"),
     * derivado del mismo nombre/apellido inventado que da forma al mail.
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

        $faker = fake('es_AR');
        $usados = [];

        // 80 números de socio únicos dentro del universo de socios del club.
        $numerosSocio = collect(range(1, 300))->shuffle()->take(80)->values();

        foreach ($numerosSocio as $numeroSocio) {
            $this->crearUsuario($faker, $usados, $idRolSocio, (int) $numeroSocio);
        }

        for ($i = 0; $i < 10; $i++) {
            $this->crearUsuario($faker, $usados, $idRolResponsable, null);
        }

        for ($i = 0; $i < 10; $i++) {
            $this->crearUsuario($faker, $usados, $idRolOperador, null);
        }
    }

    private function crearUsuario(FakerGenerator $faker, array &$usados, int $idRol, ?int $idSocio): void
    {
        $nombre = $faker->firstName();
        $apellido = $faker->lastName();

        $fechaCreacion = $faker->dateTimeBetween('-1 year', '-1 week');

        // 80% de los usuarios ya accedió alguna vez; el resto nunca inició sesión.
        $fechaUltimoAcceso = $faker->boolean(80)
            ? $faker->dateTimeBetween($fechaCreacion, 'now')
            : null;

        DB::table('usuarios')->insert([
            'id_socio' => $idSocio,
            'id_rol' => $idRol,
            'nombre_usuario' => $this->generarUsername($nombre, $apellido, $usados),
            'apellido_nombres' => Str::title($apellido).', '.Str::title($nombre),
            'contrasena' => Hash::make('123456'),
            'fecha_creacion' => $fechaCreacion,
            'fecha_ultimo_acceso' => $fechaUltimoAcceso,
        ]);
    }

    /**
     * Genera "nombre.apellido@mvp.mail" en minúsculas y sin acentos,
     * agregando un sufijo numérico si ya existe (evita chocar con el
     * UNIQUE de `nombre_usuario`).
     */
    private function generarUsername(string $nombre, string $apellido, array &$usados): string
    {
        $base = strtolower(Str::ascii($nombre)).'.'.strtolower(Str::ascii($apellido));
        $base = preg_replace('/[^a-z.]/', '', $base);

        $username = $base.'@mvp.mail';
        $sufijo = 1;

        while (in_array($username, $usados, true)) {
            $sufijo++;
            $username = $base.$sufijo.'@mvp.mail';
        }

        $usados[] = $username;

        return $username;
    }
}