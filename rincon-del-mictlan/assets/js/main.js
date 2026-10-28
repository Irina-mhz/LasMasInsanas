/* =====================================================================
   EL RINCÓN DEL MICTLÁN · JavaScript
   1) Buscador   2) Carrusel   3) Carrito (contador)   4) Placeholders de imagen
   ===================================================================== */

/* quita acentos y mayúsculas: "Cerámica" == "ceramica" */
const normalizar = (t) => t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();

/* ---------- 1) BUSCADOR ---------- */
const inputBuscar = document.getElementById('buscador');
if (inputBuscar) {
  const tarjetas   = [...document.querySelectorAll('.tarjeta')];
  const categorias = [...document.querySelectorAll('[data-categoria]')];
  const sinResult  = document.getElementById('sin-resultados');

  inputBuscar.addEventListener('input', () => {
    const q = normalizar(inputBuscar.value);
    tarjetas.forEach((t) => t.classList.toggle('oculto', q !== '' && !normalizar(t.dataset.busqueda).includes(q)));
    // oculta las categorías que se quedaron sin productos visibles
    let visibles = 0;
    categorias.forEach((c) => {
      const hay = c.querySelector('.tarjeta:not(.oculto)') !== null;
      c.classList.toggle('oculto', !hay);
      if (hay) visibles++;
    });
    sinResult.hidden = visibles > 0;
    if (q !== '') document.getElementById('catalogo').scrollIntoView();
  });
}

/* ---------- 2) CARRUSEL (flechas) ---------- */
document.querySelectorAll('.carrusel').forEach((car) => {
  const pista = car.querySelector('.carrusel__pista');
  car.querySelectorAll('.flecha').forEach((btn) =>
    btn.addEventListener('click', () => pista.scrollBy({ left: btn.dataset.dir * 400 })));
});

/* ---------- 3) CARRITO (Conexión real con el servidor) ---------- */
function avisar(texto) {
  const a = document.getElementById('aviso');
  if (!a) return;
  a.textContent = texto; a.hidden = false;
  clearTimeout(avisar.t); avisar.t = setTimeout(() => (a.hidden = true), 2200);
}

function pintarContador(total) {
  const el = document.getElementById('contador-carrito');
  if (el) { el.textContent = total; el.hidden = total === 0; }
}

document.querySelectorAll('[data-agregar]').forEach((btn) =>
  btn.addEventListener('click', async () => {
    const t = btn.closest('[data-id]');
    const id = t.dataset.id;
    const csrfMeta = document.querySelector('meta[name="csrf"]');
    const baseUrlMeta = document.querySelector('meta[name="base-url"]');
    
    // Preparamos los datos para enviarlos al servidor (carrito_api.php)
    const formData = new FormData();
    formData.append('id_producto', id);
    formData.append('csrf', csrfMeta ? csrfMeta.content : '');

    try {
      const url = (baseUrlMeta ? baseUrlMeta.content : '/rincon-del-mictlan/') + 'carrito_api.php';
      const res = await fetch(url, { method: 'POST', body: formData });
      const data = await res.json();
      
      if (data.ok) {
        pintarContador(data.cantidad);
        avisar(data.mensaje); // Muestra "Agregado al carrito"
      } else {
        avisar(data.mensaje); // Muestra "Debes iniciar sesión..."
      }
    } catch (err) {
      avisar('Error al conectar con el carrito.');
    }
  })
);

/* ---------- 4) PLACEHOLDER si falta una imagen ----------
   Muestra un recuadro que dice QUÉ archivo falta y dónde ponerlo. */
function placeholder(img) {
  if (img.dataset.ph) return;
  img.dataset.ph = '1';
  const w = Number(img.getAttribute('width'))  || 300;
  const h = Number(img.getAttribute('height')) || 300;
  const ruta = decodeURI(new URL(img.src, location.href).pathname).replace(/^.*\/(assets\/)/, '$1');
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
    <rect width="100%" height="100%" fill="#2a1038" stroke="#601986" stroke-width="4" stroke-dasharray="10 6"/>
    <text x="50%" y="45%" fill="#fff" font-family="sans-serif" font-size="${Math.min(Math.max(11, w / 14), h / 4)}" text-anchor="middle">Falta imagen</text>
    <text x="50%" y="58%" fill="#ffb703" font-family="sans-serif" font-size="${Math.min(Math.max(9, w / 20), h / 6)}" text-anchor="middle">${ruta}</text></svg>`;
  img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
}
document.querySelectorAll('img').forEach((img) => {
  img.addEventListener('error', () => placeholder(img));
  if (img.complete && img.naturalWidth === 0) placeholder(img);   // ya había fallado antes de cargar el JS
});
