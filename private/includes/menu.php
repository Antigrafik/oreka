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
        $stmt = $pdo->prepare("SELECT roles FROM [user] WHERE usuario = :usuario");
        $stmt->execute([':usuario' => $userName]);
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

$pieces = [
  '<a href="/#learn" class="menu-item"><i class="fa-solid fa-graduation-cap"></i><span>Aula</span></a>',
  '<a href="/#forum" class="menu-item"><i class="fa-solid fa-comments"></i><span>Foro</span></a>',
  '<a href="/#community" class="menu-item"><i class="fa-solid fa-users"></i><span>Comunidad</span></a>',
  '<a href="/#my-space" class="menu-item"><i class="fa-solid fa-user"></i><span>Mi espacio</span></a>',
  '<a href="/#admin" class="menu-item"><i class="fa-solid fa-gear"></i><span>Admin</span></a>',
];

?>

<div class="menu">
    <button id="menuToggle" class="menu-toggle">
        <i class="fa-solid fa-chevron-right"></i>
        <span>Cerrar</span>
    </button>
    <nav class="menu-inner">

        <?php
        // coloca separadores sólo entre elementos visibles
        echo implode('<span class="sep" aria-hidden="true"></span>', $pieces);
        ?>
    </nav>
    <?php if ($showLegalButton): ?>
  <div class="menu-legal">
    <a href="#legal" class="menu-item legal" title="<?= htmlspecialchars($language['topbar']['legal'] ?? 'Bases / Legal') ?>">
      <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
      <span><?= htmlspecialchars($language['topbar']['legal'] ?? 'Bases / Legal') ?></span>
    </a>
  </div>
<?php endif; ?>

</div>