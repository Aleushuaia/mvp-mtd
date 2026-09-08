@extends('layouts.app')

@section('title', 'Panel')

@section('content')
    <div class="panel panel--welcome mb-4">
        <p class="welcome-eyebrow">Bienvenido/a</p>
        <h1 class="page-title h4 mb-1">{{ $usuario['apellido_nombres'] ?? $usuario['nombre_usuario'] }}</h1>
        <p class="text-muted mb-0">Perfil: {{ $usuario['rol'] }}</p>
    </div>

    <div class="panel">
        <h2 class="section-title">Panel de gestión</h2>
        <p class="text-muted mb-0">
            Las funciones de gestión de incidencias para el perfil
            <strong>{{ $usuario['rol'] }}</strong> se incorporarán en las próximas etapas del MVP.
        </p>
    </div>
@endsection
