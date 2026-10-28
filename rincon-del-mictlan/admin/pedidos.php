<?php
// ADMIN · Pedidos: ver detalle y cambiar estado (pendiente → pagado → enviado, o cancelado)
require_once __DIR__ . '/../includes/pedidos.php';
requiere_admin();

$filtro = $_GET['estado'] ?? '';
if (!isset(TRANSICIONES_PEDIDO[$filtro])) $filtro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    try {
        pedido_cambiar_estado((int)($_POST['id_pedido'] ?? 0), $_POST['estado'] ?? '');
        flash('Estado actualizado.' . (($_POST['estado'] ?? '') === 'cancelado' ? ' El stock fue devuelto al inventario.' : ''));
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    }
    header('Location: pedidos.php' . ($filtro ? '?estado=' . urlencode($filtro) : '')); exit;
}

$sql = 'SELECT pe.*, u.nombre, u.apellidos, u.email FROM pedidos pe JOIN usuarios u ON u.id_usuario = pe.id_usuario'
     . ($filtro ? ' WHERE pe.estado = ?' : '') . ' ORDER BY pe.fecha DESC, pe.id_pedido DESC LIMIT 100';
$q = db()->prepare($sql);
$q->execute($filtro ? [$filtro] : []);
$pedidos = $q->fetchAll();
$items = pedido_items(array_column($pedidos, 'id_pedido'));

$titulo = 'Admin · Pedidos';
require __DIR__ . '/../includes/cabecera.php';
?>
<main class="contenedor contenedor--ancho">
  <h1>Pedidos</h1>
  <?php $seccion = 'pedidos'; require __DIR__ . '/../includes/admin_menu.php'; ?>
  <?php flash_mostrar(); ?>

  <p class="filtros">Filtrar:
    <a href="pedidos.php" class="<?= $filtro === '' ? 'activo' : '' ?>">Todos</a>
    <?php foreach (array_keys(TRANSICIONES_PEDIDO) as $est): ?>
      <a href="pedidos.php?estado=<?= $est ?>" class="<?= $filtro === $est ? 'activo' : '' ?>"><?= ucfirst($est) ?></a>
    <?php endforeach; ?>
  </p>

  <?php if (!$pedidos): ?><p class="nota">No hay pedidos<?= $filtro ? ' con ese estado' : '' ?>.</p><?php endif; ?>

  <?php foreach ($pedidos as $p):
        $siguientes = TRANSICIONES_PEDIDO[$p['estado']]; ?>
    <details class="pedido">
      <summary>
        <strong>#<?= (int)$p['id_pedido'] ?></strong>
        <span><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></span>
        <span><?= e($p['nombre'] . ' ' . $p['apellidos']) ?></span>
        <?= etiqueta_estado($p['estado']) ?>
        <span class="precio"><?= dinero($p['total']) ?></span>
      </summary>

      <ul class="lista-items">
        <?php foreach ($items[(int)$p['id_pedido']] ?? [] as $it): ?>
          <li><span><?= (int)$it['cantidad'] ?> × <?= e($it['nombre']) ?><?= $it['talla'] ? ' · ' . e($it['talla']) : '' ?></span>
              <span><?= dinero($it['precio_unitario'] * $it['cantidad']) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <p><small>Cliente: <?= e($p['email']) ?> · Pago: <?= e(nombre_metodo($p['metodo_pago'])) ?><?= $p['ultimos4'] ? ' ···· ' . e($p['ultimos4']) : '' ?>
         · Ref. <?= e($p['referencia_pago'] ?? '—') ?></small></p>
      <?php if ($p['envio_direccion']): ?>
        <p><small>Enviar a: <?= e($p['envio_nombre']) ?> · <?= e($p['envio_direccion']) ?>, <?= e($p['envio_ciudad']) ?>, C.P. <?= e($p['envio_cp']) ?>
           <?= $p['envio_telefono'] ? ' · Tel. ' . e($p['envio_telefono']) : '' ?></small></p>
      <?php endif; ?>

      <?php if ($siguientes): ?>
        <form method="post" class="inline">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="id_pedido" value="<?= (int)$p['id_pedido'] ?>">
          <select name="estado" aria-label="Nuevo estado">
            <?php foreach ($siguientes as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn--chico" type="submit"
                  onclick="return this.form.estado.value !== 'cancelado' || confirm('Al cancelar se devuelve el stock. ¿Continuar?')">Cambiar estado</button>
        </form>
      <?php endif; ?>
    </details>
  <?php endforeach; ?>
</main>
<?php require __DIR__ . '/../includes/pie.php';
