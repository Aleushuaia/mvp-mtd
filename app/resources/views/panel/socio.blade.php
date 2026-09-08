@extends('layouts.app')

@section('title', 'Panel de Socio')

@section('content')
    <div class="panel panel--welcome mb-4">
        <p class="welcome-eyebrow">Bienvenido/a</p>
        <h1 class="page-title h4 mb-1">{{ $usuario['apellido_nombres'] ?? $usuario['nombre_usuario'] }}</h1>
        <p class="text-muted mb-0">
            Socio N.º {{ $usuario['id_socio'] ?? 's/d' }}
        </p>
    </div>

    <div class="row g-3 g-md-4 panel-acciones">
        <div class="col-12 col-md-6">
            <a href="{{ route('incidencias.create') }}" class="accion-card" data-conectividad>
                <span class="accion-card__icon" aria-hidden="true">+</span>
                <span class="accion-card__title">Nueva Incidencia</span>
                <span class="accion-card__desc">Reportar un problema en el sector de quinchos y parrilleros.</span>
            </a>
        </div>
        <div class="col-12 col-md-6">
            <a href="{{ route('incidencias.index') }}" class="accion-card accion-card--alt" data-conectividad>
                <span class="accion-card__icon" aria-hidden="true">&#9776;</span>
                <span class="accion-card__title">Mis incidencias</span>
                <span class="accion-card__desc">Consultar el estado y el avance de mis reclamos.</span>
            </a>
        </div>
    </div>
@endsection
