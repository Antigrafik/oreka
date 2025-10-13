document.addEventListener('DOMContentLoaded', () => {
  const M = {
    banner:    document.getElementById('module-banner'),
    learn:     document.getElementById('module-learn'),
    forum:     document.getElementById('module-forum'),
    community: document.getElementById('module-community'),
    legal:     document.getElementById('module-legal'),
    myspace:   document.getElementById('module-myspace'),
    admin:     document.getElementById('module-admin')
  };

  const ALL = Object.values(M).filter(Boolean);
  const hideAll = () => ALL.forEach(el => el.classList.add('hidden'));
  const show    = el => { if (el) el.classList.remove('hidden'); };

  function showDefault() {
    [M.banner, M.learn, M.forum, M.community].forEach(el => { if (el) show(el); });
  }

  function route(hash) {
    const h = (hash || '').toLowerCase();
    hideAll();

    if ((h === '#legal' || h.startsWith('#legal/')) && M.legal) {
      show(M.legal); return;
    }
    if (h === '#my-space' || h.startsWith('#my-space/')) {
      show(M.myspace); return;
    }
    if (h === '#admin' || h.startsWith('#admin/')) {
      show(M.admin); return;
    }

    showDefault();
  }

  route(location.hash || '#learn');
  window.addEventListener('hashchange', () => route(location.hash));

  document.querySelectorAll('.menu a[href^="#"]').forEach(a => {
    a.addEventListener('click', () => setTimeout(() => route(location.hash), 0));
  });

  const legalBtn = document.querySelector('.legal-link');
  if (legalBtn) legalBtn.addEventListener('click', () => setTimeout(() => route('#legal'), 0));
});