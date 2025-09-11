
(function(){
  const selTema    = document.getElementById('filter-tema');
  const selSoporte = document.getElementById('filter-soporte');
  const searchBox  = document.getElementById('search-recs');
  const orderBy    = document.getElementById('order-by');

  // Siempre obtener los <li> en el momento del filtrado (incluye clones del slider)
  function getItems(){
    return Array.from(document.querySelectorAll('.recomendation-slider .track > li'));
  }

  function matchesText(li, q) {
    if (!q) return true;
    q = q.toLowerCase();
    const text = li.textContent.toLowerCase();
    return text.indexOf(q) !== -1;
  }

  function applyFilters(){
    const temaId    = selTema.value;      // string o ""
    const soporteId = selSoporte.value;   // string o ""
    const q         = (searchBox?.value || '').trim().toLowerCase();

    const items = getItems(); // ⬅️ AHORA, NO SE QUEDAN FUERA LOS CLONES

    items.forEach(li => {
      const liTema    = li.dataset.temaId || '';
      const liSoporte = li.dataset.soporteId || '';
      const okTema    = !temaId    || liTema    === temaId;
      const okSoporte = !soporteId || liSoporte === soporteId;
      const okText    = matchesText(li, q);
      li.style.display = (okTema && okSoporte && okText) ? '' : 'none';
    });

    // (Opcional) Si el carrusel recalcula anchuras según elementos visibles,
    // puedes lanzar un evento personalizado aquí para que lo rehaga.
    // window.dispatchEvent(new Event('recalc-slider'));
  }

  // Filtrar también cuando el carrusel ya haya creado clones
  window.addEventListener('load', applyFilters);

  selTema?.addEventListener('change', applyFilters);
  selSoporte?.addEventListener('change', applyFilters);
  searchBox?.addEventListener('input', applyFilters);
  orderBy?.addEventListener('change', applyFilters);
})();

