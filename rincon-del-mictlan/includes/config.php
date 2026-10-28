<?php
// =====================================================================
//  CONFIGURACIÓN GENERAL
// =====================================================================

// Ruta del proyecto en el navegador (http://localhost + esto).
// Si tu carpeta se llama distinto, cámbialo aquí. Debe empezar y terminar con "/".
const BASE_URL = '/rincon-del-mictlan/';

// ---------------------------------------------------------------------
//  👉 AQUÍ VAN LOS NOMBRES DE TUS IMÁGENES FIJAS DEL SITIO
//  Los archivos deben estar dentro de:  assets/img/
//  Cambia SOLO el texto de la derecha por el nombre real de tu archivo.
// ---------------------------------------------------------------------
const IMG = [
    'logo'         => 'logo-calavera.png',        // calavera morada del centro del menú
    'bolsa'        => 'icono-bolsa.png',          // bolsita/carrito arriba a la izquierda
    'papel_picado' => 'banner-papel-picado.png',  // tira de papel picado de colores
    'ofrenda'      => 'ofrenda.png',              // la ilustración del altar (SIN etiquetas)
    'esqueleto'    => 'esqueleto.png',            // esqueleto decorativo (izquierda)
    'alebrije'     => 'decoracion-esquina.png',   // adorno inferior derecho
];

function img(string $clave): string { return BASE_URL . 'assets/img/' . IMG[$clave]; }
function img_producto(?string $archivo): string { return BASE_URL . 'assets/img/productos/' . ($archivo ?? 'sin-imagen.png'); }
function e(?string $t): string { return htmlspecialchars($t ?? '', ENT_QUOTES, 'UTF-8'); }

// Carpeta física donde se guardan las fotos subidas desde el panel admin
const DIR_PRODUCTOS = __DIR__ . '/../assets/img/productos/';

/** ★★★★☆ a partir de un promedio 0-5 */
function estrellas(float $prom): string {
    $llenas = (int) round($prom);
    return str_repeat('★', $llenas) . str_repeat('☆', 5 - $llenas);
}

/** "Figuras de Cerámica" -> "figuras-de-ceramica" */
function slugify(string $t): string {
    $t = strtr(mb_strtolower($t, 'UTF-8'), ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
}

function borrar_imagen_producto(?string $archivo): void {
    if (!$archivo) return;
    $ruta = DIR_PRODUCTOS . basename($archivo);   // basename evita rutas maliciosas
    if (is_file($ruta)) @unlink($ruta);
}
// Función para formatear precios en la tienda
if (!function_exists('dinero')) {
    function dinero($monto) {
        return '$' . number_format((float)$monto, 2);
    }
}
// Función para mostrar visualmente el estado de un pedido
if (!function_exists('etiqueta_estado')) {
    function etiqueta_estado($estado) {
        // Pone la primera letra en mayúscula (ej. "pagado" -> "Pagado")
        $texto = ucfirst($estado); 
        // Crea la etiqueta HTML con la clase CSS correspondiente
        return "<span class=\"etiqueta etiqueta--{$estado}\">{$texto}</span>";
    }
}
// Función para mostrar el nombre formal del método de pago
if (!function_exists('nombre_metodo')) {
    function nombre_metodo($metodo) {
        switch (strtolower($metodo)) {
            case 'tarjeta': return 'Tarjeta de Crédito / Débito';
            case 'efectivo': return 'Pago en Efectivo (OXXO)';
            case 'paypal': return 'PayPal';
            default: return ucfirst($metodo); // Por defecto, solo pone la mayúscula inicial
        }
    }
}