@extends('layouts.app')

@section('title', 'Incidencia #'.$incidencia->id_incidencia)

@section('content')
    <nav aria-label="ruta" class="mb-3">
        <a href="{{ route('operador.incidencias.index') }}" class="volver-link">&larr; Volver a incidencias</a>
    </nav>

    @if (session('ok'))
        <div class="alert alert-success" role="alert">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
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
                    <dt>Informada por</dt><dd>{{ $incidencia->usuario->apellido_nombres ?? '—' }}</dd>
                    <dt>Registrada por</dt><dd>{{ $incidencia->usuarioAlta->apellido_nombres ?? '—' }}</dd>
                    <dt>Tipo</dt><dd>{{ $incidencia->tipo->nombre }}</dd>
                    <dt>Ubicación</dt><dd>{{ $incidencia->ubicacion->nombre }}</dd>
                    <dt>Descripción</dt><dd>{{ $incidencia->descripcion }}</dd>
                    <dt>Fecha del hecho</dt><dd>{{ $incidencia->fecha_hora_evento->format('d/m/Y H:i') }}</dd>
                    <dt>Fecha de alta</dt><dd>{{ $incidencia->fecha_hora_alta->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>

            <div class="panel">
                <h2 class="section-title">Gestión</h2>

                @if ($incidencia->esGestionablePorOperador())
                    <form method="POST" action="{{ route('operador.incidencias.update', $incidencia) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label" for="id_criticidad">Criticidad</label>
                            <select class="form-select" id="id_criticidad" name="id_criticidad">
                                @foreach ($criticidades as $c)
                                    <option value="{{ $c->id }}" @selected((int) old('id_criticidad', $incidencia->id_criticidad) === $c->id)>{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <fieldset class="mb-3">
                            <legend class="form-label">Responsables asignados</legend>
                            <p class="text-muted small mb-2">Puede asignar uno o varios responsables.</p>
                            <div class="responsables-picker">
                                @foreach ($responsables as $r)
                                    <label class="responsable-check">
                                        <input type="checkbox" name="responsables[]" value="{{ $r->id }}"
                                               @checked(in_array($r->id, old('responsables', $asignados)))>
                                        <span>{{ $r->usuario->apellido_nombres ?? ('Responsable #'.$r->id) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="form-actions">
                            <a href="{{ route('operador.incidencias.index') }}" class="btn btn-outline-trebol">Cancelar</a>
                            <button type="submit" class="btn btn-trebol">Guardar cambios</button>
                        </div>
                    </form>
                @else
                    <p class="text-muted mb-2">
                        La criticidad y los responsables sólo pueden editarse mientras la incidencia
                        está <strong>En proceso</strong>.
                    </p>
                    <dl class="datos-detalle mb-0">
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
                                Sin asignar
                            @endforelse
                        </dd>
                    </dl>
                @endif
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="panel">
                <h2 class="section-title">Responsables actuales</h2>
                @forelse ($incidencia->responsables as $r)
                    <div class="responsable-box mb-2">
                        <div class="responsable-avatar" aria-hidden="true">
                            {{ Str::upper(Str::substr($r->usuario->apellido_nombres ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $r->usuario->apellido_nombres ?? '—' }}</div>
                            <div class="text-muted small">
                                Asignado el {{ \Illuminate\Support\Carbon::parse($r->pivot->fecha_asignacion)->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="responsable-pendiente mb-0">Responsable pendiente de asignación</p>
                @endforelse
            </div>

            <div class="panel">
                <h2 class="section-title">Historial de estados</h2>
                <ol class="timeline">
                    @forelse ($incidencia->historial as $evento)
                        <li class="timeline__item">
                            <div class="timeline__dot"></div>
                            <div class="timeline__body">
                                <div class="timeline__head">
                                    <strong>{{ $evento->estadoAnterior?->nombre ?? 'Alta' }} &rarr; {{ $evento->estadoNuevo?->nombre }}</strong>
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
