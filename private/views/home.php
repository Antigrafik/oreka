<div id="module-learn"><?= $learnSection ?></div>
<div id="module-forum"><?= $forumSection ?></div>
<div id="module-community"><?= $communitySection ?></div>

<div id="module-legal" class="hidden"><?= $legalSection ?></div>

<div id="module-myspace" class="hidden"><?= $mySpaceSection ?></div>

<?php if (!empty($adminSection)): ?>
  <div id="module-admin" class="hidden"><?= $adminSection ?></div>
<?php endif; ?>

<style>.hidden{display:none!important}</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const M = {
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

  function route(hash) {
    const h = (hash || '').toLowerCase();

    // Oculta SIEMPRE todo antes de mostrar
    hideAll();

    if (h === '#legal' || h.startsWith('#legal/')) {
      show(M.legal); return;
    }
    if (h === '#my-space' || h.startsWith('#my-space/')) {
      show(M.myspace); return;
    }
    if (h === '#admin' || h.startsWith('#admin/')) {
      show(M.admin); return;
    }

    // Vista por defecto (home): Aula + Foro + Comunidad
    show(M.learn); show(M.forum); show(M.community);
  }

  // Carga inicial
  route(location.hash || '#learn');

  // Cambios de hash
  window.addEventListener('hashchange', () => route(location.hash));

  // “Red de seguridad”: si el menú no cambia bien el hash, forzamos el route después del click
  document.querySelectorAll('.menu a[href^="#"]').forEach(a => {
    a.addEventListener('click', () => setTimeout(() => route(location.hash), 0));
  });

  // Botón del topbar "Bases / Legal" (por si algún día cambia su href)
  const legalBtn = document.querySelector('.legal-link');
  if (legalBtn) legalBtn.addEventListener('click', () => setTimeout(() => route('#legal'), 0));
});
</script>

