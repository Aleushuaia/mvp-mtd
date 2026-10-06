{{--
    Barra de filtros de incidencias. Compartida por "Mis incidencias" (socio)
    y el panel del operador.
    Espera: $action (URL destino), $estados
    Opcional: $todosPorDefecto
      - true  (panel del operador): botón "Todos" y todos los estados tildados.
      - false (Mis incidencias, valor por defecto): sin "Todos"; arranca sólo
        con "En proceso" y "Limpiar campos" vuelve a ese estado inicial.
--}}
@php
    $estadosDisponibles = $estados;
    $todosPorDefecto = $todosPorDefecto ?? false;

    $estadoRecibido = request()->input('estado');
    $estadosSeleccionados = collect(is_array($estadoRecibido) ? $estadoRecibido : [$estadoRecibido])
        ->filter(fn ($estado) => is_numeric($estado))
        ->map(fn ($estado) => (int) $estado)
        ->intersect($estadosDisponibles->pluck('id_estado')->all())
        ->unique()
        ->values()
        ->all();

    // Selección al entrar sin filtros: el panel del operador arranca con
    // todos los estados tildados; "Mis incidencias", sólo con "En proceso".
    $estadosPredeterminados = $todosPorDefecto
        ? $estadosDisponibles->pluck('id_estado')->map(fn ($id) => (int) $id)->all()
        : [\App\Models\Incidencia::ESTADO_EN_PROCESO];

    if ($estadosSeleccionados === []) {
        $estadosSeleccionados = $estadosPredeterminados;
    }

    $todosMarcados = count($estadosSeleccionados) === $estadosDisponibles->count();

    $clasesEstado = [
        'Borrador' => 'borrador',
        'Pendiente' => 'pendiente',
        'Confirmada' => 'confirmada',
        'En proceso' => 'en-proceso',
        'Resuelta' => 'resuelta',
        'Cancelada' => 'cancelada',
    ];
@endphp

