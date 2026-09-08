<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuenta de acceso al sistema (tabla `usuarios`).
 *
 * En esta etapa del MVP el login sólo pide el nombre de usuario: si el
 * registro existe, se considera autenticado (no se valida contraseña).
 */
class Usuario extends Model
{
    protected $table = 'usuarios';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['contrasena'];

    public const ROL_ADMINISTRADOR = 1;
    public const ROL_OPERADOR = 2;
    public const ROL_RESPONSABLE = 3;
    public const ROL_SOCIO = 4;

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function esSocio(): bool
    {
        return (int) $this->id_rol === self::ROL_SOCIO;
    }
}
