<?php
// DETALLE DE PRODUCTO + OPINIONES (RF06, RN03) + RECOMENDADOS (RF07)
//   producto.php?id=3
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$u  = usuario_actual();

// =================== ACCIONES (guardar / eliminar opinión) ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $idp    = (int)($_POST['id_producto'] ?? 0);
    $volver = BASE_URL . 'producto.php?id=' . $idp . '#opiniones';
    if (!$u) { header('Location: ' . BASE_URL . 'auth/login.php'); exit; }

    try {
        switch ($_POST['accion'] ?? '') {
            case 'guardar_resena':
                $cal = filter_var($_POST['calificacion'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
                $com = trim($_POST['comentario'] ?? '');
                if ($cal === false)         throw new RuntimeException('Elige de 1 a 5 estrellas.');
                if (mb_strlen($com) > 500)  throw new RuntimeException('El comentario no puede pasar de 500 caracteres.');
                $com = $com === '' ? null : $com;

                $q = db()->prepare('SELECT 1 FROM resenas WHERE id_producto = ? AND id_usuario = ?');
                $q->execute([$idp, $u['id']]);
                if ($q->fetchColumn()) {          // ya opinó: se edita su opinión (una por persona)
                    db()->prepare('UPDATE resenas SET calificacion = ?, comentario = ?, fecha = NOW()
                                   WHERE id_producto = ? AND id_usuario = ?')->execute([$cal, $com, $idp, $u['id']]);
                    flash('Tu opinión se actualizó.');
                } else {                          // el trigger de la BD exige una compra pagada (RN03)
                    db()->prepare('INSERT INTO resenas (id_producto, id_usuario, calificacion, comentario) VALUES (?,?,?,?)')
                        ->execute([$idp, $u['id'], $cal, $com]);
                    flash('¡Gracias por tu opinión!');
                }
                break;

            case 'eliminar_resena':   // el dueño borra la suya; el admin puede borrar cualquiera
                $esAdmin = es_admin_bd() ? 1 : 0;
                db()->prepare('DELETE FROM resenas WHERE id_resena = ? AND (id_usuario = ? OR ? = 1)')
                    ->execute([(int)($_POST['id_resena'] ?? 0), $u['id'], $esAdmin]);
                flash('Opinión eliminada.');
                break;
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    } catch (PDOException $ex) {
        // 45000 = mensajes de reglas de negocio de la BD (ej. "Solo puedes reseñar productos que ya compraste")
        flash($ex->getCode() === '45000' ? ($ex->errorInfo[2] ?? 'Acción no permitida.') : 'No se pudo guardar tu opinión.', 'error');
    }
    header('Location: ' . $volver); exit;
}

// =================== DATOS ===================
$q = db()->prepare('SELECT * FROM v_catalogo WHERE id_producto = ?');
$q->execute([$id]);
$p = $q->fetch();

if (!$p) {
    http_response_code(404);
    $titulo = 'Producto no encontrado';
    require __DIR__ . '/includes/cabecera.php';
    echo '<main class="contenedor"><h1>Producto no encontrado</h1><p><a href="' . BASE_URL . '#catalogo">← Volver al catálogo</a></p></main>';
    require __DIR__ . '/includes/pie.php'; exit;
}

$esAdmin = $u && es_admin_bd();
$mia = null; $puede = false;
if ($u) {
    $q = db()->prepare('SELECT * FROM resenas WHERE id_producto = ? AND id_usuario = ?');
    $q->execute([$id, $u['id']]);
    $mia = $q->fetch() ?: null;

    $q = db()->prepare("SELECT 1 FROM pedidos pe JOIN detalle_pedido d ON d.id_pedido = pe.id_pedido
                        WHERE pe.id_usuario = ? AND d.id_producto = ? AND pe.estado IN ('pagado','enviado') LIMIT 1");
    $q->execute([$u['id'], $id]);
    $puede = (bool)$q->fetchColumn();
}

$q = db()->prepare('SELECT r.*, us.nombre, us.apellidos FROM resenas r JOIN usuarios us ON us.id_usuario = r.id_usuario
                    WHERE r.id_producto = ? ORDER BY r.fecha DESC LIMIT 30');
$q->execute([$id]);
$opiniones = $q->fetchAll();

$q = db()->prepare('SELECT * FROM v_catalogo WHERE slug = ? AND id_producto <> ? ORDER BY RAND() LIMIT 4');   // RF07
$q->execute([$p['slug'], $id]);
$relacionados = $q->fetchAll();

$agotado = (int)$p['stock'] === 0;
$titulo  = $p['nombre'] . ' · El Rincón del Mictlán';
require __DIR__ . '/includes/cabecera.php';
?>
<main>
  <p class="migas"><a href="<?= BASE_URL ?>#cat-<?= e($p['slug']) ?>">← <?= e($p['categoria']) ?></a></p>

  <!-- data-id y data-stock los usa el botón "Agregar" (main.js) -->
  <section class="detalle" data-id="<?= (int)$p['id_producto'] ?>" data-stock="<?= (int)$p['stock'] ?>">
    <div class="detalle__foto">
      <img src="<?= img_producto($p['imagen']) ?>" alt="<?= e($p['nombre']) ?>" width="380" height="380">
    </div>
    <div>
      <h1><?= e($p['nombre']) ?></h1>
      <div class="estrellas detalle__estrellas" role="img" aria-label="Calificación <?= e((string)$p['promedio']) ?> de 5">
        <?= estrellas((float)$p['promedio']) ?>
        <a href="#opiniones"><small><?= (int)$p['total_resenas'] ?> opiniones</small></a>
      </div>
      <p class="precio">$<?= number_format((float)$p['precio'], 2) ?> MXN</p>
      <?php if ($p['talla']): ?><p><strong>Talla:</strong> <?= e($p['talla']) ?></p><?php endif; ?>
      <p><?= e($p['descripcion']) ?></p>
      <p class="stock"><?= $agotado ? 'Sin existencias' : (int)$p['stock'] . ' disponibles' ?></p>
      <button class="btn" type="button" data-agregar <?= $agotado ? 'disabled' : '' ?>><?= $agotado ? 'Agotado' : 'Agregar al carrito' ?></button>
    </div>
  </section>

  <!-- ================= OPINIONES ================= -->
  <section id="opiniones" class="opiniones">
    <h2>Opiniones</h2>
    <?php flash_mostrar(); ?>

    <?php if (!$u): ?>
      <p class="nota">¿Compraste este producto? <a href="<?= BASE_URL ?>auth/login.php">Inicia sesión</a> para dejar tu opinión.</p>
    <?php elseif (!$puede && !$mia): ?>
      <p class="nota">Solo quienes ya compraron este producto pueden opinar.</p>
    <?php else: ?>
      <form method="post" class="form-opinion">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="accion" value="guardar_resena">
        <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
        <h3><?= $mia ? 'Editar mi opinión' : 'Deja tu opinión' ?></h3>
        <div class="estrellas-input" role="radiogroup" aria-label="Calificación">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" id="e<?= $i ?>" name="calificacion" value="<?= $i ?>" <?= $mia && (int)$mia['calificacion'] === $i ? 'checked' : '' ?> required>
            <label for="e<?= $i ?>" title="<?= $i ?> estrellas">★</label>
          <?php endfor; ?>
        </div>
        <textarea name="comentario" maxlength="500" rows="3" placeholder="Cuéntanos qué te pareció (opcional)"><?= e($mia['comentario'] ?? '') ?></textarea>
        <button class="btn btn--chico" type="submit"><?= $mia ? 'Actualizar' : 'Publicar' ?></button>
      </form>
    <?php endif; ?>

    <?php if (!$opiniones): ?>
      <p class="nota">Aún no hay opiniones. ¡Sé el primero!</p>
    <?php endif; ?>

    <?php foreach ($opiniones as $r):
          $esMia = $u && (int)$r['id_usuario'] === (int)$u['id']; ?>
      <article class="opinion">
        <div class="opinion__cab">
          <strong><?= e($r['nombre'] . ' ' . mb_substr($r['apellidos'], 0, 1) . '.') ?></strong>
          <?php if ($esMia): ?><span class="etiqueta-tuya">Tu opinión</span><?php endif; ?>
          <span class="estrellas"><?= estrellas((float)$r['calificacion']) ?></span>
          <small><?= date('d/m/Y', strtotime($r['fecha'])) ?></small>
        </div>
        <?php if ($r['comentario']): ?><p><?= nl2br(e($r['comentario'])) ?></p><?php endif; ?>
        <?php if ($esMia || $esAdmin): ?>
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" value="eliminar_resena">
            <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
            <input type="hidden" name="id_resena" value="<?= (int)$r['id_resena'] ?>">
            <button class="btn btn--chico btn--peligro" type="submit" onclick="return confirm('¿Eliminar esta opinión?')">Eliminar</button>
          </form>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>

  <!-- ================= RECOMENDADOS (RF07) ================= -->
  <?php if ($relacionados): ?>
  <section class="opiniones">
    <h2>Te puede interesar</h2>
    <div class="carrusel__pista">
      <?php foreach ($relacionados as $r): ?>
        <article class="tarjeta">
          <a class="tarjeta__foto" href="producto.php?id=<?= (int)$r['id_producto'] ?>">
            <img src="<?= img_producto($r['imagen']) ?>" alt="<?= e($r['nombre']) ?>" width="220" height="220" loading="lazy">
          </a>
          <div class="estrellas"><?= estrellas((float)$r['promedio']) ?></div>
          <h3><a href="producto.php?id=<?= (int)$r['id_producto'] ?>"><?= e($r['nombre']) ?></a></h3>
          <p class="precio">$<?= number_format((float)$r['precio'], 2) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</main>
<div id="aviso" class="aviso" role="status" hidden></div>
<?php require __DIR__ . '/includes/pie.php';
