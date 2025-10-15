// community-filters.js
document.addEventListener('DOMContentLoaded', () => {
  const temaSelect     = document.getElementById('filter-tema');
  const soporteSelect  = document.getElementById('filter-soporte');
  const orderSelect    = document.getElementById('order-by');
  const searchInput    = document.getElementById('search-recs');
  const articles       = Array.from(document.querySelectorAll('.recommendation-item'));
  const feedContainer  = document.querySelector('.recommendation-feed');
  const loadMoreText   = document.querySelector('.load-more-text');
  const noResultsMsg   = document.querySelector('.no-results');

  // ==== CONFIGURACIÓN ====
  const articlesPerPage = 3;
  let visibleCount = articlesPerPage;

  // Lista actual filtrada (se actualiza en applyFilters)
  let filteredList = articles.slice();

  // ==== FUNCIÓN: mostrar X artículos visibles ====
  function showArticles(limit) {
    const list = filteredList.length ? filteredList : articles;

    list.forEach((a, i) => {
      a.style.display = i < limit ? '' : 'none';
    });

    if (loadMoreText) {
      loadMoreText.style.display = list.length > limit ? 'block' : 'none';
    }
    if (noResultsMsg) {
      noResultsMsg.style.display = list.length === 0 ? 'block' : 'none';
    }
  }

  // ==== FUNCIÓN: aplicar filtros ====
  function applyFilters() {
    const tema     = temaSelect.value;
    const soporte  = soporteSelect.value;
    const order    = orderSelect.value;
    const search   = searchInput.value.toLowerCase();

    // Filtrar y guardar los que coinciden
    const filtered = articles.filter(article => {
      const matchesTema    = !tema || article.dataset.temaId === tema;
      const matchesSoporte = !soporte || article.dataset.soporteId === soporte;
      const textContent    = article.textContent.toLowerCase();
      const matchesSearch  = !search || textContent.includes(search);
      return matchesTema && matchesSoporte && matchesSearch;
    });

    // Ocultar todos primero
    articles.forEach(a => (a.style.display = 'none'));

    // Ordenar los filtrados
    filtered.sort((a, b) => {
      if (order === 'likes') {
        return parseInt(b.dataset.likes) - parseInt(a.dataset.likes);
      } else if (order === 'recent') {
        return new Date(b.dataset.date) - new Date(a.dataset.date);
      }
      return 0; // “Todos”
    });

    // Volver a insertar solo los filtrados en el DOM
    filtered.forEach(a => feedContainer.appendChild(a));

    // Registrar la lista filtrada para que "Mostrar más" funcione bien
    filteredList = filtered;

    // Mostrar solo los primeros según el límite
    visibleCount = articlesPerPage;
    filtered.forEach((a, i) => {
      a.style.display = i < visibleCount ? '' : 'none';
    });

    // Mostrar/ocultar el texto “Mostrar más”
    if (loadMoreText) {
      loadMoreText.style.display = filtered.length > visibleCount ? 'block' : 'none';
    }

    // Mostrar o esconder el mensaje “sin resultados”
    if (noResultsMsg) {
      noResultsMsg.style.display = filtered.length === 0 ? 'block' : 'none';
    }
  }

  // ==== EVENTOS ====
  [temaSelect, soporteSelect, orderSelect].forEach(el => el.addEventListener('change', applyFilters));
  searchInput.addEventListener('input', applyFilters);

  // ==== MOSTRAR MÁS ====
  if (loadMoreText) {
    loadMoreText.addEventListener('click', () => {
      visibleCount += articlesPerPage;
      showArticles(visibleCount);
    });
  }
  // ==== INICIALIZACIÓN ====
  applyFilters();

});
