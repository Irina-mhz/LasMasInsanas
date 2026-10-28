<?php
// CRUD de categorías. El "slug" (la parte de la URL #cat-...) se fija al crear y ya no se edita,
// porque las etiquetas de la ofrenda del Home dependen de él.
require_once __DIR__ . '/../includes/auth.php';
requiere_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $id     = (int)($_POST['id_categoria'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $orden  = filter_var($_POST['orden'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 255]]);
    try {
        switch ($_POST['accion'] ?? '') {
            case 'crear':
            case 'editar':
                if ($nombre === '' || mb_strlen($nombre) > 60) throw new RuntimeException('El nombre es obligatorio (máx. 60 caracteres).');
                if ($orden === false)                          throw new RuntimeException('El orden debe ser un número de 1 a 255.');
                if ($_POST['accion'] === 'crear') {
                    $slug = slugify($nombre);
                    if ($slug === '') throw new RuntimeException('Nombre inválido.');
                    db()->prepare('INSERT INTO categorias (nombre, slug, orden) VALUES (?,?,?)')->execute([$nombre, $slug, $orden]);
                    flash('Categoría creada.');
                } else {
                    db()->prepare('UPDATE categorias SET nombre = ?, orden = ? WHERE id_categoria = ?')->execute([$nombre, $orden, $id]);
                    flash('Categoría actualizada.');
                }
                break;
            case 'eliminar':
                db()->prepare('DELETE FROM categorias WHERE id_categoria = ?')->execute([$id]);
                flash('Categoría eliminada.');
                break;
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    } catch (PDOException $ex) {
        flash($ex->getCode() === '23000'
            ? 'No se puede: ya existe una categoría igual, o la categoría todavía tiene productos.'
            : 'Error de base de datos.', 'error');
    }
    header('Location: categorias.php'); exit;
}

$categorias = db()->query("SELECT c.*, COUNT(p.id_producto) AS total
                           FROM categorias c LEFT JOIN productos p ON p.id_categoria = c.id_categoria
                           GROUP BY c.id_categoria, c.nombre, c.slug, c.orden
                           ORDER BY c.orden, c.id_categoria")->fetchAll();

$titulo = 'Admin · Categorías';
require __DIR__ . '/../includes/cabecera.php';
?>
<main class="contenedor contenedor--ancho">
  <h1>Categorías</h1>
  <?php $seccion = 'categorias'; require __DIR__ . '/../includes/admin_menu.php'; ?>
  <?php flash_mostrar(); ?>

  <form method="post" class="inline inline--wrap">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="accion" value="crear">
    <input name="nombre" placeholder="Nueva categoría" required maxlength="60" class="input-medio">
    <input name="orden" type="number" min="1" max="255" value="<?= count($categorias) + 1 ?>" class="input-mini" title="Orden">
    <button class="btn btn--chico" type="submit">+ Agregar</button>
  </form>

  <div class="tabla-scroll">
  <table class="tabla">
    <thead><tr><th>Orden</th><th>Nombre</th><th>Slug (enlace)</th><th>Productos</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($categorias as $c): ?>
      <tr>
        <td colspan="2">
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id_categoria" value="<?= (int)$c['id_categoria'] ?>">
            <input class="input-mini" type="number" name="orden" min="1" max="255" value="<?= (int)$c['orden'] ?>">
            <input class="input-medio" name="nombre" required maxlength="60" value="<?= e($c['nombre']) ?>">
            <button class="btn btn--chico btn--borde" name="accion" value="editar">Guardar</button>
          </form>
        </td>
        <td>#cat-<?= e($c['slug']) ?></td>
        <td><?= (int)$c['total'] ?></td>
        <td>
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id_categoria" value="<?= (int)$c['id_categoria'] ?>">
            <button class="btn btn--chico btn--peligro" name="accion" value="eliminar"
                    onclick="return confirm('¿Eliminar la categoría «<?= e(addslashes($c['nombre'])) ?>»?')">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</main>
<?php require __DIR__ . '/../includes/pie.php';
