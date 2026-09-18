@php
    $mapaEstados = [
        'Borrador' => 'estado-badge--borrador',
        'Pendiente' => 'estado-badge--pendiente',
        'Confirmada' => 'estado-badge--confirmada',
        'En proceso' => 'estado-badge--en-proceso',
        'Resuelta' => 'estado-badge--resuelta',
        'Cancelada' => 'estado-badge--cancelada',
    ];
    $clase = $mapaEstados[$nombre] ?? 'estado-badge--borrador';
    $compacto = $compacto ?? false;
@endphp
<span class="badge estado-badge {{ $clase }} @if ($compacto) estado-badge--compacto @endif">{{ $nombre }}</span>
