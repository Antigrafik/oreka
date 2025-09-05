<?php
declare(strict_types=1);
session_start();

/**
 * Si ya tienes $pdo disponible en el include anterior, comenta esta línea.
 * Ruta pensada para: private/includes/menu.php -> private/config/db_connect.php
 */
if (!isset($pdo)) {
    @require_once __DIR__ . '/../config/db_connect.php';
}

$isAdmin = false;

/* ----------------------------------------------------------
   Resolver identidad del usuario
   - Preferimos id en sesión; si no, email/usuario.
   - Acepta AUTH_USER/REMOTE_USER (IIS) tipo DOMAIN\cuenta.
---------------------------------------------------------- */
$userId   = $_SESSION['user_id'] ?? null;
$userName = $GLOBALS['user']
         ?? ($_SESSION['email'] ?? $_SESSION['user'] ?? $_SERVER['AUTH_USER'] ?? $_SERVER['REMOTE_USER'] ?? null);

if ($userName) {
    $userName = trim((string)$userName);
    // Si viene como DOMAIN\usuario -> quedarnos con "usuario"
    if (strpos($userName, '\\') !== false) {
        $userName = substr($userName, strrpos($userName, '\\') + 1);
    }
}

try {
    if ($userId) {
        $stmt = $pdo->prepare("SELECT roles FROM [user] WHERE id = :id");
        $stmt->execute([':id' => $userId]);
    } elseif (!empty($userName)) {
        $stmt = $pdo->prepare("SELECT roles FROM [user] WHERE name = :name");
        $stmt->execute([':name' => $userName]);
    }

    if (isset($stmt)) {
        $role = $stmt->fetchColumn();
        if ($role !== false) {
            $v = strtolower(trim((string)$role));
            // Acepta 'admin' textual o 1/true numérico/booleano
            $isAdmin = ($v === 'admin' || $v === '1' || $v === 'true');
        }
    }
} catch (Throwable $e) {
    // Si quieres depurar:
    // error_log('menu role check: ' . $e->getMessage());
}
?>

<div class="menu">
  <nav class="menu-inner">

    <a id="menu-learn" class="menu-item" href="/#learn"><?= htmlspecialchars($language['menu']['learn'] ?? 'Aula') ?></a>
    <span class="sep" aria-hidden="true"></span>

    <a id="menu-forum" class="menu-item" href="/#forum"><?= htmlspecialchars($language['menu']['forum'] ?? 'Foro') ?></a>
    <span class="sep" aria-hidden="true"></span>

    <a id="menu-community" class="menu-item" href="/#community"><?= htmlspecialchars($language['menu']['community'] ?? 'Comunidad') ?></a>
    <span class="sep" aria-hidden="true"></span>

    <a class="menu-item" href="/store"><?= htmlspecialchars($language['menu']['shop'] ?? 'Tienda') ?></a>
    <span class="sep" aria-hidden="true"></span>

    <a class="menu-item" href="/mi-espacio"><?= htmlspecialchars($language['menu']['my_space'] ?? 'Mi espacio') ?></a>

    <?php if ($isAdmin): ?>
      <span class="sep" aria-hidden="true"></span>
      <a id="menu-admin" class="menu-item" href="/#admin"><?= htmlspecialchars($language['menu']['admin'] ?? 'Admin') ?></a>
    <?php endif; ?>
  </nav>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // ids de los anchors y sus enlaces en el menú
  const map = { '#learn':'menu-learn', '#forum':'menu-forum', '#community':'menu-community', '#admin':'menu-admin' };

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
  document.querySelectorAll('#menu-learn, #menu-forum, #menu-community, #menu-admin').forEach(a => {
    a.addEventListener('click', (ev) => {
      const href = a.getAttribute('href') || '';
      const m = href.match(/#[-\w/]+/);
      if (!m) return;
      const hash = m[0];

      // Solo prevenir si ya estamos en la home (/, /index, etc.)
      const onHome = location.pathname === '/' || /\/index(\.php)?$/.test(location.pathname);
      if (onHome) {
        ev.preventDefault();

        // 🔧 CAMBIO: en vez de replaceState, usa location.hash para que dispare 'hashchange'
        if (location.hash !== hash) {
          location.hash = hash;          // dispara 'hashchange' -> home.php hace toggle y scroll
        } else {
          // Si ya estamos en el mismo hash, fuerza el ciclo igualmente
          window.dispatchEvent(new HashChangeEvent('hashchange'));
        }

        // Opcional: si quieres mantener el marcado activo inmediato
        markActive(hash);
        // El scroll ya lo hace el listener de home.php; si quieres mantenerlo aquí, déjalo:
        setTimeout(() => scrollToHash(hash), 0);
      }
      // Si NO estamos en home, dejamos navegar a "/#hash" y al cargar hará scroll automático
    });
  });

  // Al cargar, si viene con hash (p.ej. desde /store -> click AULA) haz scroll
  if (location.hash && document.querySelector(location.hash)) {
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
