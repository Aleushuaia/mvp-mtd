<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso · Gestión de Incidencias</title>
    <link rel="icon" type="image/png" href="{{ asset('img/trebol.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/trebol.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
</head>
<body class="auth-body">

    <main class="auth-card">
        <div class="auth-brand">
            <img src="{{ asset('img/trebol.png') }}" alt="Club Social y Deportivo El Trébol">
            <h1>Gestión de Incidencias</h1>
            <p>Club Social y Deportivo &laquo;El Trébol&raquo;</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" novalidate>
            @csrf

            <div class="mb-2">
                <label for="nombre_usuario" class="form-label">Nombre de usuario</label>
                <input type="text" class="form-control @error('nombre_usuario') is-invalid @enderror"
                       id="nombre_usuario" name="nombre_usuario" value="{{ old('nombre_usuario') }}"
                       autocomplete="username" autocapitalize="none" spellcheck="false"
                       placeholder="nombre.apellido@mvp.mail" required autofocus>
            </div>

            <p class="text-muted mb-3" style="font-size:.76rem;">
                En esta versión de prueba no se solicita contraseña: se ingresa sólo con el
                nombre de usuario.
            </p>

            <p class="form-label mb-1">Términos y condiciones de uso</p>
            <div class="terms-box" tabindex="0">
                El sistema es de uso exclusivo para socios mayores de edad. Los datos personales
                se recolectan y utilizan únicamente para identificar y gestionar los reclamos,
                conforme a la Ley 25.326 de Protección de Datos Personales. La información
                registrada podrá ser consultada por el personal autorizado del Club para el
                seguimiento y resolución de cada incidencia.
            </div>

            <div class="form-check terms-check mb-1">
                <input class="form-check-input" type="checkbox" id="acepta" name="acepta" value="1">
                <label class="form-check-label" for="acepta">
                    He leído y acepto los términos y condiciones de uso.
                </label>
            </div>

            <div class="auth-actions">
                <button type="submit" id="btnIngresar" class="btn btn-trebol w-100" disabled>Ingresar</button>
            </div>
        </form>

        <p class="auth-footnote">Prototipo funcional — Etapa MVP</p>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/conectividad.js') }}?v={{ filemtime(public_path('js/conectividad.js')) }}"></script>
    <script>
        (function () {
            var chk = document.getElementById('acepta');
            var btn = document.getElementById('btnIngresar');
            function sync() { btn.disabled = !chk.checked; }
            chk.addEventListener('change', sync);
            sync();
        })();
    </script>
</body>
</html>
