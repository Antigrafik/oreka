<section id="admin" class="admin-root">
  <div class="admin-layout">
    <!-- Sidebar vertical -->
    <aside class="admin-sidebar">
      <h2 class="admin-title">Admin</h2>
      <nav class="admin-nav">
        <a href="#admin" data-tab="dashboard" class="admin-link">Dashboard</a>
        <a href="#admin/users" data-tab="users" class="admin-link">Usuarios</a>
        <a href="#admin/store" data-tab="store" class="admin-link">Tienda</a>
        <a href="#admin/learn" data-tab="learn" class="admin-link">Aula</a>
        <a href="#admin/forum" data-tab="forum" class="admin-link">Foro</a>
        <a href="#admin/community" data-tab="community" class="admin-link">Comunidad</a>
      </nav>
    </aside>

    <!-- Contenido -->
    <main class="admin-content">
      <!-- Dashboard (por defecto) -->
      <section id="tab-dashboard" class="admin-tab">
        <h1>Dashboard admin</h1>
        <p>Bienvenido al panel de administración.</p>
        <p>Desde aquí puedes gestionar los usuarios, roles, permisos y otras configuraciones del sistema.</p>
        <p>Utiliza el menú de navegación para acceder a las diferentes secciones.</p>
        <p>Si necesitas ayuda, consulta la documentación o contacta con el soporte técnico.</p>
        <p>Recuerda que los cambios realizados aquí pueden afectar a todo el sistema, así que procede con precaución.</p>
        <p>Para más información, visita la sección de <a href="https://www.example.com/documentacion" target="_blank" rel="noopener">documentación</a>.</p>
      </section>

      <!-- Usuarios -->
      <section id="tab-users" class="admin-tab" hidden>
        <?php
        // Incluye la vista si la tienes
        $usersView = PRIVATE_PATH . '/modules/intra/views/admin/users.php';
        if (is_file($usersView)) { include $usersView; }
        else { echo '<h2>Usuarios</h2><p>(Vista users.php no encontrada)</p>'; }
        ?>
      </section>

      <!-- Tienda -->
      <section id="tab-store" class="admin-tab" hidden>
        <?php
        $storeView = PRIVATE_PATH . '/modules/intra/views/admin/store.php';
        if (is_file($storeView)) { include $storeView; }
        else { echo '<h2>Tienda</h2><p>(Vista store.php no encontrada)</p>'; }
        ?>
      </section>

      <!-- Aula -->
      <section id="tab-learn" class="admin-tab" hidden>
        <?php
        $learnView = PRIVATE_PATH . '/modules/intra/views/admin/learn.php';
        if (is_file($learnView)) { include $learnView; }
        else { echo '<h2>Aula</h2><p>(Vista learn.php no encontrada)</p>'; }
        ?>
      </section>

      <!-- Foro -->
      <section id="tab-forum" class="admin-tab" hidden>
        <?php
        $forumView = PRIVATE_PATH . '/modules/intra/views/admin/forum.php';
        if (is_file($forumView)) { include $forumView; }
        else { echo '<h2>Foro</h2><p>(Vista forum.php no encontrada)</p>'; }
        ?>
      </section>

      <!-- Comunidad -->
      <section id="tab-community" class="admin-tab" hidden>
        <?php
        $communityView = PRIVATE_PATH . '/modules/intra/views/admin/community.php';
        if (is_file($communityView)) { include $communityView; }
        else { echo '<h2>Comunidad</h2><p>(Vista community.php no encontrada)</p>'; }
        ?>
      </section>
    </main>
  </div>
</section>

<style>
  .admin-root { padding: 1rem; }

  /* Grid: sidebar izquierda + contenido derecha */
.admin-layout{
  display: grid;
  grid-template-columns: var(--adminWidth, 240px) 1fr; /* <- clave */
  gap: 1.25rem;
  align-items: start;
}

  /* Sidebar pegajoso (no se mueve al hacer scroll) */
.admin-sidebar{
  grid-column: 1 / 2; /* reserva la primera pista del grid */
  position: fixed;
  top: calc(var(--menuH, 0px) + 81px);
  left: var(--adminLeft, 0px);
  width: var(--adminWidth, 240px);
  z-index: 10;
  border: 1px solid #c00;
  border-radius: 16px;
  padding: 1rem;
  background: #fff;
}

  /* Contenido a la derecha */
