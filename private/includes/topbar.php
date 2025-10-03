<?php
global $language;
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = $_SERVER['REMOTE_USER'] ?? null;
$totalPuntos = null;

if ($user && strpos($user, '\\') !== false) {
    $user = explode('\\', $user, 2)[1];
}

if ($user) {
    try {
        $checkUser = $pdo->prepare("SELECT id FROM [user] WHERE usuario = :usuario");
        $checkUser->execute([':usuario' => $user]);
        $userData = $checkUser->fetch(PDO::FETCH_ASSOC);

       /*if (!$userData) {
            // Usuario no permitido → mostrar mensaje y salir
            echo "<h1 style='color:red; text-align:center;'>" . $language['topbar']['without_permission'] . "</h1>";
            exit;
        }*/

        $sql = "
            SELECT COALESCE(SUM(p.points), 0) AS total_puntos
            FROM [user] u
            JOIN user_activity ua ON ua.id_user = u.id
            JOIN point p        ON p.id = ua.id_point
            WHERE u.usuario = :usuario
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':usuario' => $user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalPuntos = (string)$row['total_puntos'];

    } catch (PDOException $e) {
        echo "<h1>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</h1>";
        exit;
    }
} else {
    echo "<h1 style='color:red; text-align:center;'>" . $language['topbar']['without_permission'] . "</h1>";
    exit;
}

$showLegalButton = true;
try {
  $st = $pdo->prepare("SELECT CONVERT(INT, show_module) FROM [module_toggle] WHERE module_key = 'legal'");
  $st->execute();
  $val = $st->fetchColumn();
  if ($val !== false) $showLegalButton = ((int)$val === 1);
} catch (Throwable $e) { /* deja visible si hay error */ }

?>

<!-- <header class="main-header">
  <div class="logo-left">
    <img src="/assets/images/logo_oreka.png" alt="Oreka Logo">
  </div>

  <div class="user-center">
    <span><?= htmlspecialchars($user) ?> | <?= htmlspecialchars($totalPuntos) ?> <?= htmlspecialchars($language['topbar']['points'] ?? 'puntos') ?></span>
  </div>

  <div class="logo-right-container">
    <?php if ($showLegalButton): ?>
      <a href="#legal" class="legal-link" title="<?= htmlspecialchars($language['topbar']['legal'] ?? 'Bases / Legal') ?>">
        <?= htmlspecialchars($language['topbar']['legal'] ?? 'Bases / Legal') ?>
      </a>
    <?php endif; ?>
    <img src="/assets/images/logo_kutxa.jpg" alt="Kutxa Logo" class="logo-right">
  </div>
</header> -->


<header class="main-header">
  <div class="logo-left">
    <span><?= htmlspecialchars($user) ?> | <?= htmlspecialchars($totalPuntos) ?> <?= htmlspecialchars($language['topbar']['points'] ?? 'puntos') ?></span>
 
  </div>
 
  <div class="user-center">
    <img src="/assets/images/logos/logoHorOreka.svg" alt="Kutxa Logo">
  </div>
 
  <div class="logo-right-container">
 
    <img src="/assets/images/logos/logoKB.svg" alt="Oreka Logo" class="logo-right">
  </div>
</header>