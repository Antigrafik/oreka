<?php
// Helper para ID de usuario actual (ajústalo a tu sesión real)
function forum_current_user_id(): ?int {
    if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] > 0) return (int)$_SESSION['user']['id'];
    if (isset($_SESSION['id_user']) && (int)$_SESSION['id_user'] > 0) return (int)$_SESSION['id_user'];
    return null;
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../models/Forum.php';

// Procesar “Apuntarme”
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action__'] ?? '') === 'forum_join') {
    $uid = forum_current_user_id();
    $fid = isset($_POST['forum_id']) ? (int)$_POST['forum_id'] : 0;

    if (!$uid) {
        $_SESSION['flash_forum_err'] = $language['forum']['login_required'] ?? 'Inicia sesión para apuntarte.';
    } elseif ($fid > 0) {
        $ok = (new Forum())->join($fid, $uid);
        $_SESSION['flash_forum_ok'] = $ok
            ? ($language['forum']['joined_ok'] ?? 'Te has apuntado correctamente.')
            : ($language['forum']['joined_err'] ?? 'No se ha podido completar la inscripción.');
    }
    // Redirección limpia (evitar reenvío de formulario)
    $base = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $base . '#forum');
    exit;
}

// Cargar eventos visibles
$lang = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
$fallback = ($lang === 'es') ? 'eu' : 'es';
$uid = forum_current_user_id();
$events = (new Forum())->getAll($lang, $fallback, $uid);

// Locale para los meses
$locale = ($lang === 'eu') ? 'eu_ES' : 'es_ES';
$fmtMes = null;
if (class_exists('IntlDateFormatter')) {
    $fmtMes = new IntlDateFormatter(
        $locale,
        IntlDateFormatter::NONE,
        IntlDateFormatter::NONE,
        date_default_timezone_get(),
        IntlDateFormatter::GREGORIAN,
        'MMM'
    );
}

// Flashes
$ok  = $_SESSION['flash_forum_ok']  ?? null;
$err = $_SESSION['flash_forum_err'] ?? null;
unset($_SESSION['flash_forum_ok'], $_SESSION['flash_forum_err']);
?>

<section id="forum" class="forum-hero">
  <h1><?= htmlspecialchars($language['forum']['title'] ?? 'FORO OREKA') ?></h1>
  <p><?= htmlspecialchars($language['forum']['subtitle'] ?? 'Aquí encontrarás todos nuestros webinars, talleres y actividades. ¡Participa!') ?></p>
</section>

<?php if ($ok):  ?><div class="flash ok"><?= htmlspecialchars($ok)  ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<section class="forum-list">
  <?php if (empty($events)): ?>
    <div class="empty"><?= htmlspecialchars($language['forum']['empty'] ?? 'No hay eventos publicados todavía.') ?></div>
  <?php else: ?>
    <?php foreach ($events as $e):
      // Claves: id, category_name, forum_title, description, url, date_start, date_finish, joined
      $dtStart = !empty($e['date_start'])  ? new DateTime($e['date_start'])  : null;
      $dtEnd   = !empty($e['date_finish']) ? new DateTime($e['date_finish']) : null;

      if ($dtStart) {
          $day = $dtStart->format('d');
          $mon = $fmtMes ? strtoupper($fmtMes->format($dtStart)) : strtoupper($dtStart->format('M'));
      } else {
          $day = '--'; $mon = '--';
      }

      $sameDay = ($dtStart && $dtEnd) ? ($dtStart->format('Y-m-d') === $dtEnd->format('Y-m-d')) : false;
      $fechaTxt = '';
      if ($dtStart && $dtEnd) {
          if ($sameDay) {
              $fechaTxt = $dtStart->format('d/m/Y') . ' — ' . $dtStart->format('H:i') . '–' . $dtEnd->format('H:i') . 'h';
          } else {
              $fechaTxt = $dtStart->format('d/m/Y H:i') . ' — ' . $dtEnd->format('d/m/Y H:i') . 'h';
          }
      } elseif ($dtStart) {
          $fechaTxt = $dtStart->format('d/m/Y H:i') . 'h';
      }

      $url     = trim($e['url'] ?? '');
      $hasUrl  = $url !== '';
      $joined  = !empty($e['joined']);
    ?>
      <article class="forum-item">
        <div class="forum-date">
          <div class="day"><?= htmlspecialchars($day) ?></div>
          <div class="mon"><?= htmlspecialchars($mon) ?></div>
        </div>

        <div class="forum-body">
          <div class="forum-meta">
            <?php if (!empty($e['category_name'])): ?>
              <span class="tag"><?= htmlspecialchars($e['category_name']) ?></span>
            <?php endif; ?>
            <?php if ($fechaTxt): ?>
              <span class="meta">⏰ <?= htmlspecialchars($fechaTxt) ?></span>
            <?php endif; ?>
          </div>

          <h3 class="forum-title"><?= htmlspecialchars($e['forum_title'] ?? '') ?></h3>

          <?php if (!empty($e['description'])): ?>
            <div class="forum-desc"><?= $e['description'] /* ya viene como HTML desde translation */ ?></div>
          <?php endif; ?>
        </div>

        <div class="forum-cta">
          <?php if (!$uid): ?>
            <a class="btn forum-join" href="/login"><?= htmlspecialchars($language['forum']['login_to_join'] ?? 'Inicia sesión para apuntarte') ?></a>
          <?php elseif ($joined): ?>
            <button class="btn forum-join joined" type="button" disabled><?= htmlspecialchars($language['forum']['joined'] ?? 'Apuntado') ?></button>
          <?php else: ?>
            <form method="post" action="" onsubmit="this.querySelector('button').disabled=true;">
              <input type="hidden" name="__action__" value="forum_join">
              <input type="hidden" name="forum_id"  value="<?= (int)$e['id'] ?>">
              <button class="btn forum-join" type="submit"><?= htmlspecialchars($language['forum']['cta'] ?? 'Apuntarme') ?></button>
            </form>
          <?php endif; ?>

          <?php if ($hasUrl): ?>
            <a class="btn forum-link" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">
              <?= htmlspecialchars($language['forum']['more'] ?? 'Ver enlace') ?>
            </a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<style>
/* Estilos mínimos para el botón “Apuntado” */
.btn.forum-join.joined {
  background: #0aa;
  color: #fff;
  opacity: 1;
  cursor: default;
}
</style>
