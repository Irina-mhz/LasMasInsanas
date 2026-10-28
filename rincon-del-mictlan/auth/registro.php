<?php
require_once __DIR__ . '/../includes/auth.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $nombre = trim($_POST['nombre'] ?? '');  $apellidos = trim($_POST['apellidos'] ?? '');
    $email  = trim($_POST['email'] ?? '');   $pass = $_POST['password'] ?? '';

    if ($nombre === '' || $apellidos === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Revisa tus datos.';
    } elseif (strlen($pass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        try {
            // IMPORTANTE: no se envía id_rol. La BD asigna "cliente" y un trigger
            // impide cualquier otro valor. Solo un admin puede cambiarlo después.
            db()->prepare("INSERT INTO usuarios (nombre, apellidos, email, password_hash) VALUES (?,?,?,?)")
                ->execute([$nombre, $apellidos, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            header('Location: login.php' . (isset($_GET['volver']) ? '?volver=' . urlencode($_GET['volver']) : '')); exit;
        } catch (PDOException $ex) {
            $error = $ex->getCode() === '23000' ? 'Ese correo ya está registrado.' : 'No se pudo crear la cuenta.';
        }
    }
}

$titulo = 'Registro · El Rincón del Mictlán';
require __DIR__ . '/../includes/cabecera.php';
?>
<main class="form-card">
  <h1>Crear cuenta</h1>
  <?php if ($error): ?><p class="alerta"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Nombre <input name="nombre" required maxlength="60"></label>
    <label>Apellidos <input name="apellidos" required maxlength="80"></label>
    <label>Correo <input type="email" name="email" required></label>
    <label>Contraseña (mín. 8) <input type="password" name="password" required minlength="8"></label>
    <button class="btn" type="submit">Registrarme</button>
  </form>
</main>
<?php require __DIR__ . '/../includes/pie.php';
