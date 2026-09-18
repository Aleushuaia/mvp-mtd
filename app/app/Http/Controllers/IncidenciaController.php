<?php

namespace App\Http\Controllers;

use App\Models\EstadoIncidencia;
use App\Models\HistorialEstadoIncidencia;
use App\Models\Incidencia;
use App\Models\TipoIncidencia;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CRUD de incidencias para el rol Socio (RF02).
 *
 * Circuito:
 *  1. El socio completa 3 campos (tipo, ubicación, descripción) — todos obligatorios.
 *  2. Al guardar, la incidencia queda en estado BORRADOR, criticidad NORMAL.
 *  3. Inmediatamente se muestra un modal con los datos cargados que pide
 *     confirmar el envío; si confirma, la incidencia pasa a PENDIENTE.
 *  4. El sistema simula la revisión, asignación y resolución posteriores.
 *
 * Todo cambio de estado (incluida el alta) queda registrado en
 * `historial_estado_incidencia` con estado anterior/nuevo, usuario,
 * fecha/hora y un comentario.
 *
 *  5. Cada etapa automática se evalúa luego de diez segundos.
 *
 * Datos que se completan automáticamente (el socio no los ingresa):
 *  - id_usuario / id_usuario_alta = socio en sesión
 *  - id_estado_incidencia = BORRADOR
 *  - id_criticidad = NORMAL
 *  - fecha_hora_alta = ahora
 * El socio sí declara `fecha_hora_evento` (fecha y hora del hecho) en el
 * formulario.
 */
class IncidenciaController extends Controller
{
    /** "Mis incidencias": sólo las que presentó el propio socio. */
    public function index(Request $request): View
    {
        $socioId = $this->socioId();

        $estados = EstadoIncidencia::orderBy('id_estado')->get();

        $filtros = $request->only(['numero', 'descripcion', 'estado']);
        // "Mis incidencias" abre con "Todos" tildado: sin filtro explícito
        // de estado se muestran las incidencias en cualquier estado.
        if (! $request->has('estado')) {
            $filtros['estado'] = $estados->pluck('id_estado')->all();
        }

        $incidencias = Incidencia::with(['tipo', 'ubicacion', 'estado', 'criticidad', 'responsables.usuario'])
            ->where('id_usuario', $socioId)
            ->filtrar($filtros)
            ->orderByDesc('fecha_hora_alta')
            ->orderByDesc('id_incidencia')
            ->get();

        $totalIncidencias = Incidencia::where('id_usuario', $socioId)->count();

        return view('incidencias.index', compact('incidencias', 'estados', 'totalIncidencias'));
    }

    /** Formulario de alta. */
    public function create(): View
    {
        return view('incidencias.create', [
            'tipos' => TipoIncidencia::orderBy('nombre')->get(),
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(),
        ]);
    }

    /** Alta de incidencia -> estado BORRADOR + registro en historial. */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $datos = $this->validarDatos($request);

        $socioId = $this->socioId();
        $ahora = now();
        $fechaEvento = Carbon::parse($datos['fecha_hora_evento']);

        $incidencia = DB::transaction(function () use ($datos, $socioId, $ahora, $fechaEvento) {
            $incidencia = Incidencia::create([
                'id_usuario_alta' => $socioId,
                'id_usuario' => $socioId,
                'id_tipo_incidencia' => $datos['id_tipo_incidencia'],
                'id_ubicacion' => $datos['id_ubicacion'],
                'id_estado_incidencia' => Incidencia::ESTADO_BORRADOR,
                'id_criticidad' => Incidencia::CRITICIDAD_NORMAL,
                'descripcion' => $datos['descripcion'],
                'fecha_hora_evento' => $fechaEvento,
                'fecha_hora_alta' => $ahora,
            ]);

            $this->registrarHistorial(
                $incidencia,
                null,
                Incidencia::ESTADO_BORRADOR,
                $socioId,
                'Alta de la incidencia. Estado inicial: Borrador.',
                $ahora,
            );

            return $incidencia;
        });

        // Paso intermedio OBLIGATORIO: pantalla de confirmación antes de la grilla.
        $destino = route('incidencias.confirmar-alta', $incidencia);

