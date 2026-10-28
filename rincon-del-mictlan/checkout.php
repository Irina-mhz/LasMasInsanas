<?php
// CHECKOUT: datos de envío + método de pago (SIMULADO)      [RF04, RN01, RN02]
require_once __DIR__ . '/includes/pedidos.php';
requiere_login('checkout.php');                      // RN01: para pagar hay que iniciar sesión

$u = usuario_actual();
['items' => $items, 'total_c' => $totalC, 'avisos' => $avisos] = carrito_resumen();

if (!$items || ($avisos && $_SERVER['REQUEST_METHOD'] !== 'POST')) {
    flash($items ? implode(' ', $avisos) : 'Tu carrito está vacío.', $items ? 'error' : 'ok');
    header('Location: carrito.php'); exit;
}

$errores = [];
$metodo  = $_POST['metodo'] ?? 'tarjeta';
$envio   = ['nombre' => $u['nombre'], 'direccion' => '', 'ciudad' => '', 'cp' => '', 'telefono' => ''];
$titular = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    foreach ($envio as $k => $_) $envio[$k] = trim($_POST['envio_' . $k] ?? '');
    if ($envio['nombre'] === '' || mb_strlen($envio['nombre']) > 100)        $errores[] = 'Escribe el nombre de quien recibe.';
    if ($envio['direccion'] === '' || mb_strlen($envio['direccion']) > 200)  $errores[] = 'Escribe la dirección de envío.';
    if ($envio['ciudad'] === '' || mb_strlen($envio['ciudad']) > 80)         $errores[] = 'Escribe la ciudad.';
    if (!preg_match('/^\d{5}$/', $envio['cp']))                              $errores[] = 'El código postal debe tener 5 dígitos.';
    if ($envio['telefono'] !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $envio['telefono'])) $errores[] = 'El teléfono no es válido.';
    if (!in_array($metodo, ['tarjeta', 'paypal', 'google_play'], true))      $errores[] = 'Elige un método de pago.';

    $pago = [];
    if ($metodo === 'tarjeta') {
        $pago['numero'] = preg_replace('/\D/', '', $_POST['numero'] ?? '');
        $titular        = trim($_POST['titular'] ?? '');
        $errores = array_merge($errores, validar_tarjeta($pago['numero'], trim($_POST['vence'] ?? ''), trim($_POST['cvv'] ?? ''), $titular));
    }

    if (!$errores) {
        try {
            $r = procesar_compra((int)$u['id'], $metodo, $envio, $pago);
            unset($pago);                                    // el número de tarjeta nunca se conserva
            header('Location: pedido_confirmado.php?id=' . $r['id']); exit;
        } catch (RuntimeException $e) {
            $errores[] = $e->getMessage();
        }
    }
}

$titulo = 'Finalizar compra · El Rincón del Mictlán';
require __DIR__ . '/includes/cabecera.php';
?>
<main class="contenedor contenedor--ancho">
  <h1>Finalizar compra</h1>
  <p class="simulado"><strong>Modo de demostración:</strong> el pago es simulado y no se realiza ningún cobro. No uses datos de tu tarjeta real.</p>
  <?php foreach ($errores as $err): ?><p class="alerta"><?= e($err) ?></p><?php endforeach; ?>

  <div class="checkout">
    <form method="post" id="form-pago" class="checkout__form" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

      <fieldset>
        <legend>1. Datos de envío</legend>
        <label>Nombre de quien recibe <input name="envio_nombre" required maxlength="100" value="<?= e($envio['nombre']) ?>" autocomplete="name"></label>
        <label>Dirección (calle, número, colonia) <input name="envio_direccion" required maxlength="200" value="<?= e($envio['direccion']) ?>" autocomplete="street-address"></label>
        <div class="fila2">
          <label>Ciudad <input name="envio_ciudad" required maxlength="80" value="<?= e($envio['ciudad']) ?>" autocomplete="address-level2"></label>
          <label>Código postal <input name="envio_cp" required inputmode="numeric" pattern="\d{5}" maxlength="5" value="<?= e($envio['cp']) ?>" autocomplete="postal-code"></label>
        </div>
        <label>Teléfono (opcional) <input name="envio_telefono" type="tel" maxlength="20" value="<?= e($envio['telefono']) ?>" autocomplete="tel"></label>
      </fieldset>

      <fieldset>
        <legend>2. Método de pago</legend>
        <div class="metodos">
          <?php foreach (['tarjeta' => 'Tarjeta', 'paypal' => 'PayPal', 'google_play' => 'Google Play'] as $v => $t): ?>
            <label class="metodo"><input type="radio" name="metodo" value="<?= $v ?>" <?= $metodo === $v ? 'checked' : '' ?>><span><?= $t ?></span></label>
          <?php endforeach; ?>
        </div>

        <!-- Se deshabilita (y no se envía) cuando el método no es tarjeta -->
        <fieldset id="campos-tarjeta" class="campos-tarjeta" <?= $metodo !== 'tarjeta' ? 'disabled' : '' ?>>
          <label>Nombre en la tarjeta <input name="titular" maxlength="60" required value="<?= e($titular) ?>" autocomplete="cc-name"></label>
          <label>Número de tarjeta <input name="numero" id="numero" inputmode="numeric" maxlength="23" required placeholder="4242 4242 4242 4242" autocomplete="cc-number"></label>
          <div class="fila2">
            <label>Vence (MM/AA) <input name="vence" id="vence" maxlength="5" required placeholder="12/29" autocomplete="cc-exp"></label>
            <label>CVV <input name="cvv" type="password" inputmode="numeric" maxlength="4" required autocomplete="cc-csc"></label>
          </div>
        </fieldset>
        <p class="nota-pago" id="nota-externa" <?= $metodo === 'tarjeta' ? 'hidden' : '' ?>>
          Al pulsar «Pagar» se simulará la aprobación en la pasarela del método elegido.</p>
      </fieldset>

      <button class="btn" type="submit">Pagar <?= dinero($totalC / 100) ?> (simulado)</button>
    </form>

    <aside class="resumen" aria-label="Resumen del pedido">
      <h2>Tu pedido</h2>
      <ul>
        <?php foreach ($items as $it): ?>
          <li><span><?= (int)$it['cantidad'] ?> × <?= e($it['nombre']) ?></span><span><?= dinero($it['subtotal_c'] / 100) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <p class="resumen__total"><span>Total</span><strong><?= dinero($totalC / 100) ?></strong></p>
      <p><a href="carrito.php">← Editar carrito</a></p>
      <div class="tarjetas-prueba">
        <strong>Tarjetas de prueba</strong><br>
        4242 4242 4242 4242 → aprobada<br>
        4000 0000 0000 0002 → rechazada<br>
        4000 0000 0000 9995 → sin fondos<br>
        (fecha futura y cualquier CVV)
      </div>
    </aside>
  </div>
</main>
<?php require __DIR__ . '/includes/pie.php';
