(function () {
  // trabaja SOLO dentro de #community-recommendations
  const root = document.getElementById('community-recommendations');
  if (!root) return;

  const selTema    = root.querySelector('#filter-tema');
  const selSoporte = root.querySelector('#filter-soporte');
  const searchBox  = root.querySelector('#search-recs');
  const orderBy    = root.querySelector('#order-by');

  function getItems() {
    // sólo slides de recommendations, no meeting
    return Array.from(root.querySelectorAll('.recomendation-slider .track > li'));
  }

  function matchesText(li, q) {
    if (!q) return true;
    q = q.toLowerCase();
    const text = li.textContent.toLowerCase();
    return text.indexOf(q) !== -1;
  }

  function applyFilters() {
    const temaId    = selTema?.value || '';
    const soporteId = selSoporte?.value || '';
    const q         = (searchBox?.value || '').trim().toLowerCase();

    const items = getItems();

    items.forEach(li => {
      const liTema    = li.dataset.temaId || '';
      const liSoporte = li.dataset.soporteId || '';
      const okTema    = !temaId    || liTema    === temaId;
      const okSoporte = !soporteId || liSoporte === soporteId;
      const okText    = matchesText(li, q);
      li.style.display = (okTema && okSoporte && okText) ? '' : 'none';
    });

    // Si tu slider necesita recalcular anchuras:
    // window.dispatchEvent(new Event('recalc-slider'));
  }

  window.addEventListener('load', applyFilters);
  selTema?.addEventListener('change', applyFilters);
  selSoporte?.addEventListener('change', applyFilters);
  searchBox?.addEventListener('input', applyFilters);
  orderBy?.addEventListener('change', applyFilters);
})();