.admin-content{
  grid-column: 2 / 3;        /* <- clave */
  border: 1px solid #c00;
  border-radius: 16px;
  padding: 1.25rem;
  min-height: 60vh;
  overflow: visible;
}

@supports not (grid-template-columns: subgrid) {
  .admin-content{ margin-left: 0; } /* por defecto 0 */
}

  .admin-tab[hidden] { display: none !important; }

  .admin-title { margin: 0 0 .75rem; font-size: 1.25rem; }
  .admin-nav { display: grid; gap: .5rem; }
  .admin-link { display: block; padding: .5rem .75rem; border-radius: 10px; text-decoration: none; color: #111; border: 1px solid transparent; }
  .admin-link.active { background: #f8d7da; border-color: #c00; font-weight: 600; }

  @media (max-width: 980px){
    .admin-sidebar{ position: static; left:auto; width:auto; top:auto; }
    .admin-layout{ grid-template-columns: 1fr; }
  }

</style>

<script>
(function(){
  function setMenuHeightVar(){
    const mh = (document.querySelector('.menu')?.offsetHeight || 0);
    document.documentElement.style.setProperty('--menuH', mh + 'px');
  }

function placeAdminSidebar(){
  const layout  = document.querySelector('.admin-layout');
  const sidebar = document.querySelector('.admin-sidebar');
  if (!layout || !sidebar) return;

  // Left absoluto del layout (no del sidebar)
  const layoutRect = layout.getBoundingClientRect();
  const left = layoutRect.left + window.pageXOffset;

  // Ancho REAL de la 1ª columna del grid (`grid-template-columns`)
  const cs = getComputedStyle(layout);
  // ejemplo: "240px 1fr"  -> tomamos el primer valor
  const firstCol = (cs.gridTemplateColumns || '').split(' ')[0] || '240px';

  // Parseamos px (si es fr o %, caemos al ancho actual del aside)
  let sidebarW = parseFloat(firstCol);
  if (!Number.isFinite(sidebarW)) sidebarW = sidebar.offsetWidth || 240;

  // Aplicamos a las variables CSS que usa el sidebar fixed
  document.documentElement.style.setProperty('--adminLeft',  left + 'px');
  document.documentElement.style.setProperty('--adminWidth', sidebarW + 'px');
}

  function updateAll(){
    setMenuHeightVar();
    placeAdminSidebar();
  }

  // Inicial y eventos
  updateAll();
  window.addEventListener('resize', updateAll);
  window.addEventListener('scroll', placeAdminSidebar, { passive: true });

  // Por si cambias de tab y varía la altura/layout
  window.addEventListener('hashchange', () => setTimeout(updateAll, 0));
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {

  const links = Array.from(document.querySelectorAll('.admin-link'));
  const tabs  = {
    'dashboard': document.getElementById('tab-dashboard'),
    'users':     document.getElementById('tab-users'),
    'store':     document.getElementById('tab-store'),
    'learn':     document.getElementById('tab-learn'),
    'forum':     document.getElementById('tab-forum'),
    'community': document.getElementById('tab-community'),
  };

  function pickTabFromHash() {
    // Hash esperado: #admin, #admin/users, #admin/store, ...
    const h = (location.hash || '#admin').toLowerCase();
    const m = h.match(/^#admin(?:\/([-\w]+))?$/);
    const tab = m && m[1] ? m[1] : 'dashboard';
    return tabs[tab] ? tab : 'dashboard';
  }

  function activate(tabName) {
    // tabs
    Object.entries(tabs).forEach(([name, el]) => {
      if (!el) return;
      if (name === tabName) el.removeAttribute('hidden'); else el.setAttribute('hidden', '');
    });
    // links activos
    links.forEach(a => {
      const t = a.getAttribute('data-tab');
      a.classList.toggle('active', t === tabName);
    });
  }

  // Inicial
  activate(pickTabFromHash());

  // Cambios de hash
  window.addEventListener('hashchange', () => activate(pickTabFromHash()));
});
</script>
