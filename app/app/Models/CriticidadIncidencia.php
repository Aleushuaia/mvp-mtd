<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de niveles de criticidad (tabla `criticidades_incidencia`).
 */
class CriticidadIncidencia extends Model
{
    protected $table = 'criticidades_incidencia';

    public $timestamps = false;

    protected $guarded = [];
}
