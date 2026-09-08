@extends('layouts.app')

@section('title', 'Mis incidencias')

@section('content')
    <nav aria-label="ruta" class="mb-3">
        <a href="{{ route('panel.socio') }}" class="volver-link">&larr; Volver al panel</a>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="page-title h4 mb-0">Mis incidencias</h1>
            <p class="text-muted mb-0">Reclamos que usted presentó.</p>
        </div>
        <a href="{{ route('incidencias.create') }}" class="btn btn-trebol" data-conectividad>+ Nueva incidencia</a>
    </div>

    @if (session('ok'))
        <div class="alert alert-success" role="alert">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    @if ($procesando)
        <div class="alert alert-asignacion d-flex align-items-center gap-2" role="status">
            <span class="spinner" aria-hidden="true"></span>
            Simulación en curso: el club está gestionando una de sus incidencias
            (asignación de responsable y resolución). La lista se actualiza sola.
        </div>
    @endif

    @include('incidencias.partials.filtros', ['action' => route('incidencias.index'), 'estados' => $estados])

    @php($hayFiltros = request()->hasAny(['numero', 'descripcion', 'estado']))

    @if ($incidencias->isEmpty())
        <div class="panel text-center py-5">
            <div class="empty-icon" aria-hidden="true">&#128203;</div>
            @if ($hayFiltros)
                <h2 class="h6 mb-1">Sin resultados</h2>
                <p class="text-muted mb-3">Ninguna de sus incidencias coincide con los filtros aplicados.</p>
                <a href="{{ route('incidencias.index') }}" class="btn btn-outline-trebol">Quitar filtros</a>
            @else
                <h2 class="h6 mb-1">Todavía no tiene incidencias registradas</h2>
                <p class="text-muted mb-3">Cuando registre un reclamo, aparecerá en esta lista.</p>
                <a href="{{ route('incidencias.create') }}" class="btn btn-trebol" data-conectividad>Registrar una incidencia</a>
            @endif
        </div>
    @else
        {{-- ---------- Tabla (escritorio / tablet ancho) ---------- --}}
        <div class="panel d-none d-lg-block">
            <div>
                <table class="table table-incidencias align-middle mb-0">
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Tipo</th>
                            <th>Ubicación</th>
                            <th>Fecha del hecho</th>
                            <th>Descripción</th>
                            <th>Criticidad</th>
                            <th>Estado / Responsable</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incidencias as $incidencia)
                            @php($resp = $incidencia->responsables->first())
                            <tr>
                                <td class="fw-semibold">#{{ $incidencia->id_incidencia }}</td>
                                <td>{{ $incidencia->tipo->nombre }}</td>
                                <td>{{ $incidencia->ubicacion->nombre }}</td>
                                <td class="text-nowrap">{{ $incidencia->fecha_hora_evento->format('d/m/Y H:i') }}</td>
                                <td class="celda-descripcion">{{ $incidencia->descripcion }}</td>
                                <td>
                                    <span class="badge crit-badge crit-{{ Str::slug($incidencia->criticidad?->nombre ?? 'Normal') }}">
                                        {{ $incidencia->criticidad?->nombre ?? 'Normal' }}
                                    </span>
                                </td>
                                <td>
                                    @include('incidencias.partials.estado-badge', ['nombre' => $incidencia->estado->nombre])
                                    <div class="mini-responsable">
                                        @if ($resp)
                                            {{ $resp->usuario->apellido_nombres ?? '—' }}
                                        @elseif ($incidencia->estado->nombre === 'Cancelada')
                                            &mdash;
                                        @else
                                            Responsable pendiente de asignación
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end">@include('incidencias.partials.acciones', ['incidencia' => $incidencia])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ---------- Tarjetas (celular / tablet angosto) ---------- --}}
        <div class="d-lg-none incidencia-cards">
            @foreach ($incidencias as $incidencia)
                @php($resp = $incidencia->responsables->first())
                <div class="panel incidencia-card">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <span class="fw-semibold">Incidencia #{{ $incidencia->id_incidencia }}</span>
                        @include('incidencias.partials.estado-badge', ['nombre' => $incidencia->estado->nombre])
                    </div>
                    <dl class="incidencia-card__datos mb-2">
                        <dt>Tipo</dt><dd>{{ $incidencia->tipo->nombre }}</dd>
                        <dt>Ubicación</dt><dd>{{ $incidencia->ubicacion->nombre }}</dd>
                        <dt>Fecha del hecho</dt><dd>{{ $incidencia->fecha_hora_evento->format('d/m/Y H:i') }}</dd>
                        <dt>Criticidad</dt>
                        <dd>
                            <span class="badge crit-badge crit-{{ Str::slug($incidencia->criticidad?->nombre ?? 'Normal') }}">
                                {{ $incidencia->criticidad?->nombre ?? 'Normal' }}
                            </span>
                        </dd>
                        <dt>Responsable</dt>
                        <dd>
                            @if ($resp)
                                {{ $resp->usuario->apellido_nombres ?? '—' }}
                            @elseif ($incidencia->estado->nombre === 'Cancelada')
                                &mdash;
                            @else
                                <span class="text-muted">Pendiente de asignación</span>
                            @endif
                        </dd>
                        <dt>Descripción</dt><dd>{{ $incidencia->descripcion }}</dd>
                    </dl>
                    <div class="text-end">@include('incidencias.partials.acciones', ['incidencia' => $incidencia])</div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ================= Modal: confirmación de acción ================= --}}
    <div class="modal fade" id="modalConfirmar" tabindex="-1" aria-labelledby="modalConfirmarTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h6" id="modalConfirmarTitulo">Confirmar</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p id="modalConfirmarPregunta" class="mb-3">¿Desea confirmar la acción?</p>
                    <dl class="datos-modal">
                        <dt>N.º</dt><dd id="mcNumero">—</dd>
                        <dt>Tipo</dt><dd id="mcTipo">—</dd>
                        <dt>Ubicación</dt><dd id="mcUbicacion">—</dd>
                        <dt>Fecha del hecho</dt><dd id="mcFechaEvento">—</dd>
                        <dt>Descripción</dt><dd id="mcDescripcion">—</dd>
                        <dt>Criticidad</dt><dd id="mcCriticidad">—</dd>
                        <dt>Estado actual</dt><dd id="mcEstado">—</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnDescartarModal" class="btn btn-outline-trebol" data-bs-dismiss="modal">No confirmar ahora</button>
                    <form id="formConfirmar" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" id="btnConfirmarAccion" class="btn btn-trebol">Confirmar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Modal: historial de estados ================= --}}
    <div class="modal fade" id="modalHistorial" tabindex="-1" aria-labelledby="modalHistorialTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h6" id="modalHistorialTitulo">Historial de la incidencia</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p id="historialEstado" class="text-muted small mb-3">Cargando…</p>
                    <ol id="historialLista" class="timeline"></ol>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-trebol" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    // ---------- Modal de confirmación (rellena datos + destino del form) ----------
    var modalConfirmar = document.getElementById('modalConfirmar');
    var formConfirmar = document.getElementById('formConfirmar');
    var btnConfirmar = document.getElementById('btnConfirmarAccion');
    var btnDescartar = document.getElementById('btnDescartarModal');

    function pintarConfirmacion(d) {
        document.getElementById('modalConfirmarTitulo').textContent = d.titulo || 'Confirmar';
        document.getElementById('modalConfirmarPregunta').textContent = d.pregunta || '¿Desea confirmar la acción?';
        document.getElementById('mcNumero').textContent = '#' + (d.numero || '');
        document.getElementById('mcTipo').textContent = d.tipo || '—';
        document.getElementById('mcUbicacion').textContent = d.ubicacion || '—';
        document.getElementById('mcFechaEvento').textContent = d.fechaEvento || '—';
        document.getElementById('mcDescripcion').textContent = d.descripcion || '—';
        document.getElementById('mcCriticidad').textContent = d.criticidad || '—';
        document.getElementById('mcEstado').textContent = d.estado || '—';
        formConfirmar.setAttribute('action', d.accion || '');
        btnConfirmar.textContent = d.confirmarLabel || 'Confirmar';
        btnConfirmar.className = 'btn ' + (d.confirmarClase || 'btn-trebol');
        btnDescartar.textContent = d.descartarLabel || 'No confirmar ahora';
    }

    if (modalConfirmar) {
        modalConfirmar.addEventListener('show.bs.modal', function (event) {
            var t = event.relatedTarget;
            if (!t) return;
            pintarConfirmacion({
                titulo: t.getAttribute('data-titulo'),
                pregunta: t.getAttribute('data-pregunta'),
                accion: t.getAttribute('data-accion'),
                confirmarLabel: t.getAttribute('data-confirmar-label'),
                confirmarClase: t.getAttribute('data-confirmar-clase'),
                descartarLabel: t.getAttribute('data-descartar-label'),
                numero: t.getAttribute('data-numero'),
                tipo: t.getAttribute('data-tipo'),
                ubicacion: t.getAttribute('data-ubicacion'),
                fechaEvento: t.getAttribute('data-fecha-evento'),
                descripcion: t.getAttribute('data-descripcion'),
                criticidad: t.getAttribute('data-criticidad'),
                estado: t.getAttribute('data-estado'),
            });
        });
    }

    // ---------- Modal de historial (carga por fetch) ----------
    var modalHistorial = document.getElementById('modalHistorial');
    if (modalHistorial) {
        modalHistorial.addEventListener('show.bs.modal', function (event) {
            var t = event.relatedTarget;
            if (!t) return;
            var url = t.getAttribute('data-historial-url');
            var numero = t.getAttribute('data-numero');
            var lista = document.getElementById('historialLista');
            var estadoTxt = document.getElementById('historialEstado');
            document.getElementById('modalHistorialTitulo').textContent = 'Historial · Incidencia #' + numero;
            lista.innerHTML = '';
            estadoTxt.textContent = 'Cargando…';

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var eventos = data.eventos || [];
                    if (!eventos.length) { estadoTxt.textContent = 'Sin movimientos registrados.'; return; }
                    estadoTxt.textContent = eventos.length + ' movimiento(s) registrado(s).';
                    eventos.forEach(function (ev) {
                        var li = document.createElement('li');
                        li.className = 'timeline__item';
                        var transicion = ev.desde ? (ev.desde + ' → ' + ev.hasta) : ('Alta → ' + ev.hasta);
                        li.innerHTML =
                            '<div class="timeline__dot"></div>' +
                            '<div class="timeline__body">' +
                              '<div class="timeline__head"><strong>' + transicion + '</strong>' +
                              '<span class="timeline__fecha">' + ev.fecha + '</span></div>' +
                              (ev.comentario ? '<div class="timeline__coment">' + ev.comentario + '</div>' : '') +
                              '<div class="timeline__user">Por: ' + ev.usuario + '</div>' +
                            '</div>';
                        lista.appendChild(li);
                    });
                })
                .catch(function () { estadoTxt.textContent = 'No se pudo cargar el historial.'; });
        });
    }

    // ---------- Tooltips de los botones-icono de acciones ----------
    document.querySelectorAll('[data-tooltip][title]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover', container: 'body' });
    });

    // ---------- Autorefresco mientras hay una simulación en curso ----------
    @if ($procesando)
    setTimeout(function () { window.location.reload(); }, 11000);
    @endif
})();
</script>
@endpush
