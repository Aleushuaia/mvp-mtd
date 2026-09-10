<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') · EcoReporte</title>
    <link rel="icon" type="image/png" href="{{ asset('img/trebol.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/trebol.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
</head>
<body class="app-body">

    @php($sesionUsuario = session('usuario'))

    <nav class="navbar app-navbar" aria-label="Navegación principal">
        <div class="container">
            <a class="navbar-brand" href="{{ route('post-login.destino') }}">
                <img src="{{ asset('img/trebol.png') }}" alt="EcoReporte" class="app-navbar__logo">
                <span class="app-navbar__brand">
                    <span class="app-navbar__title">EcoReporte</span>
                    <span class="app-navbar__subtitle">Gestión de Incidencias</span>
                    @if ($sesionUsuario)
                        <span class="app-navbar__rol">{{ $sesionUsuario['rol'] }}</span>
                    @endif
                </span>
            </a>

            @if ($sesionUsuario)
                <div class="dropdown ms-auto">
                    <button class="btn app-navbar__menu-toggle dropdown-toggle" type="button"
                            id="appMenu" data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="Abrir menú de usuario">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">Menú</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end app-navbar__menu" aria-labelledby="appMenu">
                        <li>
                            <button type="button" class="dropdown-item" disabled>
                                <i class="bi bi-person" aria-hidden="true"></i> Mi Perfil
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item" disabled>
                                <i class="bi bi-gear" aria-hidden="true"></i> Configuraciones
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Salir
                                </button>
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
        EcoReporte &middot; Gestión de Incidencias
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/conectividad.js') }}?v={{ filemtime(public_path('js/conectividad.js')) }}"></script>
    @stack('scripts')
</body>
</html>
