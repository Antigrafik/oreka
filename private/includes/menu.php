<?php
declare(strict_types=1);
session_start();

if (!isset($pdo)) {
    @require_once __DIR__ . '/../config/db_connect.php';
}

global $language;

/* ---- ¿Es admin? ---- */
$isAdmin = false;
$userId   = $_SESSION['user_id'] ?? null;
$userName = $GLOBALS['user']
         ?? ($_SESSION['email'] ?? $_SESSION['user'] ?? $_SERVER['AUTH_USER'] ?? $_SERVER['REMOTE_USER'] ?? null);

if ($userName) {
    $userName = trim((string)$userName);
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
            $isAdmin = ($v === 'admin' || $v === '1' || $v === 'true');
        }
    }
} catch (Throwable $e) {}

/* ---- Flags de visibilidad para el MENÚ (usar solo show_module) ----
   Controlamos sólo los que tienen entrada en el menú: learn, forum, community, store.
   (El botón "Bases / Legal" se controla en topbar.php)
*/
$menuFlags = ['learn'=>true,'forum'=>true,'community'=>true,'store'=>true];
try {
    $st = $pdo->query("
        SELECT module_key, CONVERT(INT, show_module) AS show_module
        FROM [module_toggle]
        WHERE module_key IN ('learn','forum','community','store')
    ");
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $menuFlags[$r['module_key']] = ((int)$r['show_module'] === 1);
    }
} catch (Throwable $e) {}

$pieces = [];

// Aula
if (!empty($menuFlags['learn'])) {
    $pieces[] = '<a id="menu-learn" class="menu-item" href="/#learn">' .
                htmlspecialchars($language['menu']['learn'] ?? 'Aula') . '</a>';
}
// Foro
if (!empty($menuFlags['forum'])) {
    $pieces[] = '<a id="menu-forum" class="menu-item" href="/#forum">' .
                htmlspecialchars($language['menu']['forum'] ?? 'Foro') . '</a>';
}
// Comunidad
if (!empty($menuFlags['community'])) {
    $pieces[] = '<a id="menu-community" class="menu-item" href="/#community">' .
                htmlspecialchars($language['menu']['community'] ?? 'Comunidad') . '</a>';
}
// Tienda (ruta propia)
if (!empty($menuFlags['store'])) {
    $pieces[] = '<a id="menu-store" class="menu-item" href="/store">' .
                htmlspecialchars($language['menu']['shop'] ?? 'Tienda') . '</a>';
}

// Mi espacio (siempre visible)
$pieces[] = '<a id="menu-myspace" class="menu-item" href="/#my-space">' .
            htmlspecialchars($language['menu']['my_space'] ?? 'Mi espacio') . '</a>';

// Admin (sólo si es admin)
if ($isAdmin) {
    $pieces[] = '<a id="menu-admin" class="menu-item" href="/#admin">' .
                htmlspecialchars($language['menu']['admin'] ?? 'Admin') . '</a>';
}
?>
<div class="menu">
  <nav class="menu-inner">
    <?php
      // coloca separadores sólo entre elementos visibles
      echo implode('<span class="sep" aria-hidden="true"></span>', $pieces);
    ?>
  </nav>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Mapa hash -> id del enlace, pero sólo si el elemento existe
  const map = {};
  const add = (hash, id) => { const el = document.getElementById(id); if (el) map[hash] = id; };
  add('#learn', 'menu-learn');
  add('#forum', 'menu-forum');
  add('#community', 'menu-community');
  add('#my-space', 'menu-myspace');
  add('#admin', 'menu-admin');

  function markActive(hash) {
    document.querySelectorAll('.menu-item').forEach(a => a.classList.remove('active'));
    const id = map[hash];
    if (id) { const el = document.getElementById(id); if (el) el.classList.add('active'); }
  }

  function scrollToHash(hash) {
    const target = document.querySelector(hash);
    if (!target) return;
    const headerH = document.querySelector('.menu')?.offsetHeight || 0;
    const y = target.getBoundingClientRect().top + window.pageYOffset - headerH - 8;
    window.scrollTo({ top: y, behavior: 'smooth' });
  }

  // Interceptar clicks sólo si ya estás en Home
  const selector = Object.values(map).map(id => '#' + id).join(', ');
  if (selector) {
    document.querySelectorAll(selector).forEach(a => {
      a.addEventListener('click', (ev) => {
        const href = a.getAttribute('href') || '';
        const m = href.match(/#[-\w/]+/);
        if (!m) return;
        const hash = m[0];

        const onHome = location.pathname === '/' || /\/index(\.php)?$/.test(location.pathname);
        if (onHome) {
          ev.preventDefault();
          if (location.hash !== hash) {
            location.hash = hash;           // disparará 'hashchange'
          } else {
            window.dispatchEvent(new HashChangeEvent('hashchange'));
          }
          markActive(hash);
          setTimeout(() => scrollToHash(hash), 0);
        }
      });
    });
  }

  if (location.hash && document.querySelector(location.hash)) {
    setTimeout(() => { markActive(location.hash); scrollToHash(location.hash); }, 0);
  } else {
    markActive('#learn');
  }

  window.addEventListener('hashchange', () => {
    markActive(location.hash);
    scrollToHash(location.hash);
  });
});
</script>
