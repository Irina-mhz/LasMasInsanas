<?php
// =====================================================================
//  PASARELA DE PAGO SIMULADA  ·  NO COBRA NADA
//  Imita la respuesta de un banco para poder demostrar el flujo completo.
//  Para pasar a PayPal Sandbox / Stripe más adelante solo se reemplaza
//  la función simular_pasarela(); el resto del sistema no cambia.
//
//  Tarjetas de prueba (cualquier CVV y una fecha futura):
//    4242 4242 4242 4242  -> aprobada
//    4000 0000 0000 0002  -> rechazada (tarjeta declinada)
//    4000 0000 0000 9995  -> rechazada (fondos insuficientes)
// =====================================================================

/** Algoritmo de Luhn: detecta números de tarjeta mal escritos. */
function luhn_valido(string $numero): bool
{
    $suma = 0; $par = false;
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $d = (int)$numero[$i];
        if ($par) { $d *= 2; if ($d > 9) $d -= 9; }
        $suma += $d; $par = !$par;
    }
    return $suma % 10 === 0;
}

/** Valida los datos de la tarjeta (solo formato). Devuelve la lista de errores. */
function validar_tarjeta(string $numero, string $vence, string $cvv, string $titular): array
{
    $err = [];
    if (mb_strlen(trim($titular)) < 3)                               $err[] = 'Escribe el nombre como aparece en la tarjeta.';
    if (!preg_match('/^\d{13,19}$/', $numero) || !luhn_valido($numero)) $err[] = 'El número de tarjeta no es válido.';
    if (!preg_match('/^\d{3,4}$/', $cvv))                            $err[] = 'El CVV debe tener 3 o 4 dígitos.';

    if (!preg_match('~^(0[1-9]|1[0-2])\s*/\s*(\d{2})$~', $vence, $m)) {
        $err[] = 'La fecha de vencimiento debe tener el formato MM/AA.';
    } else {
        $mes = (int)$m[1]; $anio = 2000 + (int)$m[2];
        $hoyA = (int)date('Y'); $hoyM = (int)date('n');
        if ($anio < $hoyA || ($anio === $hoyA && $mes < $hoyM)) $err[] = 'La tarjeta está vencida.';
        elseif ($anio > $hoyA + 15)                             $err[] = 'La fecha de vencimiento no es válida.';
    }
    return $err;
}

/**
 * "Cobra" el pedido. Devuelve ['ok'=>bool, 'mensaje'=>string, 'referencia'=>string, 'ultimos4'=>?string]
 * $pago['numero'] solo se lee aquí, jamás se guarda ni se registra.
 */
function simular_pasarela(string $metodo, array $pago, float $total): array
{
    usleep(700000);                                   // simula la latencia de un banco real

    $ultimos4 = null;
    if ($metodo === 'tarjeta') {
        $ultimos4 = substr($pago['numero'] ?? '', -4);
        if ($ultimos4 === '0002') return ['ok' => false, 'mensaje' => 'Pago rechazado: tarjeta declinada por el banco (simulación).'];
        if ($ultimos4 === '9995') return ['ok' => false, 'mensaje' => 'Pago rechazado: fondos insuficientes (simulación).'];
    }
    $prefijo = ['tarjeta' => 'TAR', 'paypal' => 'PPL', 'google_play' => 'GPL'][$metodo] ?? 'PAG';
    return ['ok' => true, 'mensaje' => 'Pago aprobado (simulación).',
            'referencia' => $prefijo . '-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'ultimos4'   => $ultimos4];
}
