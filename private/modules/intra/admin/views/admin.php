<?php
$GLOBALS['moduleFlags'] = $moduleFlags;
?>

<section id="admin" class="admin-root">
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <h2 class="admin-title">Admin</h2>
      <nav class="admin-nav">
        <a href="#admin" data-tab="dashboard" class="admin-link"><?= htmlspecialchars($language['menu_admin']['dashboard'] ?? 'Dashboard') ?></a>
        <a href="#admin/users" data-tab="users" class="admin-link"><?= htmlspecialchars($language['menu_admin']['users'] ?? 'Usuarios') ?></a>
        <a href="#admin/learn" data-tab="learn" class="admin-link"><?= htmlspecialchars($language['menu_admin']['learn'] ?? 'Aula') ?></a>
        <a href="#admin/forum" data-tab="forum" class="admin-link"><?= htmlspecialchars($language['menu_admin']['forum'] ?? 'Foro') ?></a>

        <a href="#admin/community" data-tab="community" class="admin-link"><?= htmlspecialchars($language['menu_admin']['community'] ?? 'Comunidad') ?></a>
        <div class="admin-subnav">
          <a href="#admin/community/recommendations" data-tab="community-recommendations" class="admin-sublink">
            <?= htmlspecialchars($language['modules']['recommendations'] ?? 'Recomendaciones') ?>
          </a>
          <a href="#admin/community/routines" data-tab="community-routines" class="admin-sublink">
            <?= htmlspecialchars($language['modules']['routines'] ?? 'Rutinas') ?>
          </a>
          <a href="#admin/community/trial" data-tab="community-trial" class="admin-sublink">
            <?= htmlspecialchars($language['modules']['trial'] ?? 'Pruebas') ?>
          </a>
          <a href="#admin/community/meeting" data-tab="community-meeting" class="admin-sublink">
            <?= htmlspecialchars($language['modules']['meeting'] ?? 'Quedadas') ?>
          </a>
        </div>
        <a href="#admin/banner" data-tab="banner" class="admin-link"><?= htmlspecialchars($language['menu_admin']['banner'] ?? 'Banner') ?></a>
        <a href="#admin/store" data-tab="store" class="admin-link"><?= htmlspecialchars($language['menu_admin']['store'] ?? 'Tienda') ?></a>
        <a href="#admin/legal" data-tab="legal" class="admin-link"><?= htmlspecialchars($language['menu_admin']['legal'] ?? 'Legal') ?></a>
      </nav>
    </aside>

    <main class="admin-content">
      <section id="tab-dashboard" class="admin-tab">
        <h1>Dashboard admin</h1>
        <p>Bienvenido al panel de administración.</p>
        <p>Desde aquí puedes gestionar los usuarios, roles, permisos y otras configuraciones del sistema.</p>
        <p>Utiliza el menú de navegación para acceder a las diferentes secciones.</p>
        <p>Recuerda que los cambios realizados aquí pueden afectar a todo el sistema, así que procede con precaución.</p>
      </section>

      <section id="tab-users" class="admin-tab" hidden>
        <?php
          $usersView = PRIVATE_PATH . '/modules/intra/admin/views/users.php';
          if (is_file($usersView)) { include $usersView; }
          else { echo '<h2>Usuarios</h2><p>(Vista users.php no encontrada)</p>'; }
        ?>
      </section>

      <section id="tab-banner" class="admin-tab" hidden>
        <?php
          $bannerView = PRIVATE_PATH . '/modules/intra/admin/views/banner.php';
          if (is_file($bannerView)) { include $bannerView; }
          else { echo '<h2>Banner</h2><p>(Vista banner.php no encontrada)</p>'; }
        ?>
      </section>

      <section id="tab-store" class="admin-tab" hidden>
        <?php
          $storeView = PRIVATE_PATH . '/modules/intra/admin/views/store.php';
          if (is_file($storeView)) { include $storeView; }
          else { echo '<h2>Tienda</h2><p>(Vista store.php no encontrada)</p>'; }
        ?>
      </section>

      <section id="tab-learn" class="admin-tab" hidden>
        <?php
          $learnView = PRIVATE_PATH . '/modules/intra/admin/views/learn.php';
          if (is_file($learnView)) { include $learnView; }
          else { echo '<h2>Aula</h2><p>(Vista learn.php no encontrada)</p>'; }
        ?>
      </section>

      <section id="tab-forum" class="admin-tab" hidden>
        <?php
          $forumView = PRIVATE_PATH . '/modules/intra/admin/views/forum.php';
          if (is_file($forumView)) { include $forumView; }
          else { echo '<h2>Foro</h2><p>(Vista forum.php no encontrada)</p>'; }
        ?>
      </section>

      <section id="tab-community" class="admin-tab" hidden>
        <?php
          $communityView = PRIVATE_PATH . '/modules/intra/admin/views/community.php';
          if (is_file($communityView)) { include $communityView; }
          else { echo '<h2>Comunidad</h2><p>(Vista community.php no encontrada)</p>'; }
        ?>
      </section>

      <section id="tab-community-recommendations" class="admin-tab" hidden>
        <?php
          $view = PRIVATE_PATH . '/modules/intra/admin/views/community_recommendations.php';
          if (is_file($view)) { include $view; }
          else { echo '<h2>Recomendaciones</h2><p>(Vista community_recommendations.php no encontrada)</p>'; }
        ?>
      </section>

      <section id="tab-community-routines" class="admin-tab" hidden>
        <?php
          $view = PRIVATE_PATH . '/modules/intra/admin/views/community_routines.php';
          if (is_file($view)) { include $view; }
          else { echo '<h2>Rutinas</h2><p>(Vista community_routines.php no encontrada)</p>'; }
        ?>
      </section>

      <section id="tab-community-trial" class="admin-tab" hidden>
        <?php
          $view = PRIVATE_PATH . '/modules/intra/admin/views/community_trial.php';
          if (is_file($view)) { include $view; }
          else { echo '<h2>Pruebas</h2><p>(Vista community_trial.php no encontrada)</p>'; }
        ?>
      </section>

      <section id="tab-community-meeting" class="admin-tab" hidden>
        <?php
          $view = PRIVATE_PATH . '/modules/intra/admin/views/community_meeting.php';
          if (is_file($view)) { include $view; }
          else { echo '<h2>Quedadas</h2><p>(Vista community_meeting.php no encontrada)</p>'; }
        ?>
      </section>

      <section id="tab-legal" class="admin-tab" hidden>
        <?php
          $legalView = PRIVATE_PATH . '/modules/intra/admin/views/legal.php';
          if (is_file($legalView)) { include $legalView; }
          else { echo '<h2>Legal</h2><p>(Vista legal.php no encontrada)</p>'; }
        ?>
      </section>
    </main>
  </div>
</section>

<script src="/assets/js/admin/admin.js"></script>