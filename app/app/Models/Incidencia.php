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

    /**
     * Vocales acentuadas (y con diéresis) y su letra base, para la búsqueda
     * por descripción. Se listan ambas capitalizaciones porque LOWER() de la
     * base puede no plegar letras no ASCII según su configuración regional.
     *
     * @var array<string, string>
     */
    private const EQUIVALENCIAS_SIN_ACENTO = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
    ];

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

        $descripcion = trim((string) ($filtros['descripcion'] ?? ''));

        return $query
            ->when(filled($filtros['numero'] ?? null),
                fn (Builder $q) => $q->where('id_incidencia', (int) $filtros['numero']))
            ->when($descripcion !== '', fn (Builder $q) => $this->buscarDescripcion($q, $descripcion))
            ->whereIn('id_estado_incidencia', $estados);
    }

    /**
     * Búsqueda por texto en la descripción que no distingue mayúsculas de
     * minúsculas ni vocales con o sin acento ("Pérdida" == "perdida").
     *
     * Se normaliza igual el texto buscado (PHP) y la columna (SQL) con
     * REPLACE anidados + LOWER, que funcionan igual en PostgreSQL y SQLite
     * sin depender de la extensión `unaccent` ni de la configuración regional
     * de la base. La ñ se conserva: es otra letra, no una vocal acentuada.
     */
    private function buscarDescripcion(Builder $query, string $texto): Builder
    {
        $columna = $this->getTable().'.descripcion';

        foreach (self::EQUIVALENCIAS_SIN_ACENTO as $conAcento => $sinAcento) {
            $columna = "REPLACE({$columna}, '{$conAcento}', '{$sinAcento}')";
        }

        $texto = mb_strtolower(strtr($texto, self::EQUIVALENCIAS_SIN_ACENTO));

        return $query->whereRaw("LOWER({$columna}) LIKE ?", ['%'.$texto.'%']);
    }
}
