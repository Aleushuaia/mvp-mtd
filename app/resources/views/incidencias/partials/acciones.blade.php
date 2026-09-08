{{--
    Acciones de una incidencia como botones-icono (sin texto, con tooltip).
    Compartido entre la tabla de escritorio y las tarjetas móviles.
    Espera: $incidencia
--}}
@php
    $criticidadNombre = $incidencia->criticidad?->nombre ?? 'Normal';
    $datosComunes = [
        'data-numero' => $incidencia->id_incidencia,
        'data-tipo' => $incidencia->tipo->nombre,
        'data-ubicacion' => $incidencia->ubicacion->nombre,
        'data-fecha-evento' => $incidencia->fecha_hora_evento->format('d/m/Y H:i'),
        'data-descripcion' => $incidencia->descripcion,
        'data-criticidad' => $criticidadNombre,
        'data-estado' => $incidencia->estado->nombre,
    ];
@endphp

<div class="acciones-iconos">
    <a href="{{ route('incidencias.show', $incidencia) }}"
       class="btn-icono" data-tooltip title="Ver detalle" aria-label="Ver detalle de la incidencia">
        <i class="bi bi-eye" aria-hidden="true"></i>
    </a>

    <button type="button" class="btn-icono" data-tooltip title="Ver historial"
            aria-label="Ver historial de estados"
            data-bs-toggle="modal" data-bs-target="#modalHistorial"
            data-historial-url="{{ route('incidencias.historial', $incidencia) }}"
            data-numero="{{ $incidencia->id_incidencia }}">
        <i class="bi bi-clock-history" aria-hidden="true"></i>
    </button>

    @if ($incidencia->estaPendiente())
        <button type="button" class="btn-icono btn-icono--ok" data-tooltip title="Confirmar envío"
                aria-label="Confirmar el envío de la incidencia"
                data-bs-toggle="modal" data-bs-target="#modalConfirmar"
                @foreach ($datosComunes as $k => $v) {{ $k }}="{{ $v }}" @endforeach
                data-accion="{{ route('incidencias.confirmar', $incidencia) }}"
                data-titulo="Confirmar envío de la incidencia"
                data-pregunta="¿Desea confirmar el envío de la incidencia N.º {{ $incidencia->id_incidencia }}? Pasará al estado «Confirmada»."
                data-confirmar-label="Confirmar envío"
                data-confirmar-clase="btn-trebol"
                data-descartar-label="No confirmar ahora">
            <i class="bi bi-send-check" aria-hidden="true"></i>
        </button>
    @endif

    @if ($incidencia->sePuedeCancelar())
        <button type="button" class="btn-icono btn-icono--danger" data-tooltip title="Cambiar a Cancelada"
                aria-label="Cambiar la incidencia al estado Cancelada"
                data-bs-toggle="modal" data-bs-target="#modalConfirmar"
                @foreach ($datosComunes as $k => $v) {{ $k }}="{{ $v }}" @endforeach
                data-accion="{{ route('incidencias.cancelar', $incidencia) }}"
                data-titulo="Cancelar la incidencia"
                data-pregunta="¿Desea cambiar la incidencia N.º {{ $incidencia->id_incidencia }} al estado «Cancelada»? Esta acción no podrá revertirse."
                data-confirmar-label="Cambiar a Cancelada"
                data-confirmar-clase="btn-danger"
                data-descartar-label="Volver">
            <i class="bi bi-x-circle" aria-hidden="true"></i>
        </button>
    @endif
</div>
