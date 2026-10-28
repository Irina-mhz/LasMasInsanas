<?php
// =====================================================================
//  CARRITO DE COMPRAS (guardado en la sesión del servidor)
//  Funciona para invitados y clientes (RN01). El stock se valida siempre (RN02).
//  Estructura en sesión:  $_SESSION['carrito'] = [ id_producto => cantidad ]
// =====================================================================
require_once __DIR__ . '/auth.php';

function carrito_leer(): array     { return array_map('intval', $_SESSION['carrito'] ?? []); }
function carrito_cantidad(): int   { return array_sum(carrito_leer()); }
function carrito_vaciar(): void    { $_SESSION['carrito'] = []; }

function carrito_producto(int $id): ?array
{
    $q = db()->prepare('SELECT id_producto, nombre, precio, stock, talla, imagen FROM productos WHERE id_producto = ? AND activo = 1');
    $q->execute([$id]);
    return $q->fetch() ?: null;
}

/** Suma unidades al carrito sin pasar del stock disponible (RN02). */
function carrito_agregar(int $id, int $cantidad = 1): void
{
    $p = carrito_producto($id);
    if (!$p)                    throw new RuntimeException('Ese producto ya no está disponible.');
    if ((int)$p['stock'] === 0) throw new RuntimeException('«' . $p['nombre'] . '» está agotado.');

    $c = carrito_leer();
    $nueva = ($c[$id] ?? 0) + max(1, $cantidad);
    if ($nueva > (int)$p['stock']) {
        throw new RuntimeException('Solo hay ' . (int)$p['stock'] . ' disponibles de «' . $p['nombre'] . '».');
    }
    $c[$id] = $nueva;
    $_SESSION['carrito'] = $c;
}

/** Fija la cantidad exacta de un producto (0 = quitarlo). */
function carrito_fijar(int $id, int $cantidad): void
{
    $c = carrito_leer();
    if ($cantidad <= 0) { unset($c[$id]); $_SESSION['carrito'] = $c; return; }

    $p = carrito_producto($id);
    if (!$p) { unset($c[$id]); $_SESSION['carrito'] = $c; throw new RuntimeException('Ese producto ya no está disponible.'); }
    if ($cantidad > (int)$p['stock']) {
        throw new RuntimeException('Solo hay ' . (int)$p['stock'] . ' disponibles de «' . $p['nombre'] . '».');
    }
    $c[$id] = $cantidad;
    $_SESSION['carrito'] = $c;
}

/**
 * Carrito listo para mostrar, con precios ACTUALES de la base de datos.
 * Quita productos agotados/ocultos y recorta cantidades que excedan el stock.
 * Devuelve ['items' => [...], 'total_c' => centavos, 'avisos' => [...]]
 * (se trabaja en centavos enteros para evitar errores de redondeo con decimales).
 */
function carrito_resumen(): array
{
    $c = carrito_leer();
    $items = []; $avisos = []; $total = 0;
    if (!$c) return ['items' => [], 'total_c' => 0, 'avisos' => []];

    $in = implode(',', array_fill(0, count($c), '?'));
    $q  = db()->prepare("SELECT id_producto, nombre, precio, stock, talla, imagen
                         FROM productos WHERE activo = 1 AND id_producto IN ($in)");
    $q->execute(array_keys($c));
    $prods = [];
    foreach ($q->fetchAll() as $p) $prods[(int)$p['id_producto']] = $p;

    foreach ($c as $id => $cant) {
        $p = $prods[$id] ?? null;
        if (!$p) {
            unset($c[$id]);
            $avisos[] = 'Un producto de tu carrito ya no está disponible y se quitó.';
            continue;
        }
        if ((int)$p['stock'] === 0) {
            unset($c[$id]);
            $avisos[] = '«' . $p['nombre'] . '» se agotó y se quitó de tu carrito.';
            continue;
        }
        if ($cant > (int)$p['stock']) {
            $cant = (int)$p['stock']; $c[$id] = $cant;
            $avisos[] = 'Ajustamos «' . $p['nombre'] . '» a ' . $cant . ' pieza(s), que es lo que hay en existencia.';
        }
        $cent = (int)round((float)$p['precio'] * 100);
       $items[] = $p + ['cantidad' => $cant, 'precio_c' => $cent, 'subtotal_c' => $cent * $cant];
        $total  += $cent * $cant;
    }
    $_SESSION['carrito'] = $c;
    return ['items' => $items, 'total_c' => $total, 'avisos' => $avisos];
}
