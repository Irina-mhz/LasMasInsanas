<?php
// =====================================================================
//  PÁGINA PRINCIPAL: Ofrenda interactiva + Catálogo con buscador
// =====================================================================
require_once __DIR__ . '/includes/auth.php';

// --- Datos desde MySQL -------------------------------------------------
$errorBD = null; $secciones = [];
try {
    $filas = db()->query("SELECT * FROM v_catalogo ORDER BY orden, id_producto")->fetchAll();
    foreach ($filas as $f) {                       // agrupa por categoría
        $secciones[$f['slug']]['nombre']      = $f['categoria'];
        $secciones[$f['slug']]['productos'][] = $f;
    }
} catch (PDOException $ex) {
    $errorBD = 'No se pudo conectar con la base de datos. Revisa includes/conexion.php y que MySQL esté encendido.';
}

$titulo = 'El Rincón del Mictlán';
$buscador = true;
require __DIR__ . '/includes/cabecera.php';
?>

<!-- IMG-3 · Papel picado: nombre del archivo en includes/config.php → IMG['papel_picado'] -->
<img class="papel-picado" src="<?= img('papel_picado') ?>" alt="" width="1600" height="90">

<main>
<!-- ================= OFRENDA INTERACTIVA ================= -->
<section id="ofrenda" class="seccion-ofrenda">
  <!-- IMG-5 · Esqueleto decorativo: includes/config.php → IMG['esqueleto'] -->
  <img class="deco deco--esqueleto" src="<?= img('esqueleto') ?>" alt="" width="180" height="220">

  <h1 class="titulo-sitio">El Rincón del Mictlán</h1>

  <div class="ofrenda">
    <!-- IMG-4 · Ilustración del altar: includes/config.php → IMG['ofrenda'] -->
    <img class="ofrenda__img" src="<?= img('ofrenda') ?>" alt="Ofrenda de Día de Muertos" width="520" height="440">

    <!--
      Cada etiqueta es un ANCHOR LINK al catálogo (href="#cat-SLUG").
      Para MOVERLAS ajusta en style=""  --y (altura, %)  y  --dx (distancia al centro, %).
      Cambia izq/der según el lado. Para cambiar el destino modifica el href.
    -->
    <a class="etiqueta etiqueta--izq" style="--y:22%; --dx:14%" href="#cat-marcos">Marcos de fotos</a>
    <a class="etiqueta etiqueta--izq" style="--y:45%; --dx:14%" href="#cat-calaveritas">Calaveritas</a>
    <a class="etiqueta etiqueta--izq" style="--y:68%; --dx:20%" href="#cat-estantes">Estantes</a>

    <a class="etiqueta etiqueta--der" style="--y:30%; --dx:12%" href="#cat-flores">Flores</a>
    <a class="etiqueta etiqueta--der" style="--y:46%; --dx:17%" href="#cat-figuras-ceramica">Figuras de cerámica</a>
    <a class="etiqueta etiqueta--der" style="--y:62%; --dx:14%" href="#cat-velas">Velas</a>
    <a class="etiqueta etiqueta--der" style="--y:86%; --dx:12%" href="#cat-papel-picado">Papel picado</a>
  </div>

  <!-- IMG-6 · Adorno inferior derecho: includes/config.php → IMG['alebrije'] -->
  <img class="deco deco--esquina" src="<?= img('alebrije') ?>" alt="" width="150" height="150">
</section>

<!-- ================= CATÁLOGO ================= -->
<section id="catalogo" class="catalogo">
  <?php if ($errorBD): ?>
    <p class="alerta"><?= e($errorBD) ?></p>
  <?php endif; ?>

  <p id="sin-resultados" class="sin-resultados" hidden>No encontramos productos con esa búsqueda 💀</p>

  <?php foreach ($secciones as $slug => $sec): ?>
  <section id="cat-<?= e($slug) ?>" class="categoria" data-categoria>
    <h2><?= e($sec['nombre']) ?></h2>

    <div class="carrusel">
      <button class="flecha" type="button" data-dir="-1" aria-label="Anterior">‹</button>
      <div class="carrusel__pista">
        <?php foreach ($sec['productos'] as $p):
              $agotado = (int)$p['stock'] === 0; ?>
        <article class="tarjeta"
                 data-id="<?= (int)$p['id_producto'] ?>"
                 data-stock="<?= (int)$p['stock'] ?>"
                 data-busqueda="<?= e($p['nombre'] . ' ' . $p['categoria'] . ' ' . $p['descripcion'] . ' ' . $p['talla']) ?>">
          <a class="tarjeta__foto" href="<?= BASE_URL ?>producto.php?id=<?= (int)$p['id_producto'] ?>">
            <!-- IMG-7 · Foto del producto: el nombre sale de la columna `imagen` en MySQL
                 (archivo dentro de assets/img/productos/) -->
            <img src="<?= img_producto($p['imagen']) ?>" alt="<?= e($p['nombre']) ?>" width="220" height="220" loading="lazy">
          </a>
          <div class="estrellas" role="img"
               aria-label="Calificación <?= e((string)$p['promedio']) ?> de 5 (<?= (int)$p['total_resenas'] ?> reseñas)">
            <?= estrellas((float)$p['promedio']) ?> <small>(<?= (int)$p['total_resenas'] ?>)</small>
          </div>
          <h3><a href="<?= BASE_URL ?>producto.php?id=<?= (int)$p['id_producto'] ?>"><?= e($p['nombre']) ?><?= $p['talla'] ? ' · ' . e($p['talla']) : '' ?></a></h3>
          <p class="precio">$<?= number_format((float)$p['precio'], 2) ?></p>
          <button class="btn btn--chico" type="button" data-agregar <?= $agotado ? 'disabled' : '' ?>>
            <?= $agotado ? 'Agotado' : 'Agregar' ?>
          </button>
        </article>
        <?php endforeach; ?>
      </div>
      <button class="flecha" type="button" data-dir="1" aria-label="Siguiente">›</button>
    </div>
  </section>
  <?php endforeach; ?>
</section>
</main>

<div id="aviso" class="aviso" role="status" hidden></div>
<?php require __DIR__ . '/includes/pie.php';
