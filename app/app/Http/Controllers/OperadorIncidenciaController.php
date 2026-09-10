<?php

namespace App\Http\Controllers;

use App\Models\CriticidadIncidencia;
use App\Models\EstadoIncidencia;
use App\Models\HistorialEstadoIncidencia;
use App\Models\Incidencia;
use App\Models\Responsable;
use App\Models\Usuario;
use App\Simulacion\Simulador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Panel del Operador (rol "Operador").
 *
 * El operador ve TODAS las incidencias, las filtra por N.º / descripción /
 * estado, y sobre las que están En proceso puede:
 *  - cambiar la criticidad,
 *  - asignar uno o varios responsables.
 *
 * Todo cambio queda registrado en `historial_estado_incidencia` con el
 * operador como autor.
 */
class OperadorIncidenciaController extends Controller
{
    public function __construct(private readonly Simulador $simulador) {}

    public function index(Request $request): View
    {
        $this->operadorId();

        // "El sistema en uso": procesa asignaciones y resoluciones automáticas maduras.
        $this->simulador->ejecutarTodas();

        $incidencias = Incidencia::with([
            'tipo', 'ubicacion', 'estado', 'criticidad', 'usuario', 'responsables.usuario',
        ])
            ->filtrar($request->only(['numero', 'descripcion', 'estado']))
            ->orderByDesc('fecha_hora_alta')
            ->orderByDesc('id_incidencia')
            ->get();

        return view('operador.incidencias.index', [
            'incidencias' => $incidencias,
            'estados' => EstadoIncidencia::orderBy('id_estado')->get(),
        ]);
    }

