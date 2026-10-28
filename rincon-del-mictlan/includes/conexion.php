<?php
// =====================================================================
//  CONEXIÓN A MySQL con PDO
//  Uso:  $pdo = db();   $pdo->prepare('SELECT ... WHERE id = ?')->execute([$id]);
// =====================================================================
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;               // una sola conexión por petición
    if ($pdo instanceof PDO) return $pdo;

    $host   = 'localhost';
    $puerto = 3307;                   // Cambiado a 3307 por la configuración de XAMPP
    $bd     = 'mictlan';
    $user   = 'root';                 // Usuario por defecto en XAMPP
    $clave  = '';                     // Contraseña vacía por defecto en XAMPP

    $pdo = new PDO("mysql:host=$host;port=$puerto;dbname=$bd;charset=utf8mb4", $user, $clave, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // errores como excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,                   // prepared statements reales
    ]);
    return $pdo;
}