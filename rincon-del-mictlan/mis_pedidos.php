<?php
// HISTORIAL DE PEDIDOS DEL CLIENTE
require_once __DIR__ . '/includes/pedidos.php';
requiere_login('mis_pedidos.php');

$q = db()->prepare('SELECT * FROM pedidos WHERE id_usuario = ? ORDER BY fecha DESC, id_pedido DESC');
$q->execute([usuario_actual()['id']]);
$pedidos = $q->fetchAll();
$items = pedido_items(array_column($pedidos, 'id_pedido'));

$titulo = 'Mis pedidos · El Rincón del Mictlán';
require __DIR__ . '/includes/cabecera.php';
?>
<main class="contenedor">
  <h1>Mis pedidos</h1>
  <?php flash_mostrar(); ?>
  <?php if (!$pedidos): ?>
    <div class="vacio"><p>Aún no tienes pedidos.</p><p><a class="btn" href="<?= BASE_URL ?>#catalogo">Ir al catálogo</a></p></div>
  <?php endif; ?>

  <?php foreach ($pedidos as $p): ?>
    <details class="pedido">
      <summary>
        <strong>Pedido #<?= (int)$p['id_pedido'] ?></strong>
        <span><?= date('d/m/Y', strtotime($p['fecha'])) ?></span>
        <?= etiqueta_estado($p['estado']) ?>
        <span class="precio"><?= dinero($p['total']) ?></span>
      </summary>
      <ul class="lista-items">
        <?php foreach ($items[(int)$p['id_pedido']] ?? [] as $it): ?>
          <li>
            <span><?= (int)$it['cantidad'] ?> × <?= e($it['nombre']) ?><?= $it['talla'] ? ' · ' . e($it['talla']) : '' ?></span>
            <span><?= dinero($it['precio_unitario'] * $it['cantidad']) ?>
              <?php if (in_array($p['estado'], ['pagado', 'enviado'], true)): ?>
                · <a href="<?= BASE_URL ?>producto.php?id=<?= (int)$it['id_producto'] ?>#opiniones">Opinar</a>
              <?php endif; ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <p><small>Pago: <?= e(nombre_metodo($p['metodo_pago'])) ?><?= $p['ultimos4'] ? ' ···· ' . e($p['ultimos4']) : '' ?>
         · Ref. <?= e($p['referencia_pago'] ?? '—') ?></small></p>
      <?php if ($p['envio_direccion']): ?>
        <p><small>Envío a: <?= e($p['envio_nombre']) ?> · <?= e($p['envio_direccion']) ?>, <?= e($p['envio_ciudad']) ?>, C.P. <?= e($p['envio_cp']) ?></small></p>
      <?php endif; ?>
    </details>
  <?php endforeach; ?>
</main>
<?php require __DIR__ . '/includes/pie.php';
