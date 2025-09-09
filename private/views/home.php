<!-- Bienvenida -->

<!-- Contenedor del módulo Learn -->
<div id="module-learn">
  <?= $learnSection ?>
</div>

<!-- Contenedor del módulo Forum -->
<div id="module-forum">
  <?= $forumSection ?>
</div>

<!-- Contenedor del módulo Community -->


<?php if (!empty($adminSection)): ?>
  <!-- Contenedor del módulo Admin -->
  <div id="module-admin">
    <?= $adminSection ?>
  </div>
<?php endif; ?>



<script>
document.addEventListener('DOMContentLoaded', () => {
  const $learnModule = document.getElementById('module-learn');
  const $forumModule = document.getElementById('module-forum');
  const $communityModule = document.getElementById('module-community');
  const $adminModule = document.getElementById('module-admin');

  function show(el){ if(el) el.classList.remove('hidden'); }
  function hide(el){ if(el) el.classList.add('hidden'); }

  function toggleByHash(hash) {
    const isAdmin = (hash === '#admin' || (hash && hash.startsWith('#admin/')));

    if (isAdmin) {
      show($adminModule); hide($learnModule); hide($forumModule); hide($communityModule);
    } else {
      hide($adminModule); show($learnModule); show($forumModule); show($communityModule);
    }
  }

  toggleByHash(location.hash || '#learn');
  window.addEventListener('hashchange', () => toggleByHash(location.hash));
});
</script>

