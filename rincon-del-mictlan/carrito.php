<?php
// CARRITO DE COMPRAS: ver, cambiar cantidades, quitar y vaciar
require_once __DIR__ . '/includes/carrito.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $id = (int)($_POST['id_producto'] ?? 0);
    try {
        switch ($_POST['accion'] ?? '') {
            case 'actualizar': carrito_fijar($id, (int)($_POST['cantidad'] ?? 0)); flash('Carrito actualizado.'); break;
            case 'quitar':     carrito_fijar($id, 0);                              flash('Producto quitado.');     break;
            case 'vaciar':     carrito_vaciar();                                   flash('Carrito vacío.');        break;
        }
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    } catch (PDOException $e) {
        flash('Error de base de datos.', 'error');
    }
    header('Location: carrito.php'); exit;
}

['items' => $items, 'total_c' => $totalC, 'avisos' => $avisos] = carrito_resumen();
if ($avisos) flash(implode(' ', $avisos), 'error');

$titulo = 'Tu carrito · El Rincón del Mictlán';
require __DIR__ . '/includes/cabecera.php';
?>
<main class="contenedor contenedor--ancho">
  <h1>Tu carrito</h1>
  <?php flash_mostrar(); ?>

  <?php if (!$items): ?>
    <div class="vacio">
      <p>Tu carrito está vacío 💀</p>
      <p><a class="btn" href="<?= BASE_URL ?>#catalogo">Ir al catálogo</a></p>
    </div>
  <?php else: ?>
    <div class="tabla-scroll">
    <table class="tabla">
      <thead><tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td class="celda-prod">
            <img class="miniatura" src="<?= img_producto($it['imagen']) ?>" alt="" width="48" height="48">
            <a href="<?= BASE_URL ?>producto.php?id=<?= (int)$it['id_producto'] ?>"><?= e($it['nombre']) ?><?= $it['talla'] ? ' · ' . e($it['talla']) : '' ?></a>
          </td>
          <td><?= dinero($it['precio_c'] / 100) ?></td>
          <td>
            <form method="post" class="inline">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="accion" value="actualizar">
              <input type="hidden" name="id_producto" value="<?= (int)$it['id_producto'] ?>">
              <input class="input-mini" type="number" name="cantidad" min="1" max="<?= (int)$it['stock'] ?>"
                     value="<?= (int)$it['cantidad'] ?>" aria-label="Cantidad" onchange="this.form.submit()">
              <button class="btn btn--chico btn--borde" type="submit">Actualizar</button>
            </form>
          </td>
          <td><?= dinero($it['subtotal_c'] / 100) ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="id_producto" value="<?= (int)$it['id_producto'] ?>">
              <button class="btn btn--chico btn--peligro" name="accion" value="quitar" type="submit">Quitar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="carrito-total">
      <span>Total (<?= array_sum(array_column($items, 'cantidad')) ?> artículos)</span>
      <strong><?= dinero($totalC / 100) ?> MXN</strong>
    </div>

    <div class="acciones acciones--fin">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <button class="btn btn--borde" name="accion" value="vaciar" type="submit" onclick="return confirm('¿Vaciar el carrito?')">Vaciar carrito</button>
      </form>
      <a class="btn btn--borde" href="<?= BASE_URL ?>#catalogo">Seguir comprando</a>
      <?php if (usuario_actual()): ?>
        <a class="btn" href="<?= BASE_URL ?>checkout.php">Proceder al pago</a>
      <?php else: ?>
        <a class="btn" href="<?= BASE_URL ?>auth/login.php?volver=checkout.php">Inicia sesión para pagar</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/pie.php';
