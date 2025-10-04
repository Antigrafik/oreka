(function(){
  function setMenuHeightVar(){
    const mh = (document.querySelector('.menu')?.offsetHeight || 0);
    document.documentElement.style.setProperty('--menuH', mh + 'px');
  }
  function placeAdminSidebar(){
    const layout  = document.querySelector('.admin-layout');
    const sidebar = document.querySelector('.admin-sidebar');
    if (!layout || !sidebar) return;
    const rect = layout.getBoundingClientRect();
    const left = rect.left + window.pageXOffset;
    const cs = getComputedStyle(layout);
    const firstCol = (cs.gridTemplateColumns || '').split(' ')[0] || '240px';
    let w = parseFloat(firstCol);
    if (!Number.isFinite(w)) w = sidebar.offsetWidth || 240;
    document.documentElement.style.setProperty('--adminLeft',  left + 'px');
    document.documentElement.style.setProperty('--adminWidth', w + 'px');
  }
  function updateAll(){ setMenuHeightVar(); placeAdminSidebar(); }
  updateAll();
  window.addEventListener('resize', updateAll);
  window.addEventListener('scroll', placeAdminSidebar, { passive:true });
  window.addEventListener('hashchange', () => setTimeout(updateAll, 0));
})();

document.addEventListener('DOMContentLoaded', () => {

  const tabs = {};
  document.querySelectorAll('.admin-tab').forEach(el => {
    const m = el.id.match(/^tab-(.+)$/);
    if (m) tabs[m[1]] = el;
  });

  const links = Array.from(document.querySelectorAll('.admin-link[data-tab], .admin-sublink[data-tab]'));

  const normalize = (s) => (s || '').replace(/\//g, '-');

  function pickTabFromHash() {
    const h = (location.hash || '#admin').toLowerCase();
    const m = h.match(/^#admin(?:\/([-\w\/]+))?$/);
    const name = m && m[1] ? normalize(m[1]) : 'dashboard';
    return tabs[name] ? name : 'dashboard';
  }

  function activate(name) {
    Object.entries(tabs).forEach(([n, el]) => {
      if (!el) return;
      if (n === name) el.removeAttribute('hidden'); else el.setAttribute('hidden', '');
    });
    links.forEach(a => {
      const t = a.getAttribute('data-tab');
      a.classList.toggle('active', t === name);
    });
  }

  activate(pickTabFromHash());
  window.addEventListener('hashchange', () => activate(pickTabFromHash()));
});