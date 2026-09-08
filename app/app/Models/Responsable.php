<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persona encargada de atender/resolver una incidencia (tabla `responsables`).
 * Es un rol operativo montado sobre un `Usuario` con rol "Responsable".
 */
class Responsable extends Model
{
    protected $table = 'responsables';

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
