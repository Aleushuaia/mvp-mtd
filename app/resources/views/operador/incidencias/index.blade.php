@extends('layouts.app')

@section('title', 'Incidencias')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="page-title h4 mb-0">Incidencias</h1>
            <p class="text-muted mb-0">Todas las incidencias del club — panel del operador.</p>
        </div>
        <span class="text-muted small">{{ $incidencias->count() }} resultado(s)</span>
    </div>

    @if (session('ok'))
        <div class="alert alert-success" role="alert">{{ session('ok') }}</div>
    @endif

    @include('incidencias.partials.filtros', ['action' => route('operador.incidencias.index'), 'estados' => $estados])

    @php($hayFiltros = request()->hasAny(['numero', 'descripcion', 'estado']))

    @if ($incidencias->isEmpty())
        <div class="panel text-center py-5">
            <div class="empty-icon" aria-hidden="true">&#128203;</div>
            <h2 class="h6 mb-1">Sin resultados</h2>
            <p class="text-muted mb-3">
                @if ($hayFiltros) Ninguna incidencia coincide con los filtros aplicados. @else Todavía no hay incidencias registradas. @endif
            </p>
            @if ($hayFiltros)
                <a href="{{ route('operador.incidencias.index') }}" class="btn btn-outline-trebol">Quitar filtros</a>
            @endif
        </div>
    @else
        {{-- Tabla (escritorio / tablet ancho) --}}
        <div class="panel d-none d-lg-block">
            <div class="table-responsive">
                <table class="table table-incidencias align-middle mb-0">
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Informada por</th>
                            <th>Tipo</th>
                            <th>Ubicación</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Responsables</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incidencias as $incidencia)
                            <tr>
                                <td class="fw-semibold">#{{ $incidencia->id_incidencia }}</td>
                                <td>{{ $incidencia->usuario->apellido_nombres ?? '—' }}</td>
                                <td>{{ $incidencia->tipo->nombre }}</td>
                                <td>{{ $incidencia->ubicacion->nombre }}</td>
                                <td class="celda-descripcion">{{ $incidencia->descripcion }}</td>
                                <td>@include('incidencias.partials.estado-badge', ['nombre' => $incidencia->estado->nombre])</td>
                                <td>
                                    @forelse ($incidencia->responsables as $r)
                                        <span class="resp-chip">{{ $r->usuario->apellido_nombres ?? '—' }}</span>
                                    @empty
                                        <span class="text-muted small">Sin asignar</span>
                                    @endforelse
                                </td>
                                <td class="text-end">
                                    <div class="acciones-iconos">
                                        <a href="{{ route('operador.incidencias.edit', $incidencia) }}"
                                           class="btn-icono {{ $incidencia->esGestionablePorOperador() ? 'btn-icono--ok' : '' }}"
                                           data-tooltip
                                           title="{{ $incidencia->esGestionablePorOperador() ? 'Gestionar' : 'Ver' }}"
                                           aria-label="Abrir incidencia">
                                            <i class="bi {{ $incidencia->esGestionablePorOperador() ? 'bi-pencil-square' : 'bi-eye' }}" aria-hidden="true"></i>
                                        </a>
                                        @if ($incidencia->estaEnProceso())
                                            <button type="button" class="btn-icono btn-icono--ok" data-tooltip title="Resolver incidencia"
                                                    aria-label="Marcar la incidencia como resuelta" data-bs-toggle="modal" data-bs-target="#modalResolverIncidencia"
                                                    data-resolver-url="{{ route('operador.incidencias.resolver', $incidencia) }}"
                                                    data-numero="{{ $incidencia->id_incidencia }}">
                                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="btn-icono btn-icono--danger" data-tooltip title="Cancelar incidencia"
                                                    aria-label="Cancelar la incidencia" data-bs-toggle="modal" data-bs-target="#modalCancelarIncidencia"
                                                    data-cancelar-url="{{ route('operador.incidencias.cancelar', $incidencia) }}"
                                                    data-numero="{{ $incidencia->id_incidencia }}">
                                                <i class="bi bi-x-circle" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tarjetas (celular / tablet angosto) --}}
        <div class="d-lg-none incidencia-cards">
            @foreach ($incidencias as $incidencia)
                <div class="panel incidencia-card">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <span class="fw-semibold">Incidencia #{{ $incidencia->id_incidencia }}</span>
                        @include('incidencias.partials.estado-badge', ['nombre' => $incidencia->estado->nombre])
                    </div>
                    <dl class="incidencia-card__datos mb-2">
                        <dt>Socio</dt><dd>{{ $incidencia->usuario->apellido_nombres ?? '—' }}</dd>
                        <dt>Tipo</dt><dd>{{ $incidencia->tipo->nombre }}</dd>
                        <dt>Ubicación</dt><dd>{{ $incidencia->ubicacion->nombre }}</dd>
                        <dt>Responsables</dt>
                        <dd>
                            @forelse ($incidencia->responsables as $r)
                                <span class="resp-chip">{{ $r->usuario->apellido_nombres ?? '—' }}</span>
                            @empty
                                <span class="text-muted">Sin asignar</span>
                            @endforelse
                        </dd>
                        <dt>Descripción</dt><dd>{{ $incidencia->descripcion }}</dd>
                    </dl>
                    <div class="acciones-iconos">
                        <a href="{{ route('operador.incidencias.edit', $incidencia) }}" class="btn btn-sm btn-outline-trebol">
                            {{ $incidencia->esGestionablePorOperador() ? 'Gestionar' : 'Ver detalle' }}
                        </a>
                        @if ($incidencia->estaEnProceso())
                            <button type="button" class="btn-icono btn-icono--ok" data-tooltip title="Resolver incidencia"
                                    aria-label="Marcar la incidencia como resuelta" data-bs-toggle="modal" data-bs-target="#modalResolverIncidencia"
                                    data-resolver-url="{{ route('operador.incidencias.resolver', $incidencia) }}"
                                    data-numero="{{ $incidencia->id_incidencia }}">
                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="btn-icono btn-icono--danger" data-tooltip title="Cancelar incidencia"
                                    aria-label="Cancelar la incidencia" data-bs-toggle="modal" data-bs-target="#modalCancelarIncidencia"
                                    data-cancelar-url="{{ route('operador.incidencias.cancelar', $incidencia) }}"
                                    data-numero="{{ $incidencia->id_incidencia }}">
                                <i class="bi bi-x-circle" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="modal fade" id="modalResolverIncidencia" tabindex="-1"
         aria-labelledby="modalResolverIncidenciaTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formResolverIncidencia" method="POST" data-conectividad-skip>
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h6" id="modalResolverIncidenciaTitulo">Resolver incidencia</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p id="resolverIncidenciaAyuda" class="mb-3"></p>
                        <label for="comentarioResolucion" class="form-label">Mensaje para el socio</label>
                        <textarea id="comentarioResolucion" name="comentario_resolucion" class="form-control"
                                  rows="4" maxlength="200" aria-describedby="ayudaComentarioResolucion"></textarea>
                        <div id="ayudaComentarioResolucion" class="form-text">Opcional. Máximo 200 caracteres.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-trebol" data-bs-dismiss="modal">Volver</button>
                        <button type="submit" class="btn btn-trebol">Confirmar resolución</button>
                    </div>
                </div>
            </form>
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
        var modalResolver = document.getElementById('modalResolverIncidencia');
        if (modalResolver) {
            modalResolver.addEventListener('show.bs.modal', function (event) {
                var activador = event.relatedTarget;
                if (!activador) return;

                var formulario = document.getElementById('formResolverIncidencia');
                var comentario = document.getElementById('comentarioResolucion');
                var numero = activador.getAttribute('data-numero');

                formulario.action = activador.getAttribute('data-resolver-url');
                comentario.value = '';
                document.getElementById('resolverIncidenciaAyuda').textContent =
                    '¿Desea marcar como resuelta la incidencia #' + numero + '?';
            });

            modalResolver.addEventListener('shown.bs.modal', function () {
                document.getElementById('comentarioResolucion').focus();
            });
        }

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

    document.querySelectorAll('[data-tooltip][title]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover', container: 'body' });
    });
    })();
</script>
@endpush
