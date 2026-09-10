{{--
    Barra de filtros de incidencias. Compartida por "Mis incidencias" (socio)
    y el panel del operador.
    Espera: $action (URL destino), $estados
--}}
@php
    $estadosDisponibles = $estados;

    $estadoRecibido = request()->input('estado');
    $estadosSeleccionados = collect(is_array($estadoRecibido) ? $estadoRecibido : [$estadoRecibido])
        ->filter(fn ($estado) => is_numeric($estado))
        ->map(fn ($estado) => (int) $estado)
        ->intersect($estadosDisponibles->pluck('id_estado')->all())
        ->unique()
        ->values()
        ->all();

    if ($estadosSeleccionados === []) {
        $estadosSeleccionados = [\App\Models\Incidencia::ESTADO_EN_PROCESO];
    }

    $clasesEstado = [
        'Borrador' => 'borrador',
        'Pendiente' => 'pendiente',
        'Confirmada' => 'confirmada',
        'En proceso' => 'en-proceso',
        'Resuelta' => 'resuelta',
        'Cancelada' => 'cancelada',
    ];
@endphp

<form method="GET" action="{{ $action }}" class="panel filtros-incidencias mb-3" data-filtros-incidencias>
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-1">
            <label class="form-label" for="f-numero">N.º</label>
            <input type="number" min="1" class="form-control form-control-sm" id="f-numero"
                   name="numero" value="{{ request('numero') }}" placeholder="Ej. 1004" inputmode="numeric">
        </div>
        <div class="col-12 col-md-7">
            <label class="form-label" for="f-descripcion">Descripción</label>
            <input type="text" class="form-control form-control-sm" id="f-descripcion"
                   name="descripcion" value="{{ request('descripcion') }}" placeholder="Texto a buscar…">
        </div>
        <fieldset class="col-12 estado-selector" aria-label="Estados de las incidencias">
            <div class="estado-selector__opciones">
                @foreach ($estadosDisponibles as $estado)
                    @php($claseEstado = $clasesEstado[$estado->nombre] ?? 'borrador')
                    <label class="estado-selector__opcion estado-selector__opcion--{{ $claseEstado }}">
                        <input type="checkbox" name="estado[]" value="{{ $estado->id_estado }}"
                               @checked(in_array((int) $estado->id_estado, $estadosSeleccionados, true))>
                        <span>{{ $estado->nombre }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div class="col-12 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-trebol btn-sm">Filtrar</button>
            @if (request()->hasAny(['numero', 'descripcion']) || $estadosSeleccionados !== [\App\Models\Incidencia::ESTADO_EN_PROCESO])
                <a href="{{ $action }}" class="btn btn-outline-trebol btn-sm" title="Quitar filtros" aria-label="Quitar filtros">&times; Quitar filtros</a>
            @endif
        </div>
    </div>
</form>

@once
    @push('scripts')
    <script>
        document.querySelectorAll('[data-filtros-incidencias]').forEach(function (form) {
            var estados = form.querySelectorAll('input[name="estado[]"]');
            estados.forEach(function (estado) {
                estado.addEventListener('change', function () {
                    var haySeleccion = Array.prototype.some.call(estados, function (item) { return item.checked; });
                    if (!haySeleccion) {
                        estado.checked = true;
                    }
                });
            });
        });
    </script>
    @endpush
@endonce