        // Envío por fetch (borrador en el navegador): responder JSON con el destino.
        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'id_incidencia' => $incidencia->id_incidencia,
                'redirect' => $destino,
            ]);
        }

        return redirect($destino);
    }

    /**
     * Pantalla que aparece inmediatamente después de "Guardar incidencia":
     * la incidencia ya quedó como BORRADOR y aquí el socio decide si la
     * confirma (pasa a PENDIENTE) o vuelve a editar los datos. Recién
     * después continúa a "Mis incidencias".
     */
    public function confirmarAlta(Incidencia $incidencia): View|RedirectResponse
    {
        $this->autorizar($incidencia);

        if (! $incidencia->estaBorrador()) {
            return redirect()->route('incidencias.index');
        }

        $incidencia->load(['tipo', 'ubicacion']);

        return view('incidencias.confirmar-alta', compact('incidencia'));
    }

    /** Corrección de los datos antes de confirmar el envío. */
    public function edit(Incidencia $incidencia): View|RedirectResponse
    {
        $this->autorizar($incidencia);

        if (! $incidencia->estaBorrador()) {
            return redirect()->route('incidencias.index')
                ->with('error', 'Solo se pueden editar incidencias en borrador.');
        }

        return view('incidencias.create', [
            'incidencia' => $incidencia,
            'tipos' => TipoIncidencia::orderBy('nombre')->get(),
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Incidencia $incidencia): RedirectResponse|JsonResponse
    {
        $this->autorizar($incidencia);
        $datos = $this->validarDatos($request);

        DB::transaction(function () use ($incidencia, $datos) {
            $actual = Incidencia::whereKey($incidencia->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($actual->estaBorrador(), 422, 'Solo se pueden editar incidencias en borrador.');

            $actual->update($datos);
        });

        $destino = route('incidencias.confirmar-alta', $incidencia);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'id_incidencia' => $incidencia->id_incidencia,
                'redirect' => $destino,
            ]);
        }

        return redirect($destino);
    }

    /** Detalle de una incidencia propia, con su historial completo. */
    public function show(Incidencia $incidencia): View
    {
        $this->autorizar($incidencia);

        $incidencia->load([
            'tipo', 'ubicacion', 'estado', 'criticidad', 'usuarioAlta',
            'responsables.usuario',
            'historial.estadoAnterior', 'historial.estadoNuevo', 'historial.usuario',
        ]);

        return view('incidencias.show', compact('incidencia'));
    }

    /** Historial de cambios de estado (para el modal "Ver historial"). */
    public function historial(Incidencia $incidencia): JsonResponse
    {
        $this->autorizar($incidencia);

        $eventos = $incidencia->historial()
            ->with(['estadoAnterior', 'estadoNuevo', 'usuario'])
            ->get()
            ->map(fn (HistorialEstadoIncidencia $h) => [
                'fecha' => $h->fecha_hora->format('d/m/Y H:i'),
                'desde' => $h->estadoAnterior?->nombre,
                'hasta' => $h->estadoNuevo?->nombre,
                'usuario' => $h->usuario?->apellido_nombres ?? 'Sistema',
                'comentario' => $h->comentario,
            ]);

        return response()->json([
            'numero' => $incidencia->id_incidencia,
            'eventos' => $eventos,
        ]);
    }

    /** Borrador -> Pendiente al confirmar el envío. */
    public function confirmar(Incidencia $incidencia): RedirectResponse
    {
        $this->autorizar($incidencia);

        if (! $incidencia->estaBorrador()) {
            return redirect()->route('incidencias.index')
                ->with('error', 'La incidencia N.º '.$incidencia->id_incidencia.' ya no está en Borrador.');
        }

        $this->cambiarEstado(
            $incidencia,
            Incidencia::ESTADO_PENDIENTE,
            'El socio confirmó el envío de la incidencia.',
        );

        return redirect()->route('incidencias.index')
            ->with('ok', 'Incidencia N.º '.$incidencia->id_incidencia.' enviada para revisión.');
    }

    /** El socio puede cancelar una incidencia propia mientras está En proceso. */
    public function cancelar(Request $request, Incidencia $incidencia): RedirectResponse
    {
        $socioId = $this->socioId();
        $this->autorizar($incidencia);

        $datos = $request->validate([
            'comentario_cancelacion' => ['required', 'string', 'max:200'],
        ], [
            'comentario_cancelacion.required' => 'Ingrese el motivo de la cancelación.',
            'comentario_cancelacion.max' => 'El motivo de la cancelación admite hasta 200 caracteres.',
        ]);

        DB::transaction(function () use ($incidencia, $socioId, $datos) {
            $actual = Incidencia::whereKey($incidencia->getKey())->lockForUpdate()->firstOrFail();

            abort_unless(
                $actual->estaEnProceso(),
                422,
                'Solo se puede cancelar una incidencia que está En proceso.'
            );

            $actual->update(['id_estado_incidencia' => Incidencia::ESTADO_CANCELADA]);

            $this->registrarHistorial(
                $actual,
                Incidencia::ESTADO_EN_PROCESO,
                Incidencia::ESTADO_CANCELADA,
                $socioId,
                'Cancelada por el socio. Motivo: '.trim($datos['comentario_cancelacion']),
                now(),
            );
        });

        return redirect()
            ->route('incidencias.index')
            ->with('ok', 'Incidencia N.º '.$incidencia->id_incidencia.' cancelada.');
    }

    // -----------------------------------------------------------------

    /** @return array{id_tipo_incidencia: mixed, id_ubicacion: mixed, descripcion: string, fecha_hora_evento: string} */
    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'id_tipo_incidencia' => ['required', 'integer', 'exists:tipos_incidencia,id'],
            'id_ubicacion' => ['required', 'integer', 'exists:ubicaciones,id'],
            'descripcion' => ['required', 'string', 'max:140'],
            'fecha_hora_evento' => ['required', 'date', 'before_or_equal:now'],
        ], [
            'id_tipo_incidencia.required' => 'Seleccione el tipo de incidencia.',
            'id_ubicacion.required' => 'Seleccione la ubicación.',
            'descripcion.required' => 'Ingrese una breve descripción.',
            'descripcion.max' => 'La descripción admite hasta 140 caracteres.',
            'fecha_hora_evento.required' => 'Indique la fecha y hora en que ocurrió el hecho.',
            'fecha_hora_evento.before_or_equal' => 'La fecha y hora del hecho no puede ser futura.',
        ]);
    }

    private function cambiarEstado(Incidencia $incidencia, int $nuevoEstado, string $comentario): void
    {
        $socioId = $this->socioId();
        $ahora = now();
        $estadoAnterior = (int) $incidencia->id_estado_incidencia;

        DB::transaction(function () use ($incidencia, $estadoAnterior, $nuevoEstado, $socioId, $comentario, $ahora) {
            $incidencia->update(['id_estado_incidencia' => $nuevoEstado]);

            $this->registrarHistorial($incidencia, $estadoAnterior, $nuevoEstado, $socioId, $comentario, $ahora);
        });
    }

    private function registrarHistorial(
        Incidencia $incidencia,
        ?int $estadoAnterior,
        int $estadoNuevo,
        int $usuarioId,
        string $comentario,
        \DateTimeInterface $fechaHora,
    ): void {
        HistorialEstadoIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_estado_anterior' => $estadoAnterior,
            'id_estado_nuevo' => $estadoNuevo,
            'id_usuario' => $usuarioId,
            'fecha_hora' => $fechaHora,
            'comentario' => $comentario,
        ]);
    }

    /** El circuito de esta etapa es sólo para socios y sobre incidencias propias. */
    private function autorizar(Incidencia $incidencia): void
    {
        abort_unless((int) $incidencia->id_usuario === $this->socioId(), 403, 'La incidencia no pertenece al socio.');
    }

    private function socioId(): int
    {
        $usuario = session('usuario');

        abort_if($usuario === null, 401);
        abort_unless((int) $usuario['id_rol'] === Usuario::ROL_SOCIO, 403, 'Sección disponible sólo para socios.');

        return (int) $usuario['id'];
    }
}
