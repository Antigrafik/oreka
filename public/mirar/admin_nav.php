<?php
$baseUrl = $baseUrl ?? ($globalConfig['base_url'] ?? '');
$role    = $_SESSION['userRole'] ?? '';
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

$can = function(array $roles) use ($role) {
  return in_array($role, $roles, true);
};
$active = function(string $needle) use ($uriPath) {
  return str_starts_with($uriPath, $needle) ? 'active' : '';
};
?>

<aside class="admin__sidebar" id="sidebar">
  <div class="brand">
    <span class="logo">OME-AEN</span>
    <button class="sidebar__close" id="btnCloseSidebar" aria-label="Cerrar menú">×</button>
  </div>

  <nav class="menu">
    <a class="<?= $active('/admin/dashboard'); ?>" href="<?= $baseUrl; ?>/admin/dashboard">
      <i class="fa-solid fa-gauge"></i> Dashboard
    </a>

    <?php if ($can(['Administrador'])): ?>
      <a class="<?= $active('/admin/users'); ?>" href="<?= $baseUrl; ?>/admin/users">
        <i class="fa-solid fa-users"></i> Usuarios
      </a>
    <?php endif; ?>

    <?php if ($can(['Administrador','Editor'])): ?>
      <a class="<?= $active('/admin/events'); ?>" href="<?= $baseUrl; ?>/admin/events">
        <i class="fa-solid fa-calendar-days"></i> Eventos
      </a>
      <a class="<?= $active('/admin/files'); ?>" href="<?= $baseUrl; ?>/admin/files?section=biblioteca">
        <i class="fa-solid fa-folder-open"></i> Biblioteca
      </a>
      <a class="<?= $active('/admin/files'); ?>" href="<?= $baseUrl; ?>/admin/files?section=revista">
        <i class="fa-solid fa-book-open"></i> Revista Norte
      </a>
      <a class="<?= $active('/admin/category'); ?>" href="<?= $baseUrl; ?>/admin/category">
        <i class="fa-solid fa-tags"></i> Categorías
      </a>
    <?php endif; ?>

    <a href="<?= $baseUrl; ?>/admin/logout">
      <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
    </a>
  </nav>
</aside>