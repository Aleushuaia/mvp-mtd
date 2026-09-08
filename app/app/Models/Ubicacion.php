<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de sectores/ubicaciones del club (tabla `ubicaciones`).
 */
class Ubicacion extends Model
{
    protected $table = 'ubicaciones';

    public $timestamps = false;

    protected $guarded = [];
}
