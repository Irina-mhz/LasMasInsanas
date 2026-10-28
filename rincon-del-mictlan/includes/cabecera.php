<?php
// Variables opcionales antes de incluir:  $titulo (string)  $buscador (bool)
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/carrito.php';
$titulo   ??= 'El Rincón del Mictlán';
$buscador ??= false;
$u = usuario_actual();
$nCarrito = carrito_cantidad();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf" content="<?= csrf_token() ?>">
  <meta name="base-url" content="<?= BASE_URL ?>">
  <title><?= e($titulo) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/styles.css">
</head>
<body>
<header class="cabecera">
  <div class="cabecera__barra">
    <a class="bolsa" href="<?= BASE_URL ?>carrito.php" aria-label="Carrito">
      <!-- IMG-1 · Icono de bolsa: includes/config.php → IMG['bolsa'] -->
      <img src="<?= img('bolsa') ?>" alt="Carrito" width="32" height="32">
      <span id="contador-carrito" class="bolsa__n" <?= $nCarrito ? '' : 'hidden' ?>><?= $nCarrito ?></span>
    </a>

    <nav class="nav" aria-label="Principal">
      <a href="<?= BASE_URL ?>#ofrenda">Ofrenda</a>
      <!-- IMG-2 · Logo calavera: Ahora funciona como acceso al perfil -->
      <a href="<?= BASE_URL ?><?= $u ? 'mis_pedidos.php' : 'auth/login.php' ?>" title="Mi Perfil">
        <img class="logo" src="<?= img('logo') ?>" alt="Perfil del usuario" width="48" height="48">
      </a>
      <a href="<?= BASE_URL ?>#catalogo">Catálogo</a>
    </nav>

    <div class="cabecera__der">
      <?php if ($buscador): ?>
      <form class="buscador" role="search" onsubmit="return false">
        <input id="buscador" type="search" placeholder="Buscar productos…" aria-label="Buscar productos" autocomplete="off">
      </form>
      <?php endif; ?>
      <?php if ($u): ?>
        <?php if ($u['rol'] === 'admin'): ?><a class="enlace-sesion" href="<?= BASE_URL ?>admin/productos.php">Admin</a><?php endif; ?>
        <a class="enlace-sesion" href="<?= BASE_URL ?>mis_pedidos.php">Mis pedidos</a>
        <a class="enlace-sesion" href="<?= BASE_URL ?>auth/logout.php">Salir (<?= e($u['nombre']) ?>)</a>
      <?php else: ?>
        <a class="enlace-sesion" href="<?= BASE_URL ?>auth/login.php">Entrar</a>
      <?php endif; ?>
    </div>
  </div>
</header>
