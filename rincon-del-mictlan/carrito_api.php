<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/carrito.php';

// Forzamos el inicio de la sesión por si config.php no lo hace automáticamente en la API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Restauramos el código de estado HTTP para que Javascript detecte los bloqueos
function responder(array $d, int $codigo = 200): void { 
    http_response_code($codigo);
    echo json_encode($d); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
}

// 1. Validar que el usuario haya iniciado sesión (Lanzamos código 401: No autorizado)
if (!usuario_actual()) {
    responder(['ok' => false, 'mensaje' => 'Debes iniciar sesión para comprar.'], 401);
}

// 2. Validar token de seguridad (Lanzamos código 400: Petición incorrecta)
if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
    responder(['ok' => false, 'mensaje' => 'Sesión inválida. Recarga la página.'], 400);
}

// 3. Procesar el producto
try {
    carrito_agregar((int)($_POST['id_producto'] ?? 0));
    responder(['ok' => true, 'mensaje' => 'Producto agregado al carrito ✔', 'cantidad' => carrito_cantidad()], 200);
} catch (Throwable $e) {
    // Si la base de datos falla, lanzamos código 500
    responder(['ok' => false, 'mensaje' => 'Error BD: ' . $e->getMessage()], 500);
}