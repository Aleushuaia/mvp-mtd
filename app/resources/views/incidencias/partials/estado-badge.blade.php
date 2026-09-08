@php
    $mapaEstados = [
        'Borrador' => 'text-bg-light text-dark border',
        'Pendiente' => 'text-bg-warning',
        'Confirmada' => 'text-bg-success',
        'En proceso' => 'text-bg-info',
        'Resuelta' => 'text-bg-primary',
        'Cancelada' => 'text-bg-secondary',
    ];
    $clase = $mapaEstados[$nombre] ?? 'text-bg-light text-dark border';
@endphp
<span class="badge estado-badge {{ $clase }}">{{ $nombre }}</span>
