<?php
// CRUD de productos · CREAR y EDITAR (con subida de foto)   [RF02]
//   producto_form.php        -> nuevo producto
//   producto_form.php?id=7   -> editar el producto 7
require_once __DIR__ . '/../includes/auth.php';
requiere_admin();

$id = (int)($_GET['id'] ?? 0);
$p  = ['id_categoria' => '', 'nombre' => '', 'descripcion' => '', 'precio' => '', 'stock' => 0,
       'talla' => '', 'imagen' => null, 'activo' => 1];

if ($id) {
    $q = db()->prepare('SELECT * FROM productos WHERE id_producto = ?');
    $q->execute([$id]);
    $p = $q->fetch();
    if (!$p) { http_response_code(404); exit('Producto no encontrado.'); }
}
$categorias = db()->query('SELECT id_categoria, nombre FROM categorias ORDER BY orden')->fetchAll();
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    // --- Leer y validar ---
    $p['id_categoria'] = (int)($_POST['id_categoria'] ?? 0);
    $p['nombre']       = trim($_POST['nombre'] ?? '');
    $p['descripcion']  = trim($_POST['descripcion'] ?? '');
    $p['talla']        = $_POST['talla'] ?? '';
    $p['activo']       = isset($_POST['activo']) ? 1 : 0;
    $precio = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $stock  = filter_var($_POST['stock']  ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $p['precio'] = $_POST['precio'] ?? '';  $p['stock'] = $_POST['stock'] ?? 0;

    if ($p['nombre'] === '' || mb_strlen($p['nombre']) > 100)  $errores[] = 'El nombre es obligatorio (máx. 100 caracteres).';
    if (mb_strlen($p['descripcion']) > 255)                    $errores[] = 'La descripción no puede pasar de 255 caracteres.';
    if ($precio === false || $precio < 0)                      $errores[] = 'El precio debe ser un número mayor o igual a 0.';
    if ($stock === false)                                      $errores[] = 'El stock debe ser un entero mayor o igual a 0.';
    if (!in_array($p['id_categoria'], array_map('intval', array_column($categorias, 'id_categoria')), true)) $errores[] = 'Elige una categoría.';
    if (!in_array($p['talla'], ['', 'S', 'M', 'L', 'XL'], true)) $errores[] = 'Talla inválida.';

    // --- Foto (opcional) ---
    $nuevaImagen = null;
    $f = $_FILES['imagen'] ?? null;
    if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($f['error'] !== UPLOAD_ERR_OK)          $errores[] = 'No se pudo subir la imagen (¿pesa más de 2 MB?).';
        elseif ($f['size'] > 2 * 1024 * 1024)       $errores[] = 'La imagen no debe pesar más de 2 MB.';
        else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);   // se verifica el contenido real, no la extensión
            $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
            if (!$ext) $errores[] = 'Solo se permiten imágenes JPG, PNG o WebP.';
            else       $nuevaImagen = (slugify($p['nombre']) ?: 'producto') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        }
    }

    // --- Guardar ---
    if (!$errores) {
        try {
            if ($nuevaImagen && !move_uploaded_file($f['tmp_name'], DIR_PRODUCTOS . $nuevaImagen)) {
                throw new RuntimeException('No se pudo guardar la imagen. Revisa los permisos de assets/img/productos/.');
            }
            $imagenFinal = $nuevaImagen ?? $p['imagen'];
            $datos = [$p['id_categoria'], $p['nombre'], $p['descripcion'] ?: null, $precio, $stock,
                      $p['talla'] ?: null, $imagenFinal, $p['activo']];
            if ($id) {
                $datos[] = $id;
                db()->prepare('UPDATE productos SET id_categoria=?, nombre=?, descripcion=?, precio=?, stock=?, talla=?, imagen=?, activo=?
                               WHERE id_producto=?')->execute($datos);
                if ($nuevaImagen) borrar_imagen_producto($_POST['imagen_anterior'] ?? null);
                flash('Producto actualizado.');
            } else {
                db()->prepare('INSERT INTO productos (id_categoria,nombre,descripcion,precio,stock,talla,imagen,activo)
                               VALUES (?,?,?,?,?,?,?,?)')->execute($datos);
                flash('Producto creado.');
            }
            header('Location: productos.php'); exit;
        } catch (RuntimeException $ex) {
            $errores[] = $ex->getMessage();
        } catch (PDOException $ex) {
            $errores[] = 'Error de base de datos al guardar.';
        }
    }
}

$titulo = ($id ? 'Editar' : 'Nuevo') . ' producto';
require __DIR__ . '/../includes/cabecera.php';
?>
<main class="contenedor contenedor--ancho">
  <h1><?= $id ? 'Editar producto' : 'Nuevo producto' ?></h1>
  <?php $seccion = 'productos'; require __DIR__ . '/../includes/admin_menu.php'; ?>
  <?php foreach ($errores as $err): ?><p class="alerta"><?= e($err) ?></p><?php endforeach; ?>

  <form method="post" enctype="multipart/form-data" class="form-card form-card--ancho">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="imagen_anterior" value="<?= e($p['imagen']) ?>">
    <div class="form-grid">
      <label class="completo">Nombre
        <input name="nombre" required maxlength="100" value="<?= e($p['nombre']) ?>"></label>

      <label>Categoría
        <select name="id_categoria" required>
          <option value="">— Elige —</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= (int)$c['id_categoria'] ?>" <?= (int)$p['id_categoria'] === (int)$c['id_categoria'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select></label>

      <label>Talla (solo calaveritas)
        <select name="talla">
          <?php foreach (['' => '— Sin talla —', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL'] as $v => $t): ?>
            <option value="<?= $v ?>" <?= (string)$p['talla'] === (string)$v ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select></label>

      <label>Precio (MXN)
        <input type="number" name="precio" min="0" step="0.01" required value="<?= e((string)$p['precio']) ?>"></label>
      <label>Stock
        <input type="number" name="stock" min="0" step="1" required value="<?= e((string)$p['stock']) ?>"></label>

      <label class="completo">Descripción corta
        <textarea name="descripcion" maxlength="255" rows="2"><?= e($p['descripcion']) ?></textarea></label>

      <label class="completo">Foto (JPG, PNG o WebP · máx. 2 MB)
        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"></label>
      <?php if ($p['imagen']): ?>
        <div class="completo"><img class="miniatura miniatura--grande" src="<?= img_producto($p['imagen']) ?>" alt="Foto actual" width="120" height="120">
        <small>Foto actual. Si subes otra, reemplaza a esta.</small></div>
      <?php endif; ?>

      <label class="completo casilla"><input type="checkbox" name="activo" <?= $p['activo'] ? 'checked' : '' ?>> Visible en el catálogo</label>
    </div>
    <p><button class="btn" type="submit">Guardar</button> <a class="btn btn--borde" href="productos.php">Cancelar</a></p>
  </form>
</main>
<?php require __DIR__ . '/../includes/pie.php';
