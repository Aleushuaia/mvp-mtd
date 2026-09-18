<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// "El sistema en uso": antes se disparaba en cada GET de incidencias, lo que
// hacía que un request pagara sincrónicamente el procesamiento de todas las
// incidencias maduras acumuladas. Ahora corre en background vía `schedule:work`.
// Sin withoutOverlapping(): cada transición ya usa lockForUpdate() por incidencia,
// así que una ejecución solapada simplemente no encuentra nada para hacer.
Schedule::command('simulacion:ejecutar')->everyTenSeconds();
