<!-- Bienvenida -->

<!-- Contenedor del módulo Learn -->
<div id="module-learn">
  <?= $learnSection ?>
</div>

<!-- Contenedor del módulo Forum -->
<div id="module-forum">
  <?= $forumSection ?>
</div>

<?php if (!empty($adminSection)): ?>
  <!-- Contenedor del módulo Admin -->
  <div id="module-admin">
    <?= $adminSection ?>
  </div>
<?php endif; ?>



<script>
document.addEventListener('DOMContentLoaded', () => {
  // Contenedores de módulo
  const $learnModule = document.getElementById('module-learn');
  const $forumModule = document.getElementById('module-forum');
  const $adminModule = document.getElementById('module-admin');

  function show(el)   { if (el) el.classList.remove('hidden'); }
  function hide(el)   { if (el) el.classList.add('hidden'); }

  function toggleByHash(hash) {
    const isAdmin = (hash === '#admin');

    if (isAdmin) {
      show($adminModule);
      hide($learnModule);
      hide($forumModule);
    } else {
      hide($adminModule);
      show($learnModule);
      show($forumModule);
    }
  }

  // Estado inicial
  toggleByHash(location.hash || '#learn');

  // Reacciona a cambios del hash
  window.addEventListener('hashchange', () => toggleByHash(location.hash));
});
</script>
