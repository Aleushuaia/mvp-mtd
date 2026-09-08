@extends('layouts.app')

@section('title', 'Incidencia #'.$incidencia->id_incidencia)

@section('content')
    <nav aria-label="ruta" class="mb-3">
        <a href="{{ route('incidencias.index') }}" class="volver-link">&larr; Volver a mis incidencias</a>
    </nav>

    @if (session('ok'))
        <div class="alert alert-success" role="alert">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    @if ($procesando)
        <div class="alert alert-asignacion d-flex align-items-center gap-2" role="status">
            <span class="spinner" aria-hidden="true"></span>
            Simulación en curso: el club está gestionando esta incidencia. La página se actualiza sola.
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title h4 mb-0">Incidencia #{{ $incidencia->id_incidencia }}</h1>
        @include('incidencias.partials.estado-badge', ['nombre' => $incidencia->estado->nombre])
    </div>

    <div class="row g-3 g-lg-4">
        <div class="col-12 col-lg-7">
            <div class="panel">
                <h2 class="section-title">Datos de la incidencia</h2>
                <dl class="datos-detalle">
                    <dt>Tipo</dt><dd>{{ $incidencia->tipo->nombre }}</dd>
                    <dt>Ubicación</dt><dd>{{ $incidencia->ubicacion->nombre }}</dd>
                    <dt>Descripción</dt><dd>{{ $incidencia->descripcion }}</dd>
                    <dt>Criticidad</dt>
                    <dd>
                        <span class="badge crit-badge crit-{{ Str::slug($incidencia->criticidad?->nombre ?? 'Normal') }}">
                            {{ $incidencia->criticidad?->nombre ?? 'Normal' }}
                        </span>
                    </dd>
                    <dt>Estado actual</dt>
                    <dd>@include('incidencias.partials.estado-badge', ['nombre' => $incidencia->estado->nombre])</dd>
                    <dt>Registrada por</dt><dd>{{ $incidencia->usuarioAlta->apellido_nombres ?? '—' }}</dd>
                    <dt>Fecha del hecho</dt><dd>{{ $incidencia->fecha_hora_evento->format('d/m/Y H:i') }}</dd>
                    <dt>Fecha de alta</dt><dd>{{ $incidencia->fecha_hora_alta->format('d/m/Y H:i') }}</dd>
                </dl>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    @if ($incidencia->estaPendiente())
                        <form method="POST" action="{{ route('incidencias.confirmar', $incidencia) }}"
                              onsubmit="return confirm('¿Confirmar el envío de la incidencia N.º {{ $incidencia->id_incidencia }}?');">
                            @csrf
                            <button class="btn btn-trebol">Confirmar envío</button>
                        </form>
                    @endif
                    @if ($incidencia->sePuedeCancelar())
                        <form method="POST" action="{{ route('incidencias.cancelar', $incidencia) }}"
                              onsubmit="return confirm('¿Cambiar la incidencia N.º {{ $incidencia->id_incidencia }} a Cancelada? Esta acción no se puede revertir.');">
                            @csrf
                            <button class="btn btn-danger">Cambiar a Cancelada</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            @php($resp = $incidencia->responsables->first())
            <div class="panel mb-3 mb-lg-4">
                <h2 class="section-title">Responsable de la solución</h2>
                @if ($resp)
                    <div class="responsable-box">
                        <div class="responsable-avatar" aria-hidden="true">
                            {{ Str::upper(Str::substr($resp->usuario->apellido_nombres ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $resp->usuario->apellido_nombres ?? '—' }}</div>
                            <div class="text-muted small">
                                Asignado el
                                {{ \Illuminate\Support\Carbon::parse($resp->pivot->fecha_asignacion)->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>
                @elseif ($incidencia->estado->nombre === 'Cancelada')
                    <p class="responsable-pendiente mb-0">La incidencia fue cancelada; no se asignó responsable.</p>
                @else
                    <p class="responsable-pendiente mb-0">Responsable pendiente de asignación</p>
                @endif
            </div>

            <div class="panel">
                <h2 class="section-title">Historial de estados</h2>
                <ol class="timeline">
                    @forelse ($incidencia->historial as $evento)
                        <li class="timeline__item">
                            <div class="timeline__dot"></div>
                            <div class="timeline__body">
                                <div class="timeline__head">
                                    <strong>
                                        {{ $evento->estadoAnterior?->nombre ?? 'Alta' }} &rarr; {{ $evento->estadoNuevo?->nombre }}
                                    </strong>
                                    <span class="timeline__fecha">{{ $evento->fecha_hora->format('d/m/Y H:i') }}</span>
                                </div>
                                @if ($evento->comentario)
                                    <div class="timeline__coment">{{ $evento->comentario }}</div>
                                @endif
                                <div class="timeline__user">Por: {{ $evento->usuario->apellido_nombres ?? 'Sistema' }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="text-muted">Sin movimientos registrados.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    @if ($procesando)
    setTimeout(function () { window.location.reload(); }, 11000);
    @endif
</script>
@endpush
