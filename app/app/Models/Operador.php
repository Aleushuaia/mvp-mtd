<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Personal del club que asigna responsables y gestiona incidencias
 * (tabla `operadores`, PK propia `id_operador`). Rol operativo sobre un
 * `Usuario` con rol "Operador".
 */
class Operador extends Model
{
    protected $table = 'operadores';

    protected $primaryKey = 'id_operador';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'disponible' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
