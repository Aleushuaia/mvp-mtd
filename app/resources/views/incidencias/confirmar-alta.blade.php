@extends('layouts.app')

@section('title', 'Confirmar envío de la incidencia')

@section('content')
    <div class="confirm-alta-wrap">
        <div class="panel confirm-alta-card">
            <div class="confirm-alta-head">
                <span class="confirm-alta-icon" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
                <div>
                    <h1 class="h5 mb-1">Confirmar envío de la incidencia</h1>
                    <p class="text-muted small mb-0">
                        La incidencia N.º {{ $incidencia->id_incidencia }} se registró como «Pendiente».
                    </p>
                </div>
            </div>

            <dl class="datos-modal my-3">
                <dt>N.º</dt><dd>#{{ $incidencia->id_incidencia }}</dd>
                <dt>Tipo</dt><dd>{{ $incidencia->tipo->nombre }}</dd>
                <dt>Ubicación</dt><dd>{{ $incidencia->ubicacion->nombre }}</dd>
                <dt>Fecha del hecho</dt><dd>{{ $incidencia->fecha_hora_evento->format('d/m/Y H:i') }}</dd>
                <dt>Descripción</dt><dd>{{ $incidencia->descripcion }}</dd>
                <dt>Criticidad</dt>
                <dd>
                    <span class="badge crit-badge crit-{{ Str::slug($incidencia->criticidad?->nombre ?? 'Normal') }}">
                        {{ $incidencia->criticidad?->nombre ?? 'Normal' }}
                    </span>
                </dd>
                <dt>Estado actual</dt>
                <dd>@include('incidencias.partials.estado-badge', ['nombre' => $incidencia->estado->nombre])</dd>
            </dl>

            <p class="mb-3">
                ¿Desea confirmar el envío ahora? La incidencia pasará al estado
                <strong>«Confirmada»</strong>. Si prefiere, puede dejarla pendiente y confirmarla más tarde
                desde «Mis incidencias».
            </p>

            <div class="confirm-alta-actions">
                <a href="{{ route('incidencias.index') }}" class="btn btn-outline-trebol">No confirmar ahora</a>
                <form method="POST" action="{{ route('incidencias.confirmar', $incidencia) }}">
                    @csrf
                    <button type="submit" class="btn btn-trebol">Confirmar envío</button>
                </form>
            </div>
        </div>
    </div>
@endsection