<form method="GET" action="{{ $action }}" class="panel filtros-incidencias mb-3" data-filtros-incidencias
      @unless ($todosPorDefecto) data-restablecer-estados @endunless>
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
                @if ($todosPorDefecto)
                    <label class="estado-selector__opcion estado-selector__opcion--todos">
                        <input type="checkbox" data-estado-todos @checked($todosMarcados)
                               aria-label="Seleccionar todos los estados">
                        <span>Todos</span>
                    </label>
                @endif
                @foreach ($estadosDisponibles as $estado)
                    @php($claseEstado = $clasesEstado[$estado->nombre] ?? 'borrador')
                    <label class="estado-selector__opcion estado-selector__opcion--{{ $claseEstado }}">
                        <input type="checkbox" name="estado[]" value="{{ $estado->id_estado }}"
                               @checked(in_array((int) $estado->id_estado, $estadosSeleccionados, true))
                               @if (in_array((int) $estado->id_estado, $estadosPredeterminados, true)) data-predeterminado @endif>
                        <span>{{ $estado->nombre }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div class="col-12 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-trebol btn-sm">Filtrar</button>
            <button type="button" class="btn btn-outline-trebol btn-sm"
                    @if (! filled(request('numero')) && ! filled(request('descripcion')) && ($todosPorDefecto || $estadosSeleccionados === $estadosPredeterminados)) disabled @endif
                    data-limpiar-campos title="Limpiar campos" aria-label="Limpiar campos">&times; Limpiar campos</button>
        </div>
    </div>

    @unless ($todosPorDefecto)
        {{-- Aviso superpuesto y centrado sobre el panel (se oculta solo) al restablecer los campos. --}}
        <div class="filtros-aviso" role="status" aria-live="polite" data-aviso-restablecido>
            <div class="filtros-aviso__caja">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>Los campos de búsqueda fueron restablecidos.</span>
            </div>
        </div>
    @endunless
</form>

@once
    @push('scripts')
    <script>
        document.querySelectorAll('[data-filtros-incidencias]').forEach(function (form) {
            var todos = form.querySelector('[data-estado-todos]');
            var estados = form.querySelectorAll('input[name="estado[]"]');
            var restablecerEstados = form.hasAttribute('data-restablecer-estados');

            function restaurarEstadosPredeterminados() {
                estados.forEach(function (estado) {
                    estado.checked = estado.hasAttribute('data-predeterminado');
                });
            }

            // Si la URL no trae "estado", esta carga es la entrada por
            // defecto: se fuerza la selección predeterminada aunque el
            // navegador haya restaurado checkboxes de una visita anterior
            // (F5, bfcache). Se usa URLSearchParams (no una regex sobre el
            // string crudo) porque el navegador codifica "estado[]" como
            // "estado%5B%5D" al armar la URL del filtro.
            var parametros = new URLSearchParams(window.location.search);
            if (!parametros.has('estado') && !parametros.has('estado[]')) {
                restaurarEstadosPredeterminados();
            }

            function sincronizarTodos() {
                if (!todos) {
                    return;
                }
                var marcados = Array.prototype.filter.call(estados, function (item) { return item.checked; });
                todos.checked = estados.length > 0 && marcados.length === estados.length;
            }

            estados.forEach(function (estado) {
                estado.addEventListener('change', function () {
                    var haySeleccion = Array.prototype.some.call(estados, function (item) { return item.checked; });
                    if (!haySeleccion) {
                        estado.checked = true;
                    }
                    sincronizarTodos();
                    sincronizarLimpiar();
                });
            });

            if (todos) {
                todos.addEventListener('change', function () {
                    // Atajo "marcar todo": el formulario siempre exige al
                    // menos un estado, así que destildar "Todos" no aplica.
                    estados.forEach(function (estado) { estado.checked = true; });
                    sincronizarTodos();
                });
            }

            sincronizarTodos();

            var limpiar = form.querySelector('[data-limpiar-campos]');
            var camposTexto = form.querySelectorAll('input[name="numero"], input[name="descripcion"]');
            var aviso = form.querySelector('[data-aviso-restablecido]');
            var temporizadorAviso = null;

            function ocultarAviso() {
                clearTimeout(temporizadorAviso);
                if (aviso) {
                    aviso.classList.remove('is-visible');
                }
            }

            function mostrarAviso() {
                if (!aviso) {
                    return;
                }
                aviso.classList.add('is-visible');
                clearTimeout(temporizadorAviso);
                temporizadorAviso = setTimeout(ocultarAviso, 2500);
            }

            if (aviso) {
                aviso.addEventListener('click', ocultarAviso);
                document.addEventListener('keydown', function (evento) {
                    if (evento.key === 'Escape') {
                        ocultarAviso();
                    }
                });
            }

            function sincronizarLimpiar() {
                if (!limpiar) {
                    return;
                }
                var hayTexto = Array.prototype.some.call(camposTexto, function (campo) {
                    return campo.value.trim() !== '';
                });
                var estadosModificados = restablecerEstados && Array.prototype.some.call(estados, function (estado) {
                    return estado.checked !== estado.hasAttribute('data-predeterminado');
                });
                limpiar.disabled = !hayTexto && !estadosModificados;
            }

            camposTexto.forEach(function (campo) {
                campo.addEventListener('input', sincronizarLimpiar);
            });

            // Restablece N.º y Descripción (y, donde corresponde, los
            // estados al predeterminado) en el propio formulario, sin
            // recargar la página (el botón es type="button": no dispara
            // submit). Los resultados cambian recién al tocar "Filtrar".
            if (limpiar) {
                limpiar.addEventListener('click', function () {
                    camposTexto.forEach(function (campo) { campo.value = ''; });
                    if (restablecerEstados) {
                        restaurarEstadosPredeterminados();
                        sincronizarTodos();
                    }
                    sincronizarLimpiar();
                    camposTexto[0] && camposTexto[0].focus();

                    mostrarAviso();
                });
            }

            sincronizarLimpiar();
        });
    </script>
    @endpush
@endonce
