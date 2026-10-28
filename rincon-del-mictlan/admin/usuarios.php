<?php
// Panel de administración de usuarios y roles  (RN05: solo administradores)
require_once __DIR__ . '/../includes/auth.php';
requiere_admin();

$mensaje = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    try {
        // ÚNICA vía para cambiar roles: el procedimiento almacenado, que revisa
        // en la BD que quien lo ejecuta sea un administrador activo.
        $st = db()->prepare("CALL sp_cambiar_rol(?, ?, ?)");
        $st->execute([usuario_actual()['id'], (int)($_POST['id_usuario'] ?? 0), $_POST['rol'] ?? '']);
        $st->closeCursor();
        $mensaje = 'Rol actualizado correctamente.';
    } catch (PDOException $ex) {
        $error = $ex->errorInfo[2] ?? 'No se pudo cambiar el rol.';   // mensaje de la regla de negocio
    }
}

$usuarios = db()->query("SELECT u.id_usuario, u.nombre, u.apellidos, u.email, u.activo, r.nombre AS rol,
                                a.nombre AS asignado_por
                         FROM usuarios u
                         JOIN roles r ON r.id_rol = u.id_rol
                         LEFT JOIN usuarios a ON a.id_usuario = u.rol_asignado_por
                         ORDER BY u.id_usuario")->fetchAll();

$titulo = 'Admin · Usuarios';
require __DIR__ . '/../includes/cabecera.php';
?>
<main class="contenedor contenedor--ancho">
  <h1>Usuarios y roles</h1>
  <?php $seccion = 'usuarios'; require __DIR__ . '/../includes/admin_menu.php'; ?>
  <?php if ($mensaje): ?><p class="ok"><?= e($mensaje) ?></p><?php endif; ?>
  <?php if ($error):   ?><p class="alerta"><?= e($error) ?></p><?php endif; ?>

  <div class="tabla-scroll">
  <table class="tabla">
    <thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol actual</th><th>Asignado por</th><th>Cambiar rol</th></tr></thead>
    <tbody>
    <?php foreach ($usuarios as $f): ?>
      <tr>
        <td><?= (int)$f['id_usuario'] ?></td>
        <td><?= e($f['nombre'] . ' ' . $f['apellidos']) ?></td>
        <td><?= e($f['email']) ?></td>
        <td><strong><?= e($f['rol']) ?></strong></td>
        <td><?= e($f['asignado_por'] ?? '—') ?></td>
        <td>
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id_usuario" value="<?= (int)$f['id_usuario'] ?>">
            <select name="rol">
              <option value="cliente" <?= $f['rol']==='cliente'?'selected':'' ?>>cliente</option>
              <option value="admin"   <?= $f['rol']==='admin'?'selected':'' ?>>admin</option>
            </select>
            <button class="btn btn--chico" type="submit">Guardar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</main>
<?php require __DIR__ . '/../includes/pie.php';
