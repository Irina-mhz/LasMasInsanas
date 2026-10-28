<?php
// CRUD de productos · LISTA + acciones rápidas (stock, ocultar, eliminar)   [RF02]
require_once __DIR__ . '/../includes/auth.php';
requiere_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $id = (int)($_POST['id_producto'] ?? 0);
    try {
        switch ($_POST['accion'] ?? '') {
            case 'stock':
                $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                if ($stock === false) throw new RuntimeException('El stock debe ser un número entero de 0 en adelante.');
                db()->prepare('UPDATE productos SET stock = ? WHERE id_producto = ?')->execute([$stock, $id]);
                flash('Stock actualizado.');
                break;

            case 'toggle':   // mostrar / ocultar en el catálogo
                db()->prepare('UPDATE productos SET activo = 1 - activo WHERE id_producto = ?')->execute([$id]);
                flash('Visibilidad actualizada.');
                break;

            case 'eliminar':
                $q = db()->prepare('SELECT imagen FROM productos WHERE id_producto = ?');
                $q->execute([$id]);
                $imagen = $q->fetchColumn();
                try {
                    db()->prepare('DELETE FROM productos WHERE id_producto = ?')->execute([$id]);
                    borrar_imagen_producto($imagen ?: null);
                    flash('Producto eliminado.');
                } catch (PDOException $ex) {
                    if ($ex->getCode() !== '23000') throw $ex;
                    // Ya fue vendido: no se puede borrar sin romper el historial de pedidos
                    db()->prepare('UPDATE productos SET activo = 0 WHERE id_producto = ?')->execute([$id]);
                    flash('Ese producto ya tiene pedidos, así que no se puede borrar. Se ocultó del catálogo.', 'error');
                }
                break;
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    } catch (PDOException $ex) {
        flash('Error de base de datos.', 'error');
    }
    header('Location: productos.php'); exit;
}

$productos = db()->query("SELECT p.*, c.nombre AS categoria
                          FROM productos p JOIN categorias c ON c.id_categoria = p.id_categoria
                          ORDER BY c.orden, p.id_producto")->fetchAll();

$titulo = 'Admin · Productos';
require __DIR__ . '/../includes/cabecera.php';
?>
<main class="contenedor contenedor--ancho">
  <h1>Productos</h1>
  <?php $seccion = 'productos'; require __DIR__ . '/../includes/admin_menu.php'; ?>
  <?php flash_mostrar(); ?>

  <p><a class="btn btn--chico" href="producto_form.php">+ Nuevo producto</a></p>

  <div class="tabla-scroll">
  <table class="tabla">
    <thead><tr><th>Foto</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($productos as $p): ?>
      <tr>
        <td><img class="miniatura" src="<?= img_producto($p['imagen']) ?>" alt="" width="48" height="48"></td>
        <td><?= e($p['nombre']) ?><?= $p['talla'] ? ' · ' . e($p['talla']) : '' ?></td>
        <td><?= e($p['categoria']) ?></td>
        <td>$<?= number_format((float)$p['precio'], 2) ?></td>
        <td>
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" value="stock">
            <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
            <input class="input-mini" type="number" name="stock" min="0" value="<?= (int)$p['stock'] ?>">
            <button class="btn btn--chico btn--borde" type="submit">OK</button>
          </form>
        </td>
        <td class="<?= $p['activo'] ? '' : 'estado-off' ?>"><?= $p['activo'] ? 'Visible' : 'Oculto' ?></td>
        <td class="acciones">
          <a class="btn btn--chico" href="producto_form.php?id=<?= (int)$p['id_producto'] ?>">Editar</a>
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
            <button class="btn btn--chico btn--borde" name="accion" value="toggle"><?= $p['activo'] ? 'Ocultar' : 'Mostrar' ?></button>
            <button class="btn btn--chico btn--peligro" name="accion" value="eliminar"
                    onclick="return confirm('¿Eliminar «<?= e(addslashes($p['nombre'])) ?>»? Esta acción no se puede deshacer.')">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</main>
<?php require __DIR__ . '/../includes/pie.php';
