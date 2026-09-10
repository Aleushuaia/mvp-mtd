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
@endphp
<span class="badge estado-badge {{ $clase }}">{{ $nombre }}</span>
