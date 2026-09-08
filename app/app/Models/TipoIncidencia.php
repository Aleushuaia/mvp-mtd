<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de tipos de incidencia (tabla `tipos_incidencia`).
 */
class TipoIncidencia extends Model
{
    protected $table = 'tipos_incidencia';

    public $timestamps = false;

    protected $guarded = [];
}
