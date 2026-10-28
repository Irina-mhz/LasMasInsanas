<?php /* Menú del panel admin. Antes de incluirlo define $seccion = 'productos' | 'categorias' | 'usuarios' */
$seccion ??= ''; ?>
<nav class="admin-menu" aria-label="Administración">
  <a href="<?= BASE_URL ?>admin/productos.php"  class="<?= $seccion === 'productos'  ? 'activo' : '' ?>">Productos</a>
  <a href="<?= BASE_URL ?>admin/pedidos.php"    class="<?= $seccion === 'pedidos'    ? 'activo' : '' ?>">Pedidos</a>
  <a href="<?= BASE_URL ?>admin/categorias.php" class="<?= $seccion === 'categorias' ? 'activo' : '' ?>">Categorías</a>
  <a href="<?= BASE_URL ?>admin/usuarios.php"   class="<?= $seccion === 'usuarios'   ? 'activo' : '' ?>">Usuarios y roles</a>
</nav>
