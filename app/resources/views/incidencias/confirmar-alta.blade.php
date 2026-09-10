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
                        Revise los datos de la incidencia N.º {{ $incidencia->id_incidencia }} antes de enviarla.
                    </p>
                </div>
            </div>

            <dl class="datos-modal my-3">
                <dt>N.º</dt><dd>#{{ $incidencia->id_incidencia }}</dd>
                <dt>Tipo</dt><dd>{{ $incidencia->tipo->nombre }}</dd>
                <dt>Ubicación</dt><dd>{{ $incidencia->ubicacion->nombre }}</dd>
                <dt>Fecha del hecho</dt><dd>{{ $incidencia->fecha_hora_evento->format('d/m/Y H:i') }}</dd>
                <dt>Descripción</dt><dd>{{ $incidencia->descripcion }}</dd>
            </dl>

            <p class="mb-3">
                Si los datos son correctos, confirme el envío. Si necesita corregir algo,
                puede volver a editar antes de confirmar.
            </p>

            <div class="confirm-alta-actions">
                <a href="{{ route('incidencias.edit', $incidencia) }}" class="btn btn-outline-trebol">Volver a editar</a>
                <form method="POST" action="{{ route('incidencias.confirmar', $incidencia) }}">
                    @csrf
                    <button type="submit" class="btn btn-trebol">Confirmar envío</button>
                </form>
            </div>
        </div>
    </div>
@endsection
