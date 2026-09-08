<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    /**
     * Ejecuta las migraciones (crea todo el esquema).
     */
    public function up(): void
    {
        // ---------------------------------------------------------------
        // 1) Catálogo: Roles
        // ---------------------------------------------------------------
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_rol', 30)->comment('Administrador, Operador, Responsable, Socio');

            $table->comment('Catálogo de roles del sistema');
        });

        // ---------------------------------------------------------------
        // 2) Usuarios (núcleo de autenticación)
        // ---------------------------------------------------------------
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_socio')->nullable()
                ->comment('Referencia externa al Socio vía API (RF01). Sin FK local: Socio no se persiste aquí.');

            $table->unsignedBigInteger('id_rol')->nullable();

            $table->string('nombre_usuario', 50)->unique()->comment('Login del usuario');
            $table->string('contrasena', 255)->comment('Hash de la contraseña, nunca texto plano');
            $table->dateTime('fecha_creacion');
            $table->dateTime('fecha_ultimo_acceso')->nullable();

            $table->foreign('id_rol')->references('id')->on('roles')->nullOnDelete();

            $table->comment('Cuentas de acceso al sistema (socios, operadores, responsables, administradores)');
        });

        // ---------------------------------------------------------------
        // 3) Catálogo: Tipo_incidencia
        // ---------------------------------------------------------------
        Schema::create('tipos_incidencia', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->comment('Humo, Suciedad, Plagas, Malos olores, Desagües, Daño alambre perimetral, Otros');

            $table->comment('Catálogo de tipos de incidencia');
        });

        // ---------------------------------------------------------------
        // 4) Catálogo: Ubicacion
        // ---------------------------------------------------------------
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->comment('Quinchos, Parrilleros, Piletones, Vestuarios, Exterior del Club, Otros');

            $table->comment('Catálogo de sectores/ubicaciones del club');
        });

        // ---------------------------------------------------------------
        // 5) Catálogo: Criticidad_incidencia
        // ---------------------------------------------------------------
        Schema::create('criticidades_incidencia', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->comment('Normal, Urgente, Muy Urgente');

            $table->comment('Catálogo de niveles de criticidad');
        });

        // ---------------------------------------------------------------
        // 6) Catálogo: Estado_incidencia (PK propia: id_estado)
        // ---------------------------------------------------------------
        Schema::create('estados_incidencia', function (Blueprint $table) {
            $table->id('id_estado');
            $table->string('nombre', 30)->comment('Abierta, Asignada, En Proceso, Cierre Solicitado, Cerrada, Cancelada');

            $table->comment('Catálogo de estados del ciclo de vida de una Incidencia');
        });

        // ---------------------------------------------------------------
        // 7) Responsable (rol operativo sobre un Usuario)
        // ---------------------------------------------------------------
        Schema::create('responsables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->boolean('disponible')->default(true)->comment('Habilita/inhabilita nuevas asignaciones');

            $table->foreign('id_usuario')->references('id')->on('usuarios')->cascadeOnDelete();

            $table->comment('Persona encargada de atender/resolver incidencias');
        });

        // ---------------------------------------------------------------
        // 8) Operador (rol operativo sobre un Usuario)
        // ---------------------------------------------------------------
        Schema::create('operadores', function (Blueprint $table) {
            $table->id('id_operador');
            $table->unsignedBigInteger('id_usuario');
            $table->boolean('disponible')->default(true)->comment('Habilita/inhabilita nuevas asignaciones');

            $table->foreign('id_usuario')->references('id')->on('usuarios')->cascadeOnDelete();

            $table->comment('Personal del club que asigna responsables y gestiona incidencias');
        });

        // ---------------------------------------------------------------
        // 9) Incidencia (entidad núcleo, PK propia: id_incidencia)
        // ---------------------------------------------------------------
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id('id_incidencia');

            $table->unsignedBigInteger('id_usuario_alta')->comment('Quién completó el formulario (socio u operador asistiendo)');
            $table->unsignedBigInteger('id_usuario')->comment('El usuario que efectivamente realiza el reclamo');
            $table->unsignedBigInteger('id_tipo_incidencia');
            $table->unsignedBigInteger('id_ubicacion');
            $table->unsignedBigInteger('id_estado_incidencia');
            $table->unsignedBigInteger('id_criticidad')->nullable();

            $table->string('descripcion', 140)->comment('Comentario breve del socio (límite de wireframe)');
            $table->dateTime('fecha_hora_evento')->comment('Cuándo ocurrió el hecho, declarado por el socio');
            $table->dateTime('fecha_hora_alta')->comment('Timestamp de creación del registro en el sistema');

            $table->foreign('id_usuario_alta')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id')->on('usuarios')->restrictOnDelete();
            $table->foreign('id_tipo_incidencia')->references('id')->on('tipos_incidencia')->restrictOnDelete();
            $table->foreign('id_ubicacion')->references('id')->on('ubicaciones')->restrictOnDelete();
            $table->foreign('id_estado_incidencia')->references('id_estado')->on('estados_incidencia')->restrictOnDelete();
            $table->foreign('id_criticidad')->references('id')->on('criticidades_incidencia')->nullOnDelete();

            // Búsqueda y filtrado (RF05) y rangos de fecha (criterios de aceptación)
            $table->index('id_estado_incidencia');
            $table->index('id_tipo_incidencia');
            $table->index('fecha_hora_evento');
            $table->index('fecha_hora_alta');

            $table->comment('Registro principal: un problema informado por un socio');
        });

        // ---------------------------------------------------------------
        // 10) Incidencias_responsables (pivot N:M, PK compuesta)
        // ---------------------------------------------------------------
        Schema::create('incidencias_responsables', function (Blueprint $table) {
            $table->unsignedBigInteger('id_incidencia');
            $table->unsignedBigInteger('id_responsable');
            $table->dateTime('fecha_asignacion');

            $table->primary(['id_incidencia', 'id_responsable']);

            $table->foreign('id_incidencia')->references('id_incidencia')->on('incidencias')->cascadeOnDelete();
            $table->foreign('id_responsable')->references('id')->on('responsables')->cascadeOnDelete();

            $table->comment('Asociación N:M entre Incidencia y Responsable');
        });

        // ---------------------------------------------------------------
        // 11) Historial_Estado_Incidencia (auditoría de cambios de estado)
        // ---------------------------------------------------------------
        Schema::create('historial_estado_incidencia', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_incidencia');
            $table->unsignedBigInteger('id_estado_anterior')->nullable()->comment('Nulo si es el alta inicial');
            $table->unsignedBigInteger('id_estado_nuevo');
            $table->unsignedBigInteger('id_usuario')->comment('Quién realizó el cambio');

            $table->dateTime('fecha_hora');
            $table->string('comentario', 500)->nullable()->comment('Detalle de la acción realizada');

            $table->foreign('id_incidencia')->references('id_incidencia')->on('incidencias')->cascadeOnDelete();
            $table->foreign('id_estado_anterior')->references('id_estado')->on('estados_incidencia')->nullOnDelete();
            $table->foreign('id_estado_nuevo')->references('id_estado')->on('estados_incidencia')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id')->on('usuarios')->restrictOnDelete();

            $table->index('fecha_hora');

            $table->comment('Auditoría: registro de cada cambio de estado de una Incidencia');
        });
    }

    /**
     * Revierte las migraciones (elimina todo el esquema en orden inverso).
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('historial_estado_incidencia');
        Schema::dropIfExists('incidencias_responsables');
        Schema::dropIfExists('incidencias');
        Schema::dropIfExists('operadores');
        Schema::dropIfExists('responsables');
        Schema::dropIfExists('estados_incidencia');
        Schema::dropIfExists('criticidades_incidencia');
        Schema::dropIfExists('ubicaciones');
        Schema::dropIfExists('tipos_incidencia');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('roles');

        Schema::enableForeignKeyConstraints();
    }
};