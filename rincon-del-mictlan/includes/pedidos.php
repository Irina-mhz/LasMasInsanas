<?php
// =====================================================================
//  PEDIDOS: compra con transacción, cambios de estado y consultas
// =====================================================================
require_once __DIR__ . '/carrito.php';
require_once __DIR__ . '/pasarela_simulada.php';

/** Estados a los que se puede pasar desde cada estado. */
const TRANSICIONES_PEDIDO = [
    'pendiente' => ['pagado', 'cancelado'],
    'pagado'    => ['enviado', 'cancelado'],
    'enviado'   => [],
    'cancelado' => [],
];

/**
 * Convierte el carrito en un pedido PAGADO, todo o nada:
 *   1) bloquea las filas de los productos y verifica el stock (RN02)
 *   2) descuenta stock, crea el pedido y su detalle
 *   3) "cobra" en la pasarela simulada
 *   4) si todo salió bien confirma (COMMIT); si algo falla deshace TODO (ROLLBACK)
 * Lanza RuntimeException con un mensaje apto para mostrar al cliente.
 */
function procesar_compra(int $idUsuario, string $metodo, array $envio, array $pago): array
{
    $carrito = carrito_leer();
    if (!$carrito) throw new RuntimeException('Tu carrito está vacío.');
    ksort($carrito);                       // bloqueo siempre en el mismo orden = sin interbloqueos

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $sel = $pdo->prepare('SELECT id_producto, nombre, precio, stock, activo FROM productos WHERE id_producto = ? FOR UPDATE');
        $dec = $pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id_producto = ? AND stock >= ?');

        $lineas = []; $totalC = 0;
        foreach ($carrito as $idp => $cant) {
            $sel->execute([$idp]);
            $p = $sel->fetch();
            if (!$p || !$p['activo']) throw new RuntimeException('Un producto de tu carrito ya no está disponible. Revisa tu carrito.');
            if ((int)$p['stock'] < $cant) {
                throw new RuntimeException('«' . $p['nombre'] . '» ya no tiene suficiente stock (quedan ' . (int)$p['stock'] . ').');
            }
            $dec->execute([$cant, $idp, $cant]);
            if ($dec->rowCount() !== 1) throw new RuntimeException('No se pudo reservar «' . $p['nombre'] . '». Intenta de nuevo.');

            $cent = (int)round((float)$p['precio'] * 100);
            $lineas[] = [$idp, $cant, $cent];
            $totalC  += $cent * $cant;
        }

        $pdo->prepare("INSERT INTO pedidos (id_usuario, total, estado, metodo_pago, envio_nombre, envio_direccion, envio_ciudad, envio_cp, envio_telefono)
                       VALUES (?, ?, 'pendiente', ?, ?, ?, ?, ?, ?)")
            ->execute([$idUsuario, number_format($totalC / 100, 2, '.', ''), $metodo,
                       $envio['nombre'], $envio['direccion'], $envio['ciudad'], $envio['cp'], $envio['telefono'] ?: null]);
        $idPedido = (int)$pdo->lastInsertId();

        $det = $pdo->prepare('INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario) VALUES (?,?,?,?)');
        foreach ($lineas as [$idp, $cant, $cent]) {
            $det->execute([$idPedido, $idp, $cant, number_format($cent / 100, 2, '.', '')]);
        }

        // --- Cobro (simulado). Si lo rechazan, la excepción deshace stock, pedido y detalle ---
        $r = simular_pasarela($metodo, $pago, $totalC / 100);
        if (!$r['ok']) throw new RuntimeException($r['mensaje']);

        $pdo->prepare("UPDATE pedidos SET estado = 'pagado', referencia_pago = ?, ultimos4 = ?, fecha_pago = NOW() WHERE id_pedido = ?")
            ->execute([$r['referencia'], $r['ultimos4'], $idPedido]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e instanceof PDOException) {
            error_log('[mictlan] ' . $e->getMessage());        // detalle técnico solo en el log de PHP
            throw new RuntimeException('Ocurrió un error al procesar tu pedido. No se hizo ningún cargo. Intenta de nuevo.');
        }
        throw $e;
    }

    carrito_vaciar();
    return ['id' => $idPedido, 'referencia' => $r['referencia']];
}

/** Cambia el estado de un pedido (uso del administrador). Al cancelar, devuelve el stock. */
function pedido_cambiar_estado(int $idPedido, string $nuevo): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT estado FROM pedidos WHERE id_pedido = ? FOR UPDATE');
        $q->execute([$idPedido]);
        $actual = $q->fetchColumn();
        if ($actual === false) throw new RuntimeException('El pedido no existe.');
        if (!in_array($nuevo, TRANSICIONES_PEDIDO[$actual] ?? [], true)) {
            throw new RuntimeException("No se puede pasar de «$actual» a «$nuevo».");
        }
        if ($nuevo === 'cancelado') {
            $d = $pdo->prepare('SELECT id_producto, cantidad FROM detalle_pedido WHERE id_pedido = ?');
            $d->execute([$idPedido]);
            $up = $pdo->prepare('UPDATE productos SET stock = stock + ? WHERE id_producto = ?');
            foreach ($d->fetchAll() as $l) $up->execute([$l['cantidad'], $l['id_producto']]);
        }
        $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id_pedido = ?')->execute([$nuevo, $idPedido]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e instanceof PDOException) { error_log('[mictlan] ' . $e->getMessage()); throw new RuntimeException('Error de base de datos.'); }
        throw $e;
    }
}

/** Productos de varios pedidos en UNA consulta: [id_pedido => [filas...]] */
function pedido_items(array $ids): array
{
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $q = db()->prepare("SELECT d.id_pedido, d.id_producto, d.cantidad, d.precio_unitario, p.nombre, p.talla, p.imagen
                        FROM detalle_pedido d JOIN productos p ON p.id_producto = d.id_producto
                        WHERE d.id_pedido IN ($in) ORDER BY d.id_detalle");
    $q->execute(array_values($ids));
    $out = [];
    foreach ($q->fetchAll() as $r) $out[(int)$r['id_pedido']][] = $r;
    return $out;
}
