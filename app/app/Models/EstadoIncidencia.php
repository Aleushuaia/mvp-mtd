<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de estados del ciclo de vida de una incidencia
 * (tabla `estados_incidencia`, PK propia `id_estado`).
 */
class EstadoIncidencia extends Model
{
    protected $table = 'estados_incidencia';

    protected $primaryKey = 'id_estado';

    public $timestamps = false;

    protected $guarded = [];
}
