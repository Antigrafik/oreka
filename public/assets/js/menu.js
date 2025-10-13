document.addEventListener('DOMContentLoaded', () => {
  const menu = document.querySelector('.menu');
  const toggle = document.getElementById('menuToggle');
  const main = document.querySelector('.main-content');

  // ====== 1) Sidebar plegable solo por clic ======
  if (menu && toggle) {
    toggle.addEventListener('click', () => {
      const isOpen = menu.classList.toggle('is-open');

      // Cambiar icono y texto del botón
      const icon = toggle.querySelector('i');
      const label = toggle.querySelector('span');
      if (isOpen) {
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-left');
        label.textContent = 'Cerrar';
      } else {
        icon.classList.remove('fa-chevron-left');
        icon.classList.add('fa-chevron-right');
        label.textContent = '';
      }

      // Ajustar margen del contenido
      if (main) {
        main.style.marginLeft = isOpen ? '160px' : '60px';
      }
    });
  }

  // ====== 2) Marcado activo por hash (sin scroll manual) ======
  const map = {};
  const add = (hash, id) => {
    const el = document.getElementById(id);
    if (el) map[hash] = id;
  };
  add('#learn',     'menu-learn');
  add('#forum',     'menu-forum');
  add('#community', 'menu-community');
  add('#my-space',  'menu-myspace');
  add('#admin',     'menu-admin');

  function markActive(hash) {
    document.querySelectorAll('.menu-item').forEach(a => a.classList.remove('active'));
    const id = map[hash];
    if (id) {
      const el = document.getElementById(id);
      if (el) el.classList.add('active');
    }
  }

  // Enlace interno activo al cargar
  if (location.hash && document.querySelector(location.hash)) {
    markActive(location.hash);
  } else {
    markActive('#learn');
  }

  // Cambiar activo en cambios de hash
  window.addEventListener('hashchange', () => {
    markActive(location.hash);
  });
});
