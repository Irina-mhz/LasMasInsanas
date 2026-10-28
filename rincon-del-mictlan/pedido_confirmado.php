<?php
// CONFIRMACIÓN DE COMPRA  (pedido_confirmado.php?id=12)
require_once __DIR__ . '/includes/pedidos.php';
requiere_login();

$id = (int)($_GET['id'] ?? 0);
$esAdmin = es_admin_bd() ? 1 : 0;
$q = db()->prepare('SELECT * FROM pedidos WHERE id_pedido = ? AND (id_usuario = ? OR ? = 1)');   // solo el dueño (o un admin)
$q->execute([$id, usuario_actual()['id'], $esAdmin]);
$ped = $q->fetch();
if (!$ped) { http_response_code(404); exit('Pedido no encontrado.'); }
$items = pedido_items([$id])[$id] ?? [];

$titulo = 'Pedido #' . $id . ' · El Rincón del Mictlán';
require __DIR__ . '/includes/cabecera.php';
?>
<main class="contenedor">
  <div class="confirmacion">
    <div class="confirmacion__icono" aria-hidden="true">✔</div>
    <h1>¡Gracias por tu compra!</h1>
    <p>Pedido <strong>#<?= $id ?></strong> · <?= etiqueta_estado($ped['estado']) ?></p>
  </div>
  <p class="simulado">Pago simulado: no se realizó ningún cargo real.</p>

  <div class="tabla-scroll">
  <table class="tabla">
    <thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $it): ?>
      <tr>
        <td class="celda-prod"><img class="miniatura" src="<?= img_producto($it['imagen']) ?>" alt="" width="48" height="48">
            <?= e($it['nombre']) ?><?= $it['talla'] ? ' · ' . e($it['talla']) : '' ?></td>
        <td><?= (int)$it['cantidad'] ?></td>
        <td><?= dinero($it['precio_unitario']) ?></td>
        <td><?= dinero($it['precio_unitario'] * $it['cantidad']) ?></td>
        <td><a class="btn btn--chico btn--borde" href="<?= BASE_URL ?>producto.php?id=<?= (int)$it['id_producto'] ?>#opiniones">Opinar</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <p class="carrito-total"><span>Total pagado</span><strong><?= dinero($ped['total']) ?> MXN</strong></p>
  <ul class="datos-pedido">
    <li><strong>Método:</strong> <?= e(nombre_metodo($ped['metodo_pago'])) ?><?= $ped['ultimos4'] ? ' terminación ' . e($ped['ultimos4']) : '' ?></li>
    <li><strong>Referencia:</strong> <?= e($ped['referencia_pago'] ?? '—') ?></li>
    <li><strong>Enviar a:</strong> <?= e($ped['envio_nombre']) ?> · <?= e($ped['envio_direccion']) ?>, <?= e($ped['envio_ciudad']) ?>, C.P. <?= e($ped['envio_cp']) ?></li>
  </ul>

  <div class="acciones acciones--fin">
    <a class="btn btn--borde" href="<?= BASE_URL ?>mis_pedidos.php">Mis pedidos</a>
    <a class="btn" href="<?= BASE_URL ?>#catalogo">Seguir comprando</a>
  </div>
</main>
<?php require __DIR__ . '/includes/pie.php';
