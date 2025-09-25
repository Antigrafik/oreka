<?php
declare(strict_types=1);
session_start();

if (!isset($pdo)) {
    @require_once __DIR__ . '/../config/db_connect.php';
}

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

    <!-- CAMBIO: Mi espacio usa hash en la home -->
    <a id="menu-myspace" class="menu-item" href="/#my-space"><?= htmlspecialchars($language['menu']['my_space'] ?? 'Mi espacio') ?></a>

    <?php if ($isAdmin): ?>
      <span class="sep" aria-hidden="true"></span>
      <a id="menu-admin" class="menu-item" href="/#admin"><?= htmlspecialchars($language['menu']['admin'] ?? 'Admin') ?></a>
    <?php endif; ?>
  </nav>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // ids de los anchors y sus enlaces en el menú
  const map = {
    '#learn'     : 'menu-learn',
    '#forum'     : 'menu-forum',
    '#community' : 'menu-community',
    '#my-space'  : 'menu-myspace',   // ← añadido
    '#admin'     : 'menu-admin'
  };

  function markActive(hash) {
    document.querySelectorAll('.menu-item').forEach(a => a.classList.remove('active'));
    const id = map[hash];
    if (id) document.getElementById(id)?.classList.add('active');
  }

  function scrollToHash(hash) {
    const target = document.querySelector(hash);
    if (!target) return;
    const headerH = document.querySelector('.menu')?.offsetHeight || 0;
    const y = target.getBoundingClientRect().top + window.pageYOffset - headerH - 8;
    window.scrollTo({ top: y, behavior: 'smooth' });
  }

  // Interceptar clicks solo si ya estás en Home
  document.querySelectorAll('#menu-learn, #menu-forum, #menu-community, #menu-myspace, #menu-admin')
    .forEach(a => {
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
