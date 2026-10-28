<?php
require_once __DIR__ . '/../includes/auth.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $q = db()->prepare("SELECT u.id_usuario, u.nombre, u.password_hash, r.nombre AS rol
                        FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol
                        WHERE u.email = ? AND u.activo = 1");
    $q->execute([trim($_POST['email'] ?? '')]);
    $fila = $q->fetch();

    if ($fila && password_verify($_POST['password'] ?? '', $fila['password_hash'])) {
        session_regenerate_id(true);                       // evita fijación de sesión
        $_SESSION['usuario'] = ['id' => (int)$fila['id_usuario'], 'nombre' => $fila['nombre'], 'rol' => $fila['rol']];
        db()->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?")->execute([$fila['id_usuario']]);
        $destino = isset($_GET['volver']) ? ruta_segura($_GET['volver'])
                 : BASE_URL . ($fila['rol'] === 'admin' ? 'admin/productos.php' : '');
        header('Location: ' . $destino); exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}

$titulo = 'Iniciar sesión · El Rincón del Mictlán';
require __DIR__ . '/../includes/cabecera.php';
?>
<main class="form-card">
  <h1>Iniciar sesión</h1>
  <?php if ($error): ?><p class="alerta"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Correo <input type="email" name="email" required></label>
    <label>Contraseña <input type="password" name="password" required></label>
    <button class="btn" type="submit">Entrar</button>
  </form>
  <p>¿Sin cuenta? <a href="registro.php<?= isset($_GET['volver']) ? '?volver=' . urlencode($_GET['volver']) : '' ?>">Regístrate</a></p>
</main>
<?php require __DIR__ . '/../includes/pie.php';
