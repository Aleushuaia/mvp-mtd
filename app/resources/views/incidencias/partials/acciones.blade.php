{{--
    Acciones de una incidencia como botones-icono (sin texto, con tooltip).
    Compartido entre la tabla de escritorio y las tarjetas móviles.
    Espera: $incidencia
--}}
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

    @if ($incidencia->estaBorrador())
        <a href="{{ route('incidencias.confirmar-alta', $incidencia) }}"
           class="btn-icono btn-icono--ok" data-tooltip title="Confirmar envío"
           aria-label="Confirmar el envío de la incidencia">
            <i class="bi bi-send-check" aria-hidden="true"></i>
        </a>
    @endif

    @if ($incidencia->estaEnProceso())
        <button type="button" class="btn-icono btn-icono--danger" data-tooltip title="Cancelar incidencia"
                aria-label="Cancelar la incidencia" data-bs-toggle="modal" data-bs-target="#modalCancelarIncidencia"
                data-cancelar-url="{{ route('incidencias.cancelar', $incidencia) }}"
                data-numero="{{ $incidencia->id_incidencia }}">
            <i class="bi bi-x-circle" aria-hidden="true"></i>
        </button>
    @endif

</div>
