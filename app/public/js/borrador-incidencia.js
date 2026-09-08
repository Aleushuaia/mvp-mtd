/* ============================================================
   Borrador de incidencia en el navegador (JS vanilla)
   ------------------------------------------------------------
   - Autoguardado en localStorage con debounce (~400 ms).
   - Independiente del backend: si PHP no responde, el borrador
     queda intacto. Sólo se borra tras un HTTP ok confirmado.
   - Al abrir el formulario, si hay borrador -> modal para
     "Continuar borrador" o "Empezar en blanco".
   - Envío por fetch + AbortController:
       * ~3 s sin respuesta -> banner de conexión lenta (no bloqueante).
       * error de red / offline -> banner de fallo, borrador a salvo,
         botón "Reintentar".
   ============================================================ */
(function () {
    'use strict';

    var form = document.getElementById('formIncidencia');
    if (!form) return;

    var STORAGE_KEY = form.getAttribute('data-borrador-key') || 'borrador_incidencia_v1';
    var INDEX_URL = form.getAttribute('data-index-url') || '/incidencias';
    var CAMPOS = ['id_tipo_incidencia', 'id_ubicacion', 'fecha_hora_evento', 'descripcion'];
    var DEBOUNCE_MS = 400;
    var LENTO_MS = 3000;

    var bannersBox = document.getElementById('bannersIncidencia');
    var btnGuardar = document.getElementById('btnGuardarIncidencia');
    var areaDescripcion = document.getElementById('descripcion');
    var contador = document.getElementById('contadorDescripcion');

    // -------------------- localStorage --------------------
    function leerAlmacen() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null'); }
        catch (e) { return null; }
    }
    function escribirAlmacen(obj) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(obj)); } catch (e) { /* modo privado / cuota */ }
    }
    function borrarBorrador() {
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* no-op */ }
    }

    function leerFormulario() {
        var data = {};
        CAMPOS.forEach(function (name) {
            var el = form.elements[name];
            if (el) data[name] = el.value;
        });
        return data;
    }
    function tieneContenido(data) {
        if (!data) return false;
        return !!(data.id_tipo_incidencia || data.id_ubicacion || (data.descripcion && data.descripcion.trim()));
    }

    // -------------------- Autoguardado con debounce --------------------
    // Cuando el backend confirma el guardado, se bloquea todo autoguardado
    // posterior: así el borrador borrado NO se vuelve a escribir mientras la
    // página navega (pagehide / visibilitychange / debounce pendiente).
    var guardadoConfirmado = false;
    var debounceId = null;

    function programarGuardado() {
        if (guardadoConfirmado) return;
        clearTimeout(debounceId);
        debounceId = setTimeout(function () {
            if (guardadoConfirmado) return;
            var data = leerFormulario();
            if (tieneContenido(data)) {
                escribirAlmacen({ data: data, savedAt: new Date().toISOString() });
            } else {
                borrarBorrador();
            }
        }, DEBOUNCE_MS);
    }
    function guardarAhora() {
        if (guardadoConfirmado) return;
        clearTimeout(debounceId);
        var data = leerFormulario();
        if (tieneContenido(data)) {
            escribirAlmacen({ data: data, savedAt: new Date().toISOString() });
        }
    }

    form.addEventListener('input', programarGuardado);
    form.addEventListener('change', programarGuardado);

    // Flush inmediato al salir/ocultar la página (por si el debounce no llegó a disparar).
    window.addEventListener('pagehide', guardarAhora);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') guardarAhora();
    });

    // -------------------- Contador de descripción --------------------
    function sincronizarContador() {
        if (contador && areaDescripcion) contador.textContent = areaDescripcion.value.length;
    }
    if (areaDescripcion) areaDescripcion.addEventListener('input', sincronizarContador);
    sincronizarContador();

    // -------------------- Tiempo relativo en español --------------------
    function textoHace(iso) {
        var t = new Date(iso).getTime();
        if (isNaN(t)) return 'hace un momento';
        var seg = Math.max(0, Math.round((Date.now() - t) / 1000));
        if (seg < 45) return 'hace unos segundos';
        var min = Math.round(seg / 60);
        if (min < 60) return 'hace ' + min + (min === 1 ? ' minuto' : ' minutos');
        var hs = Math.round(min / 60);
        if (hs < 24) return 'hace ' + hs + (hs === 1 ? ' hora' : ' horas');
        var dias = Math.round(hs / 24);
        return 'hace ' + dias + (dias === 1 ? ' día' : ' días');
    }

    function textoOpcion(name, value) {
        var sel = form.elements[name];
        if (!sel || !value) return null;
        var opts = sel.options || [];
        for (var i = 0; i < opts.length; i++) {
            if (opts[i].value === String(value)) return opts[i].textContent.trim();
        }
        return null;
    }

    // -------------------- Cargar valores del borrador al formulario --------------------
    function cargarBorrador(data) {
        CAMPOS.forEach(function (name) {
            var el = form.elements[name];
            if (el && typeof data[name] !== 'undefined' && data[name] !== null) {
                el.value = data[name];
            }
        });
        sincronizarContador();
    }

    // -------------------- Modal "borrador detectado" --------------------
    function ofrecerBorrador(borrador) {
        var modalEl = document.getElementById('modalBorrador');

        // Fallback sin Bootstrap: preguntar con confirm (no sobreescribe en silencio).
        if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) {
            if (window.confirm('Hay un borrador de incidencia guardado en este dispositivo. ¿Desea continuarlo?')) {
                cargarBorrador(borrador.data);
            } else {
                borrarBorrador();
            }
            return;
        }

        var elHace = document.getElementById('borradorHace');
        if (elHace) elHace.textContent = 'Guardado ' + textoHace(borrador.savedAt);

        var d = (borrador.data.descripcion || '').trim();
        var setPrev = function (id, val) {
            var n = document.getElementById(id);
            if (n) n.textContent = val || '—';
        };
        setPrev('bpTipo', textoOpcion('id_tipo_incidencia', borrador.data.id_tipo_incidencia));
        setPrev('bpUbicacion', textoOpcion('id_ubicacion', borrador.data.id_ubicacion));
        setPrev('bpDescripcion', d ? (d.length > 90 ? d.slice(0, 90) + '…' : d) : '');

        var modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });

        var btnCont = document.getElementById('btnContinuarBorrador');
        var btnBlanco = document.getElementById('btnEmpezarBlanco');

        if (btnCont) btnCont.onclick = function () { cargarBorrador(borrador.data); modal.hide(); };
        if (btnBlanco) btnBlanco.onclick = function () { borrarBorrador(); modal.hide(); };

        modal.show();
    }

    var borradorInicial = leerAlmacen();
    if (borradorInicial && tieneContenido(borradorInicial.data)) {
        ofrecerBorrador(borradorInicial);
    }

    // -------------------- Banners --------------------
    function limpiarBanners() { if (bannersBox) bannersBox.innerHTML = ''; }

    function bannerLento() {
        if (!bannersBox) return;
        limpiarBanners();
        var el = document.createElement('div');
        el.className = 'alert alert-warning banner-red';
        el.setAttribute('role', 'status');
        el.innerHTML =
            '<div class="banner-red__texto">' +
                '<strong>Tu conexión está muy lenta.</strong> ' +
                'Te recomendamos acercarte a la recepción del club para conectarte a la red WiFi del lugar, ' +
                'o solicitar asistencia si el problema persiste.' +
            '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-dark banner-red__btn" data-accion="asistencia">Solicitar asistencia</button>';
        bannersBox.appendChild(el);
        el.querySelector('[data-accion="asistencia"]').addEventListener('click', function () {
            el.querySelector('.banner-red__texto').innerHTML =
                '<strong>Solicitud de asistencia registrada.</strong> ' +
                'Acércate a la recepción del club: un operador puede cargar la incidencia por vos. ' +
                'Tus datos quedaron guardados como borrador en este dispositivo.';
            this.remove();
        });
    }

    function bannerFallo() {
        if (!bannersBox) return;
        limpiarBanners();
        var el = document.createElement('div');
        el.className = 'alert alert-danger banner-red';
        el.setAttribute('role', 'alert');
        el.innerHTML =
            '<div class="banner-red__texto">' +
                '<strong>No pudimos completar el envío.</strong> ' +
                'Tus datos quedaron guardados como borrador en este dispositivo, así que podés ' +
                'reintentar sin volver a cargarlos. Si el problema persiste, acercate a la recepción ' +
                'del club para solicitar ayuda.' +
            '</div>' +
            '<span class="banner-red__acciones">' +
                '<button type="button" class="btn btn-sm btn-danger banner-red__btn" data-accion="reintentar">Reintentar</button>' +
                '<button type="button" class="btn btn-sm btn-outline-dark banner-red__btn" data-accion="asistencia">Solicitar ayuda</button>' +
            '</span>';
        bannersBox.appendChild(el);
        el.querySelector('[data-accion="reintentar"]').addEventListener('click', function () { enviar(); });
        el.querySelector('[data-accion="asistencia"]').addEventListener('click', function () {
            el.querySelector('.banner-red__texto').innerHTML =
                '<strong>Acercate a la recepción del club.</strong> ' +
                'El personal puede cargar la incidencia por vos o ayudarte con la conexión. ' +
                'Tus datos siguen guardados como borrador en este dispositivo.';
            el.querySelector('.banner-red__acciones').remove();
        });
    }

    function mostrarErroresValidacion(json) {
        if (!bannersBox) return;
        limpiarBanners();
        var mensajes = [];
        if (json && json.errors) {
            Object.keys(json.errors).forEach(function (k) { mensajes = mensajes.concat(json.errors[k]); });
        } else if (json && json.message) {
            mensajes = [json.message];
        } else {
            mensajes = ['No se pudo guardar la incidencia. Revisá los datos ingresados.'];
        }
        var el = document.createElement('div');
        el.className = 'alert alert-danger';
        el.setAttribute('role', 'alert');
        var ul = document.createElement('ul');
        ul.className = 'mb-0 ps-3';
        mensajes.forEach(function (m) {
            var li = document.createElement('li');
            li.textContent = m;
            ul.appendChild(li);
        });
        el.appendChild(ul);
        bannersBox.appendChild(el);
    }

    // -------------------- Envío con fetch + AbortController --------------------
    var enviando = false;
    var abortActual = null;

    function restaurarBoton() {
        enviando = false;
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.textContent = btnGuardar.getAttribute('data-txt') || 'Guardar incidencia';
        }
    }

    function enviar() {
        if (enviando) return;
        enviando = true;

        if (btnGuardar) {
            btnGuardar.setAttribute('data-txt', btnGuardar.textContent);
            btnGuardar.disabled = true;
            btnGuardar.textContent = 'Enviando…';
        }
        limpiarBanners();

        // Persistir el borrador ANTES de enviar (por si el envío falla).
        var data = leerFormulario();
        if (tieneContenido(data)) {
            escribirAlmacen({ data: data, savedAt: new Date().toISOString() });
        }

        // El navegador ya sabe que no hay conexión: no intentamos siquiera.
        if (navigator.onLine === false) {
            bannerFallo();
            restaurarBoton();
            return;
        }

        // Abortar un intento previo pendiente (p. ej. tras "Reintentar").
        if (abortActual) { try { abortActual.abort(); } catch (e) { /* no-op */ } }
        abortActual = ('AbortController' in window) ? new AbortController() : null;

        var lentoId = setTimeout(bannerLento, LENTO_MS);

        fetch(form.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
            credentials: 'same-origin',
            signal: abortActual ? abortActual.signal : undefined
        })
            .then(function (resp) {
                clearTimeout(lentoId);

                if (resp.ok) {
                    // El backend confirmó que la incidencia se guardó: recién ahora
                    // se elimina el borrador del navegador y se bloquea todo
                    // autoguardado posterior (para que no se vuelva a escribir
                    // mientras la página redirige).
                    guardadoConfirmado = true;
                    clearTimeout(debounceId);
                    borrarBorrador();

                    return resp.json()
                        .then(function (json) {
                            window.location.assign((json && json.redirect) || INDEX_URL);
                        })
                        .catch(function () {
                            window.location.assign(INDEX_URL);
                        });
                }

                if (resp.status === 422) {
                    return resp.json().then(function (json) {
                        mostrarErroresValidacion(json);
                        restaurarBoton();
                    });
                }

                // 500 u otro: tratar como fallo; conservar borrador.
                bannerFallo();
                restaurarBoton();
            })
            .catch(function (err) {
                clearTimeout(lentoId);
                if (err && err.name === 'AbortError') return; // reintento en curso
                bannerFallo(); // offline / error de red -> borrador intacto
                restaurarBoton();
            });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        enviar();
    });
})();
