@extends('layouts.app')

@section('title', 'Nueva incidencia')

@section('content')
    <nav aria-label="ruta" class="mb-3">
        <a href="{{ route('panel.socio') }}" class="volver-link">&larr; Volver al panel</a>
    </nav>

    <div class="panel panel--form">
        <h1 class="page-title h4">Nueva incidencia</h1>
        <p class="page-subtitle">
            Indique el tipo, la ubicación y una breve descripción del problema.
            Todos los campos son obligatorios.
        </p>

        {{-- Banners dinámicos (conexión lenta / envío fallido). Sin JS quedan vacíos. --}}
        <div id="bannersIncidencia" aria-live="polite"></div>

        {{-- Errores del servidor (fallback sin JavaScript). --}}
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="formIncidencia" method="POST" action="{{ route('incidencias.store') }}" novalidate
              data-conectividad-skip
              data-borrador-key="borrador_incidencia_v1"
              data-index-url="{{ route('incidencias.index') }}">
            @csrf

            <div class="mb-3">
                <label for="id_tipo_incidencia" class="form-label">Tipo de incidencia <span class="req">*</span></label>
                <select class="form-select @error('id_tipo_incidencia') is-invalid @enderror"
                        id="id_tipo_incidencia" name="id_tipo_incidencia" required>
                    <option value="" selected disabled>Seleccione una opción…</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->id }}" @selected(old('id_tipo_incidencia') == $tipo->id)>{{ $tipo->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="id_ubicacion" class="form-label">Ubicación <span class="req">*</span></label>
                <select class="form-select @error('id_ubicacion') is-invalid @enderror"
                        id="id_ubicacion" name="id_ubicacion" required>
                    <option value="" selected disabled>Seleccione una opción…</option>
                    @foreach ($ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id }}" @selected(old('id_ubicacion') == $ubicacion->id)>{{ $ubicacion->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="fecha_hora_evento" class="form-label">Fecha y hora del hecho <span class="req">*</span></label>
                <input type="datetime-local"
                       class="form-control @error('fecha_hora_evento') is-invalid @enderror"
                       id="fecha_hora_evento" name="fecha_hora_evento"
                       value="{{ old('fecha_hora_evento', now()->format('Y-m-d\TH:i')) }}"
                       max="{{ now()->format('Y-m-d\TH:i') }}" required>
                <div class="form-text">Cuándo ocurrió el problema (no puede ser una fecha futura).</div>
            </div>

            <div class="mb-2">
                <label for="descripcion" class="form-label">Descripción <span class="req">*</span></label>
                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                          id="descripcion" name="descripcion" rows="3" maxlength="140"
                          placeholder="Describa brevemente el problema observado" required>{{ old('descripcion') }}</textarea>
                <div class="form-text text-end"><span id="contadorDescripcion">0</span>/140</div>
            </div>

            <div class="form-actions">
                <a href="{{ route('panel.socio') }}" class="btn btn-outline-trebol">Cancelar</a>
                <button type="submit" id="btnGuardarIncidencia" class="btn btn-trebol">Guardar incidencia</button>
            </div>
        </form>
    </div>

    {{-- ================= Modal: borrador detectado ================= --}}
    <div class="modal fade" id="modalBorrador" tabindex="-1" aria-labelledby="modalBorradorTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h6" id="modalBorradorTitulo">Tenés un borrador sin enviar</h2>
                </div>
                <div class="modal-body">
                    <p id="borradorHace" class="text-muted small mb-3">Guardado hace un momento</p>
                    <p class="mb-2">Encontramos una incidencia que empezaste a cargar en este dispositivo:</p>
                    <dl class="datos-modal">
                        <dt>Tipo</dt><dd id="bpTipo">—</dd>
                        <dt>Ubicación</dt><dd id="bpUbicacion">—</dd>
                        <dt>Descripción</dt><dd id="bpDescripcion">—</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnEmpezarBlanco" class="btn btn-outline-trebol">Empezar en blanco</button>
                    <button type="button" id="btnContinuarBorrador" class="btn btn-trebol">Continuar borrador</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('js/borrador-incidencia.js') }}?v={{ filemtime(public_path('js/borrador-incidencia.js')) }}"></script>
@endpush
