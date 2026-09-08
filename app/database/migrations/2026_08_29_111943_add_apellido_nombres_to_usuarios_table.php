<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Agrega `apellido_nombres` (nombre y apellido para mostrar) a `usuarios`.
     *
     * Al final se hace un backfill de las filas ya existentes derivando el
     * valor del propio `nombre_usuario` ("nombre.apellido@mvp.mail"), para
     * que ningún registro quede sin dato de presentación.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('apellido_nombres', 100)->nullable()
                ->after('nombre_usuario')
                ->comment('Apellido y nombres del usuario, para mostrar en la interfaz');
        });

        DB::table('usuarios')
            ->whereNull('apellido_nombres')
            ->orderBy('id')
            ->each(function ($usuario) {
                DB::table('usuarios')
                    ->where('id', $usuario->id)
                    ->update(['apellido_nombres' => $this->derivarDesdeUsername($usuario->nombre_usuario)]);
            });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('apellido_nombres');
        });
    }

    /**
     * "gabriela.sanchez@mvp.mail" -> "Sanchez, Gabriela".
     */
    private function derivarDesdeUsername(string $username): string
    {
        $local = Str::before($username, '@');
        $partes = explode('.', $local, 2);

        $nombre = Str::title(preg_replace('/\d+$/', '', $partes[0] ?? '')) ?: 'Socio';
        $apellido = Str::title(preg_replace('/\d+$/', '', $partes[1] ?? '')) ?: 'Del Club';

        return trim($apellido.', '.$nombre);
    }
};