    public function edit(Incidencia $incidencia): View
    {
        $this->operadorId();

        $incidencia->load([
            'tipo', 'ubicacion', 'estado', 'criticidad', 'usuario', 'usuarioAlta',
            'responsables.usuario',
            'historial.estadoAnterior', 'historial.estadoNuevo', 'historial.usuario',
        ]);

        return view('operador.incidencias.edit', [
            'incidencia' => $incidencia,
            'criticidades' => CriticidadIncidencia::orderBy('id')->get(),
            'responsables' => Responsable::where('disponible', true)
                ->with('usuario')
                ->get()
                ->sortBy(fn (Responsable $r) => $r->usuario->apellido_nombres ?? '')
                ->values(),
            'asignados' => $incidencia->responsables->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Incidencia $incidencia): RedirectResponse
    {
        $operadorUsuarioId = $this->operadorId();

        abort_unless(
            $incidencia->esGestionablePorOperador(),
            403,
            'Sólo se puede editar mientras la incidencia está En proceso.'
        );

        $datos = $request->validate([
            'id_criticidad' => ['required', 'integer', 'exists:criticidades_incidencia,id'],
            'responsables' => ['array'],
            'responsables.*' => ['integer', 'exists:responsables,id'],
        ], [
            'id_criticidad.required' => 'Seleccione la criticidad.',
        ]);

        $seleccion = collect($datos['responsables'] ?? [])->map(fn ($id) => (int) $id)->unique();

        DB::transaction(function () use ($incidencia, $datos, $seleccion, $operadorUsuarioId) {
            $cambios = [];

            // ---- Criticidad ----
            if ((int) $incidencia->id_criticidad !== (int) $datos['id_criticidad']) {
                $antes = $incidencia->criticidad?->nombre ?? 'Normal';
                $despues = CriticidadIncidencia::find($datos['id_criticidad'])?->nombre ?? '—';
                $incidencia->update(['id_criticidad' => $datos['id_criticidad']]);
                $cambios[] = "Criticidad: {$antes} → {$despues}";
            }

            // ---- Responsables ----
            $actuales = $incidencia->responsables->pluck('id');
            $aAgregar = $seleccion->diff($actuales);
            $aQuitar = $actuales->diff($seleccion);

            if ($aQuitar->isNotEmpty()) {
                $incidencia->responsables()->detach($aQuitar->all());
            }
            foreach ($aAgregar as $idResponsable) {
                $incidencia->responsables()->attach($idResponsable, ['fecha_asignacion' => now()]);
            }

            if ($aAgregar->isNotEmpty() || $aQuitar->isNotEmpty()) {
                $nombres = Responsable::whereIn('id', $seleccion)
                    ->with('usuario')
                    ->get()
                    ->map(fn (Responsable $r) => $r->usuario->apellido_nombres ?? ('#'.$r->id))
                    ->sort()
                    ->implode(' · ');
                $cambios[] = 'Responsables: '.($nombres !== '' ? $nombres : 'sin asignar');
            }

            if ($cambios === []) {
                return;
            }

            $estadoAnterior = (int) $incidencia->id_estado_incidencia;

            HistorialEstadoIncidencia::create([
                'id_incidencia' => $incidencia->id_incidencia,
                'id_estado_anterior' => $estadoAnterior,
                'id_estado_nuevo' => $estadoAnterior,
                'id_usuario' => $operadorUsuarioId,
                'fecha_hora' => now(),
                'comentario' => 'Edición del operador. '.implode(' | ', $cambios),
            ]);
        });

        return redirect()
            ->route('operador.incidencias.edit', $incidencia)
            ->with('ok', 'Incidencia N.º '.$incidencia->id_incidencia.' actualizada.');
    }

    /** El operador puede cancelar una incidencia mientras está En proceso. */
    public function cancelar(Request $request, Incidencia $incidencia): RedirectResponse
    {
        $operadorUsuarioId = $this->operadorId();

        $datos = $request->validate([
            'comentario_cancelacion' => ['required', 'string', 'max:200'],
        ], [
            'comentario_cancelacion.required' => 'Ingrese el motivo de la cancelación.',
            'comentario_cancelacion.max' => 'El motivo de la cancelación admite hasta 200 caracteres.',
        ]);

        DB::transaction(function () use ($incidencia, $operadorUsuarioId, $datos) {
            $actual = Incidencia::whereKey($incidencia->getKey())->lockForUpdate()->firstOrFail();

            abort_unless(
                $actual->estaEnProceso(),
                422,
                'Solo se puede cancelar una incidencia que está En proceso.'
            );

            $actual->update(['id_estado_incidencia' => Incidencia::ESTADO_CANCELADA]);

            HistorialEstadoIncidencia::create([
                'id_incidencia' => $actual->id_incidencia,
                'id_estado_anterior' => Incidencia::ESTADO_EN_PROCESO,
                'id_estado_nuevo' => Incidencia::ESTADO_CANCELADA,
                'id_usuario' => $operadorUsuarioId,
                'fecha_hora' => now(),
                'comentario' => 'Cancelada por el operador. Motivo: '.trim($datos['comentario_cancelacion']),
            ]);
        });

        return redirect()
            ->route('operador.incidencias.index')
            ->with('ok', 'Incidencia N.º '.$incidencia->id_incidencia.' cancelada.');
    }

    /** El operador puede resolver una incidencia mientras está En proceso. */
    public function resolver(Request $request, Incidencia $incidencia): RedirectResponse
    {
        $operadorUsuarioId = $this->operadorId();

        $datos = $request->validate([
            'comentario_resolucion' => ['nullable', 'string', 'max:200'],
        ], [
            'comentario_resolucion.max' => 'El mensaje de resolución admite hasta 200 caracteres.',
        ]);

        DB::transaction(function () use ($incidencia, $operadorUsuarioId, $datos) {
            $actual = Incidencia::whereKey($incidencia->getKey())->lockForUpdate()->firstOrFail();

            abort_unless(
                $actual->estaEnProceso(),
                422,
                'Solo se puede resolver una incidencia que está En proceso.'
            );

            $mensaje = trim((string) ($datos['comentario_resolucion'] ?? ''));
            $actual->update(['id_estado_incidencia' => Incidencia::ESTADO_RESUELTA]);

            HistorialEstadoIncidencia::create([
                'id_incidencia' => $actual->id_incidencia,
                'id_estado_anterior' => Incidencia::ESTADO_EN_PROCESO,
                'id_estado_nuevo' => Incidencia::ESTADO_RESUELTA,
                'id_usuario' => $operadorUsuarioId,
                'fecha_hora' => now(),
                'comentario' => $mensaje === ''
                    ? 'Resuelta por el operador.'
                    : 'Resuelta por el operador. Mensaje: '.$mensaje,
            ]);
        });

        return redirect()
            ->route('operador.incidencias.index')
            ->with('ok', 'Incidencia N.º '.$incidencia->id_incidencia.' marcada como resuelta.');
    }

    /** Esta sección es exclusiva del rol Operador. */
    private function operadorId(): int
    {
        $usuario = session('usuario');

        abort_if($usuario === null, 401);
        abort_unless((int) $usuario['id_rol'] === Usuario::ROL_OPERADOR, 403, 'Sección disponible sólo para operadores.');

        return (int) $usuario['id'];
    }
}
