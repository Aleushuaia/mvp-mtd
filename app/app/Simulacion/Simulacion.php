<?php

namespace App\Simulacion;

/**
 * Contrato común de las simulaciones del MVP.
 *
 * Una "simulación" reemplaza a un actor o proceso externo que en producción
 * ocurriría de verdad (un operador asignando un responsable, una pasarela de
 * pago respondiendo, la API de socios, etc.). Todas exponen `ejecutar()` y
 * devuelven cuántos elementos procesaron.
 */
interface Simulacion
{
    public function ejecutar(): int;
}
