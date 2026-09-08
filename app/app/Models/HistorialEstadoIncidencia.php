<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Auditoría: un registro por cada cambio de estado de una incidencia
 * (tabla `historial_estado_incidencia`).
 */
class HistorialEstadoIncidencia extends Model
{
    protected $table = 'historial_estado_incidencia';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function estadoAnterior(): BelongsTo
    {
        return $this->belongsTo(EstadoIncidencia::class, 'id_estado_anterior', 'id_estado');
    }

    public function estadoNuevo(): BelongsTo
    {
        return $this->belongsTo(EstadoIncidencia::class, 'id_estado_nuevo', 'id_estado');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
