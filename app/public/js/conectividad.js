/* ============================================================
   Aviso amigable de conectividad para las acciones de backend
   ------------------------------------------------------------
   Intercepta:
     - el envío de cualquier formulario POST (login, salir,
       confirmar / cancelar incidencia, gestión del operador…);
     - la navegación de los enlaces marcados con data-conectividad
       (botones "Nueva incidencia", "Mis incidencias", etc.).

   Antes de continuar comprueba únicamente el estado de conexión que
   informa el navegador. No realiza solicitudes previas al backend, para
   que la acción se ejecute sin una espera adicional.

   El banner de fallo ofrece "Reintentar" y "Solicitar ayuda"
   (acercarse a la recepción del club).

   El formulario de "Nueva incidencia" queda excluido
   (data-conectividad-skip): tiene su propio manejo con borrador.
   ============================================================ */
(function () {
    'use strict';

    // ---------- Contenedor fijo de avisos ----------
    var box = document.getElementById('avisoConectividad');
    if (!box) {
        box = document.createElement('div');
        box.id = 'avisoConectividad';
        box.setAttribute('aria-live', 'assertive');
        (document.body || document.documentElement).appendChild(box);
    }

    function limpiar() {
        box.innerHTML = '';
        box.classList.remove('is-visible');
    }
    function pintar(html) {
        box.innerHTML = html;
        box.classList.add('is-visible');
    }

    function mostrarAyuda() {
        pintar(
            '<div class="alert alert-info banner-red mb-0" role="status">' +
                '<div class="banner-red__texto">' +
                    '<strong>Acercate a la recepción del club.</strong> ' +
                    'El personal puede completar la operación por vos o ayudarte a conectarte a la red WiFi del lugar.' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-dark banner-red__btn" data-cerrar>Cerrar</button>' +
            '</div>'
        );
        var b = box.querySelector('[data-cerrar]');
        if (b) b.addEventListener('click', limpiar);
    }

    function mostrarFallo(reintentar) {
        pintar(
            '<div class="alert alert-danger banner-red mb-0" role="alert">' +
                '<div class="banner-red__texto">' +
                    '<strong>Se perdió la conexión.</strong> ' +
                    'No pudimos completar la operación. Revisá tu conexión e intentá de nuevo; ' +
                    'si el problema persiste, acercate a la recepción del club para solicitar ayuda.' +
                '</div>' +
                '<span class="banner-red__acciones">' +
                    '<button type="button" class="btn btn-sm btn-danger banner-red__btn" data-reintentar>Reintentar</button>' +
                    '<button type="button" class="btn btn-sm btn-outline-dark banner-red__btn" data-ayuda>Solicitar ayuda</button>' +
                '</span>' +
            '</div>'
        );
        var rt = box.querySelector('[data-reintentar]');
        var ay = box.querySelector('[data-ayuda]');
        if (rt) rt.addEventListener('click', function () { limpiar(); if (typeof reintentar === 'function') reintentar(); });
        if (ay) ay.addEventListener('click', mostrarAyuda);
    }

    // API para otros scripts (p. ej. borrador-incidencia.js)
    window.AvisoConectividad = {
        mostrarFallo: mostrarFallo,
        mostrarAyuda: mostrarAyuda,
        limpiar: limpiar
    };

    // ---------- Comprobación inmediata de conexión ----------
    function verificarConexion(alExito, alFallo) {
        if (navigator.onLine === false) {
            alFallo();
            return;
        }
        alExito();
    }

    // ---------- Formularios POST ----------
    function botonEnvio(form) {
        return form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
    }

    function probarYEnviar(form) {
        limpiar();
        var btn = botonEnvio(form);
        if (btn) btn.disabled = true;

        verificarConexion(
            function () { limpiar(); form.submit(); },
            function () {
                if (btn) btn.disabled = false;
                mostrarFallo(function () { probarYEnviar(form); });
            }
        );
    }

    // ---------- Enlaces de navegación marcados ----------
    function probarYNavegar(href) {
        limpiar();
        verificarConexion(
            function () { limpiar(); window.location.assign(href); },
            function () { mostrarFallo(function () { probarYNavegar(href); }); }
        );
    }

    function esExterno(a) {
        return a.host && a.host !== window.location.host;
    }

    // ---------- Enganche de listeners ----------
    function interceptar() {
        document.querySelectorAll('form').forEach(function (form) {
            if (form.__conectividad) return;
            if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
            if (form.hasAttribute('data-conectividad-skip')) return;
            form.__conectividad = true;
            form.addEventListener('submit', function (e) {
                if (e.defaultPrevented) return; // otro handler ya canceló (p. ej. confirm())
                e.preventDefault();
                probarYEnviar(form);
            });
        });

        document.querySelectorAll('a[data-conectividad]').forEach(function (a) {
            if (a.__conectividad) return;
            a.__conectividad = true;
            a.addEventListener('click', function (e) {
                if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                var href = a.getAttribute('href');
                if (!href || href.charAt(0) === '#' || esExterno(a) || a.hasAttribute('target')) return;
                e.preventDefault();
                probarYNavegar(a.href);
            });
        });
    }

    interceptar();
    document.addEventListener('DOMContentLoaded', interceptar);
    window.addEventListener('load', interceptar);
})();
