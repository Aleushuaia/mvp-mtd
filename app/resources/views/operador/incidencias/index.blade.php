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

    @if ($procesando)
        <div class="alert alert-asignacion d-flex align-items-center gap-2" role="status">
            <span class="spinner" aria-hidden="true"></span>
            Simulación en curso (asignación / resolución automática de incidencias). La lista se actualiza sola.
        </div>
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
                            <th>Criticidad</th>
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
                                <td>
                                    <span class="badge crit-badge crit-{{ Str::slug($incidencia->criticidad?->nombre ?? 'Normal') }}">
                                        {{ $incidencia->criticidad?->nombre ?? 'Normal' }}
                                    </span>
                                </td>
                                <td>@include('incidencias.partials.estado-badge', ['nombre' => $incidencia->estado->nombre])</td>
                                <td>
                                    @forelse ($incidencia->responsables as $r)
                                        <span class="resp-chip">{{ $r->usuario->apellido_nombres ?? '—' }}</span>
                                    @empty
                                        <span class="text-muted small">Sin asignar</span>
                                    @endforelse
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('operador.incidencias.edit', $incidencia) }}"
                                       class="btn-icono {{ $incidencia->esGestionablePorOperador() ? 'btn-icono--ok' : '' }}"
                                       data-tooltip
                                       title="{{ $incidencia->esGestionablePorOperador() ? 'Gestionar' : 'Ver' }}"
                                       aria-label="Abrir incidencia">
                                        <i class="bi {{ $incidencia->esGestionablePorOperador() ? 'bi-pencil-square' : 'bi-eye' }}" aria-hidden="true"></i>
                                    </a>
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
                        <dt>Criticidad</dt>
                        <dd>
                            <span class="badge crit-badge crit-{{ Str::slug($incidencia->criticidad?->nombre ?? 'Normal') }}">
                                {{ $incidencia->criticidad?->nombre ?? 'Normal' }}
                            </span>
                        </dd>
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
                    <div class="text-end">
                        <a href="{{ route('operador.incidencias.edit', $incidencia) }}" class="btn btn-sm btn-outline-trebol">
                            {{ $incidencia->esGestionablePorOperador() ? 'Gestionar' : 'Ver detalle' }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-tooltip][title]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover', container: 'body' });
    });
    @if ($procesando)
    setTimeout(function () { window.location.reload(); }, 11000);
    @endif
</script>
@endpush
