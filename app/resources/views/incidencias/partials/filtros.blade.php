{{--
    Barra de filtros de incidencias. Compartida por "Mis incidencias" (socio)
    y el panel del operador.
    Espera: $action (URL destino), $estados (colección de EstadoIncidencia)
--}}
<form method="GET" action="{{ $action }}" class="panel filtros-incidencias mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label" for="f-numero">N.º</label>
            <input type="number" min="1" class="form-control" id="f-numero"
                   name="numero" value="{{ request('numero') }}" placeholder="Ej. 1004" inputmode="numeric">
        </div>
        <div class="col-12 col-md-5">
            <label class="form-label" for="f-descripcion">Descripción</label>
            <input type="text" class="form-control" id="f-descripcion"
                   name="descripcion" value="{{ request('descripcion') }}" placeholder="Texto a buscar…">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label" for="f-estado">Estado</label>
            <select class="form-select" id="f-estado" name="estado">
                <option value="">Todos</option>
                @foreach ($estados as $e)
                    <option value="{{ $e->id_estado }}" @selected((string) request('estado') === (string) $e->id_estado)>{{ $e->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-trebol flex-fill">Filtrar</button>
            @if (request()->hasAny(['numero', 'descripcion', 'estado']))
                <a href="{{ $action }}" class="btn btn-outline-trebol" title="Quitar filtros" aria-label="Quitar filtros">&times;</a>
            @endif
        </div>
    </div>
</form>
