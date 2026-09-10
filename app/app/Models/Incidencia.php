<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Registro principal del sistema: un problema informado por un socio
 * (tabla `incidencias`, PK propia `id_incidencia`).
 */
class Incidencia extends Model
{
    protected $table = 'incidencias';

    protected $primaryKey = 'id_incidencia';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'fecha_hora_evento' => 'datetime',
        'fecha_hora_alta' => 'datetime',
    ];

    /** Estados (coinciden con estados_incidencia.id_estado / EstadoIncidenciaSeeder). */
    public const ESTADO_BORRADOR = 1;

    public const ESTADO_PENDIENTE = 2;

    public const ESTADO_CONFIRMADA = 3;

    public const ESTADO_EN_PROCESO = 4;

    public const ESTADO_RESUELTA = 5;

    public const ESTADO_CANCELADA = 6;

    /** Criticidad por defecto de una incidencia nueva. */
    public const CRITICIDAD_NORMAL = 1;

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoIncidencia::class, 'id_tipo_incidencia');
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'id_ubicacion');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoIncidencia::class, 'id_estado_incidencia', 'id_estado');
    }

    public function criticidad(): BelongsTo
    {
        return $this->belongsTo(CriticidadIncidencia::class, 'id_criticidad');
    }

    /** Usuario que realiza el reclamo (el socio). */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    /** Usuario que completó el formulario de alta (socio u operador asistiendo). */
    public function usuarioAlta(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_alta');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstadoIncidencia::class, 'id_incidencia', 'id_incidencia')
            ->orderBy('fecha_hora')
            ->orderBy('id');
    }

    /** Responsable(s) asignado(s) vía pivote `incidencias_responsables`. */
    public function responsables(): BelongsToMany
    {
        return $this->belongsToMany(
            Responsable::class,
            'incidencias_responsables',
            'id_incidencia',
            'id_responsable',
        )->withPivot('fecha_asignacion');
    }

    public function tieneResponsable(): bool
    {
        return $this->responsables()->exists();
    }

    public function estaBorrador(): bool
    {
        return (int) $this->id_estado_incidencia === self::ESTADO_BORRADOR;
    }

    public function estaEnProceso(): bool
    {
        return (int) $this->id_estado_incidencia === self::ESTADO_EN_PROCESO;
    }

    /** El operador puede editar criticidad y responsables sólo con la incidencia activa. */
    public function esGestionablePorOperador(): bool
    {
        return $this->estaEnProceso();
    }

    /**
     * Filtro compartido (Mis incidencias / panel del operador):
     * por N.º, texto de descripción y estado.
     *
     * @param  array{numero?:mixed, descripcion?:mixed, estado?:array<int, mixed>|mixed}  $filtros
     */
    public function scopeFiltrar(Builder $query, array $filtros): Builder
    {
        $estadoRecibido = $filtros['estado'] ?? [];
        $estados = collect(is_array($estadoRecibido) ? $estadoRecibido : [$estadoRecibido])
            ->filter(fn (mixed $estado): bool => is_numeric($estado))
            ->map(fn (mixed $estado): int => (int) $estado)
            ->unique()
            ->values()
            ->all();

        if ($estados === []) {
            $estados = [self::ESTADO_EN_PROCESO];
        }

        return $query
            ->when(filled($filtros['numero'] ?? null),
                fn (Builder $q) => $q->where('id_incidencia', (int) $filtros['numero']))
            ->when(filled($filtros['descripcion'] ?? null),
                fn (Builder $q) => $q->where('descripcion', 'ilike', '%'.trim((string) $filtros['descripcion']).'%'))
            ->whereIn('id_estado_incidencia', $estados);
    }
}
