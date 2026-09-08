<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') · Gestión de Incidencias</title>
    <link rel="icon" type="image/png" href="{{ asset('img/trebol.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/trebol.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
</head>
<body class="app-body">

    @php($sesionUsuario = session('usuario'))

    <nav class="navbar navbar-expand-lg app-navbar">
        <div class="container">
            <a class="navbar-brand" href="{{ route('post-login.destino') }}">
                <img src="{{ asset('img/trebol.png') }}" alt="Club El Trébol" class="app-navbar__logo">
                <span class="app-navbar__brand">
                    <span class="app-navbar__title">Gestión de Incidencias</span>
                    @if ($sesionUsuario)
                        <span class="app-navbar__rol">{{ $sesionUsuario['rol'] }}</span>
                    @endif
                </span>
            </a>

            @if ($sesionUsuario)
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#appNav" aria-controls="appNav"
                        aria-expanded="false" aria-label="Abrir menú">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="appNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('post-login.destino') }}">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="nav-link nav-link--logout btn btn-link text-decoration-none">Salir</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endif
        </div>
    </nav>

    <main class="app-main">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer class="app-footer">
        Club Social y Deportivo &laquo;El Trébol&raquo; &middot; Gestión de Incidencias
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/conectividad.js') }}?v={{ filemtime(public_path('js/conectividad.js')) }}"></script>
    @stack('scripts')
</body>
</html>
