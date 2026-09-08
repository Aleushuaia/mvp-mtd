<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de roles del sistema (tabla `roles`).
 */
class Rol extends Model
{
    protected $table = 'roles';

    public $timestamps = false;

    protected $guarded = [];
}
