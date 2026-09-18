@extends('layouts.app')

@section('title', 'Mis incidencias')

@section('content')
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

    @include('incidencias.partials.filtros', ['action' => route('incidencias.index'), 'estados' => $estados, 'todosPorDefecto' => true])

    @php($hayFiltros = request()->hasAny(['numero', 'descripcion', 'estado']))

    @if ($incidencias->isEmpty())
        <div class="panel text-center py-5">
            <div class="empty-icon" aria-hidden="true">&#128203;</div>
            @if ($hayFiltros)
                <h2 class="h6 mb-1">Sin resultados</h2>
                <p class="text-muted mb-3">Ninguna de sus incidencias coincide con los filtros aplicados.</p>
                <a href="{{ route('incidencias.index') }}" class="btn btn-outline-trebol">Limpiar campos</a>
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
                                    @include('incidencias.partials.estado-badge', ['nombre' => $incidencia->estado->nombre, 'compacto' => true])
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

    <p class="text-muted small mt-2">{{ $incidencias->count() }} de {{ $totalIncidencias }} incidencia(s) en total</p>

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

    <div class="modal fade" id="modalCancelarIncidencia" tabindex="-1"
         aria-labelledby="modalCancelarIncidenciaTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formCancelarIncidencia" method="POST" data-conectividad-skip>
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h6" id="modalCancelarIncidenciaTitulo">Cancelar incidencia</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p id="cancelarIncidenciaAyuda" class="mb-3"></p>
                        <label for="comentarioCancelacion" class="form-label">Motivo de la cancelación</label>
                        <textarea id="comentarioCancelacion" name="comentario_cancelacion" class="form-control"
                                  rows="4" maxlength="200" required aria-describedby="ayudaComentarioCancelacion"></textarea>
                        <div id="ayudaComentarioCancelacion" class="form-text">Obligatorio. Máximo 200 caracteres.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-trebol" data-bs-dismiss="modal">Volver</button>
                        <button type="submit" class="btn btn-danger">Confirmar cancelación</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
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

    // ---------- Modal de cancelación ----------
    var modalCancelar = document.getElementById('modalCancelarIncidencia');
    if (modalCancelar) {
        modalCancelar.addEventListener('show.bs.modal', function (event) {
            var activador = event.relatedTarget;
            if (!activador) return;

            var formulario = document.getElementById('formCancelarIncidencia');
            var comentario = document.getElementById('comentarioCancelacion');
            var numero = activador.getAttribute('data-numero');

            formulario.action = activador.getAttribute('data-cancelar-url');
            comentario.value = '';
            document.getElementById('cancelarIncidenciaAyuda').textContent =
                '¿Desea cancelar la incidencia #' + numero + '? Esta acción cambiará su estado a Cancelada.';
        });

        modalCancelar.addEventListener('shown.bs.modal', function () {
            document.getElementById('comentarioCancelacion').focus();
        });
    }

    // ---------- Tooltips de los botones-icono de acciones ----------
    document.querySelectorAll('[data-tooltip][title]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover', container: 'body' });
    });

})();
</script>
@endpush
