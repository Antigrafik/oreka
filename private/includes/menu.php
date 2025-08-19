<?php
session_start();

$isAdmin = false;
if (!empty($user)) {
    $stmt = $pdo->prepare("SELECT roles FROM [user] WHERE name = :name");
    $stmt->execute([':name' => $user]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && strtolower($row['roles']) === 'admin') $isAdmin = true;
}
?>
<div class="menu">
  <nav class="menu-inner">

    <a id="menu-learn" class="menu-item" href="/#learn"><?php echo $language['menu']['learn']; ?></a>
    <span class="sep" aria-hidden="true"></span>

    <a id="menu-forum" class="menu-item" href="/#forum"><?php echo $language['menu']['forum']; ?></a>
    <span class="sep" aria-hidden="true"></span>

    <a id="menu-community" class="menu-item" href="/#community"><?php echo $language['menu']['community']; ?></a>
    <span class="sep" aria-hidden="true"></span>

    <a class="menu-item" href="/store"><?php echo $language['menu']['shop']; ?></a>
    <span class="sep" aria-hidden="true"></span>

    <a class="menu-item" href="/mi-espacio"><?php echo $language['menu']['my_space']; ?></a>

    <?php if ($isAdmin): ?>
      <span class="sep" aria-hidden="true"></span>
      <a class="menu-item" href="/admin"><?php echo $language['menu']['admin']; ?></a>
    <?php endif; ?>
  </nav>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // ids de los anchors y sus enlaces en el menú
  const map = { '#learn':'menu-learn', '#forum':'menu-forum', '#community':'menu-community' };

  // Marca activo según hash actual
  function markActive(hash) {
    document.querySelectorAll('.menu-item').forEach(a => a.classList.remove('active'));
    const id = map[hash];
    if (id) {
      const el = document.getElementById(id);
      if (el) el.classList.add('active');
    }
  }

  // Scroll con compensación por el menú fijo
  function scrollToHash(hash) {
    const target = document.querySelector(hash);
    if (!target) return;
    const headerH = document.querySelector('.menu')?.offsetHeight || 0;
    const y = target.getBoundingClientRect().top + window.pageYOffset - headerH - 8; // pequeño margen
    window.scrollTo({ top: y, behavior: 'smooth' });
  }

  // Si ya estamos en HOME, intercepta el click y haz scroll sin recargar
  document.querySelectorAll('#menu-learn, #menu-forum, #menu-community').forEach(a => {
    a.addEventListener('click', (ev) => {
      const href = a.getAttribute('href') || '';
      const m = href.match(/#\w+/);
      if (!m) return;
      const hash = m[0];

      // Solo prevenir si ya estamos en la home (/, /index, etc.)
      const onHome = location.pathname === '/' || /\/index(\.php)?$/.test(location.pathname);
      if (onHome) {
        ev.preventDefault();
        history.replaceState(null, '', '/' + hash); // actualiza la URL con el hash
        markActive(hash);
        // Espera un tick por si el layout aún no ha pintado
        setTimeout(() => scrollToHash(hash), 0);
      }
      // Si NO estamos en home, dejamos navegar a "/#hash" y al cargar hará scroll automático (abajo).
    });
  });

  // Al cargar, si viene con hash (p.ej. desde /store -> click AULA) haz scroll
  if (location.hash && document.querySelector(location.hash)) {
    // pequeño delay para asegurarnos de que las secciones ya están renderizadas
    setTimeout(() => {
      markActive(location.hash);
      scrollToHash(location.hash);
    }, 0);
  } else {
    // Marca AULA por defecto si no hay hash
    markActive('#learn');
  }

  // Si el hash cambia (p.ej. usuario escribe #forum), actúa igual
  window.addEventListener('hashchange', () => {
    markActive(location.hash);
    scrollToHash(location.hash);
  });
});
</script>


