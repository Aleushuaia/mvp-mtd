<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso · EcoReporte</title>
    <link rel="icon" type="image/png" href="{{ asset('img/trebol.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/trebol.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
</head>
<body class="auth-body">

    <main class="auth-card">
        <div class="auth-brand">
            <img src="{{ asset('img/trebol.png') }}" alt="EcoReporte">
            <h1>EcoReporte</h1>
            <p>Gestión de Incidencias</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="loginForm" method="POST" action="{{ route('login.attempt') }}" novalidate data-conectividad-skip>
            @csrf
            <input type="hidden" id="acepta" name="acepta" value="0">

            <div class="mb-2">
                <label for="nombre_usuario" class="form-label">Nombre de usuario</label>
                <input type="text" class="form-control @error('nombre_usuario') is-invalid @enderror"
                       id="nombre_usuario" name="nombre_usuario" value="{{ old('nombre_usuario') }}"
                       autocomplete="username" autocapitalize="none" spellcheck="false"
                       placeholder="nombre.apellido@mvp.mail" required autofocus>
            </div>

            <p class="text-muted mb-3">
                En esta versión de prueba no se solicita contraseña: se ingresa sólo con el
                nombre de usuario.
            </p>

            <div class="auth-actions">
                <button type="submit" id="btnIngresar" class="btn btn-trebol w-100">Continuar</button>
            </div>
            <noscript>
                <p class="small text-muted mt-3">Activá JavaScript para aceptar las condiciones e ingresar.</p>
            </noscript>
        </form>

        <p class="auth-footnote">Prototipo funcional — Etapa MVP</p>
    </main>

    <div class="auth-terms-modal" id="modalCondiciones" role="presentation" hidden>
        <div class="auth-terms-modal__backdrop"></div>
        <section class="auth-terms-modal__dialog" role="dialog" aria-modal="true"
                 aria-labelledby="modalCondicionesTitulo">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h6" id="modalCondicionesTitulo">Términos y condiciones de uso</h2>
                    <button type="button" class="auth-terms-modal__close" id="btnCerrarCondiciones"
                            aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="auth-terms-intro">Antes de ingresar, te pedimos que leas las condiciones de uso.</p>
                    <div class="terms-box">
                        <p>El sistema es de uso exclusivo para socios mayores de edad. Los datos personales
                        se recolectan y utilizan únicamente para identificar y gestionar los reclamos,
                        conforme a la Ley 25.326 de Protección de Datos Personales.</p>
                        <p class="mb-0">La información registrada podrá ser consultada por el personal autorizado
                        del Club para el seguimiento y resolución de cada incidencia.</p>
                    </div>
                    <div id="confirmacionCondiciones" class="form-check terms-check">
                        <input class="form-check-input" type="checkbox" id="aceptaCondiciones">
                        <label class="form-check-label" for="aceptaCondiciones">
                            He leído y acepto los términos y condiciones de uso.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-trebol" id="btnCancelarCondiciones">Cerrar</button>
                    <button type="button" id="btnAceptarCondiciones" class="btn btn-trebol" disabled>Continuar</button>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/conectividad.js') }}?v={{ filemtime(public_path('js/conectividad.js')) }}"></script>
    <script>
        (function () {
            var storageKey = 'trebol.condicionesAceptadas.v3';
            var accepted = false;
            var form = document.getElementById('loginForm');
            var input = document.getElementById('acepta');
            var chk = document.getElementById('aceptaCondiciones');
            var btn = document.getElementById('btnIngresar');
            var confirmBtn = document.getElementById('btnAceptarCondiciones');
            var modalElement = document.getElementById('modalCondiciones');
            var closeBtn = document.getElementById('btnCerrarCondiciones');
            var cancelBtn = document.getElementById('btnCancelarCondiciones');

            try {
                accepted = localStorage.getItem(storageKey) === '1';
            } catch (error) {
                // Si el navegador bloquea el almacenamiento, se puede aceptar para este ingreso.
            }

            function sync() {
                input.value = accepted ? '1' : '0';
            }

            function abrirModal() {
                chk.checked = false;
                confirmBtn.disabled = true;
                modalElement.hidden = false;
                requestAnimationFrame(function () {
                    chk.focus();
                });
            }

            function cerrarModal() {
                modalElement.hidden = true;
                btn.focus();
            }

            chk.addEventListener('change', function () {
                confirmBtn.disabled = !chk.checked;
            });

            confirmBtn.addEventListener('click', function () {
                if (!chk.checked) return;

                accepted = true;
                try {
                    localStorage.setItem(storageKey, '1');
                } catch (error) {
                    // Se conserva la aceptación para este ingreso aunque no pueda guardarse.
                }
                sync();
                cerrarModal();
                form.requestSubmit();
            });

            closeBtn.addEventListener('click', cerrarModal);
            cancelBtn.addEventListener('click', cerrarModal);

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modalElement.hidden) {
                    cerrarModal();
                }
            });

            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                    return;
                }

                if (!accepted) {
                    event.preventDefault();
                    abrirModal();
                }
            });

            sync();
        })();
    </script>
</body>
</html>
