document.addEventListener('DOMContentLoaded', () => {
  // Mapa hash -> id del enlace, pero sólo si el elemento existe
  const map = {};
  const add = (hash, id) => { const el = document.getElementById(id); if (el) map[hash] = id; };
  add('#learn', 'menu-learn');
  add('#forum', 'menu-forum');
  add('#community', 'menu-community');
  add('#my-space', 'menu-myspace');
  add('#admin', 'menu-admin');

  function markActive(hash) {
    document.querySelectorAll('.menu-item').forEach(a => a.classList.remove('active'));
    const id = map[hash];
    if (id) { const el = document.getElementById(id); if (el) el.classList.add('active'); }
  }

  function scrollToHash(hash) {
    if (/^#admin(?:\/|$)/i.test(hash)) return;
    let target = null;
    try { target = document.querySelector(hash); } catch(e) { return; }
    if (!target) return;
    const headerH = document.querySelector('.menu')?.offsetHeight || 0;
    const y = target.getBoundingClientRect().top + window.pageYOffset - headerH - 8;
    window.scrollTo({ top: y, behavior: 'smooth' });
  }


  // Interceptar clicks sólo si ya estás en Home
  const selector = Object.values(map).map(id => '#' + id).join(', ');
  if (selector) {
    document.querySelectorAll(selector).forEach(a => {
      a.addEventListener('click', (ev) => {
        const href = a.getAttribute('href') || '';
        const m = href.match(/#[-\w/]+/);
        if (!m) return;
        const hash = m[0];

        const onHome = location.pathname === '/' || /\/index(\.php)?$/.test(location.pathname);
        if (onHome) {
          ev.preventDefault();
          if (location.hash !== hash) {
            location.hash = hash;           // disparará 'hashchange'
          } else {
            window.dispatchEvent(new HashChangeEvent('hashchange'));
          }
          markActive(hash);
          setTimeout(() => scrollToHash(hash), 0);
        }
      });
    });
  }

  if (location.hash) {
    setTimeout(() => {
      markActive(location.hash);
      if (!/^#admin(?:\/|$)/i.test(location.hash)) {
        try { scrollToHash(location.hash); } catch(e) {}
      }
    }, 0);
  } else {
    markActive('#learn');
  }

  window.addEventListener('hashchange', () => {
    markActive(location.hash);
    if (!/^#admin(?:\/|$)/i.test(location.hash)) {
      try { scrollToHash(location.hash); } catch(e) {}
    }
  });
});

(function () {
  function setActive() {
    // limpia
    document.querySelectorAll('.menu-inner a.menu-item').forEach(a => a.classList.remove('is-active'));
    // marca por hash (Aula/Foro/Comunidad/Mi espacio/Admin)
    var hash = window.location.hash;
    if (hash) {
      var link = document.querySelector('.menu-inner a[href$="' + CSS.escape(hash) + '"]');
      if (link) link.classList.add('is-active');
      return;
    }
    // si no hay hash, marca por path (ej. /store)
    var path = location.pathname.replace(/\/+$/,'') || '/';
    var link2 = document.querySelector('.menu-inner a[href="' + path + '"]');
    if (link2) link2.classList.add('is-active');
  }
  window.addEventListener('hashchange', setActive);
  window.addEventListener('DOMContentLoaded', setActive);
})();