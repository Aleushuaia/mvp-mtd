<?php

return [

    'paths' => [
        resource_path('views'),
    ],

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

    // Bind mount de Windows: comparar filemtime() en cada request (2 stat()
    // por vista incluida) es muy costoso. Se compila una vez y sólo se
    // vuelve a compilar corriendo `php artisan view:clear` a mano.
    'check_cache_timestamps' => false,

];
