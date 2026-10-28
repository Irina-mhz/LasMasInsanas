<?php
// =====================================================================
//  SESIÓN, ROLES Y PROTECCIÓN DE RUTAS  (RN01, RN05)
// =====================================================================
require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function usuario_actual(): ?array { return $_SESSION['usuario'] ?? null; }

/** Solo permite volver a páginas internas del proyecto (evita redirecciones a sitios externos). */
function ruta_segura(string $v): string
{
    return preg_match('~^[a-z0-9_\-/]+\.php(\?[a-z0-9_=&\-]*)?$~i', $v) ? BASE_URL . $v : BASE_URL;
}

/** Exige sesión iniciada. $volver = página a la que regresar tras el login (ej. 'checkout.php'). */
function requiere_login(string $volver = ''): void
{
    if (!usuario_actual()) {
        header('Location: ' . BASE_URL . 'auth/login.php' . ($volver !== '' ? '?volver=' . urlencode($volver) : ''));
        exit;
    }
}

/** ¿El usuario de la sesión es admin ACTIVO según la BASE DE DATOS? (no confía en la sesión) */
function es_admin_bd(): bool
{
    if (!usuario_actual()) return false;
    $q = db()->prepare("SELECT 1 FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol
                        WHERE u.id_usuario = ? AND r.nombre = 'admin' AND u.activo = 1");
    $q->execute([usuario_actual()['id']]);
    return (bool) $q->fetchColumn();
}

/** Ruta solo para administradores (RN05). */
function requiere_admin(): void
{
    requiere_login();
    if (!es_admin_bd()) { http_response_code(403); exit('403 · Acceso solo para administradores.'); }
}

/** Mensajes de una sola vez (se muestran tras redirigir) */
function flash(string $mensaje, string $tipo = 'ok'): void { $_SESSION['flash'] = ['m' => $mensaje, 't' => $tipo]; }
function flash_mostrar(): void
{
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash']; unset($_SESSION['flash']);
    echo '<p class="' . ($f['t'] === 'ok' ? 'ok' : 'alerta') . '">' . e($f['m']) . '</p>';
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function csrf_validar(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Token inválido.'); }
}
