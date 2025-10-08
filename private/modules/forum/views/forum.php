<?php
// ===== Obtener el id del usuario logado =====
if (session_status() === PHP_SESSION_NONE) { session_start(); }
global $pdo;

/* ====== Helpers de sanitización para mostrar HTML seguro ====== */
function forum_sanitize_html(string $html): string {
    $allowed = ['b','strong','i','em','u','br','ul','ol','li','p','h2','h3','a'];

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div id="__wrap">'.$html.'</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    foreach ($xpath->query('//*') as $node) {
        $tag = strtolower($node->nodeName);
        if ($tag === 'div' && $node->getAttribute('id') === '__wrap') continue; // wrapper interno

        if (!in_array($tag, $allowed, true)) {
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
            continue;
        }

        // Limpieza de atributos
        if ($node->hasAttributes()) {
            $rm = [];
            foreach (iterator_to_array($node->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                if ($tag === 'a' && $name === 'href') {
                    $href = trim($attr->nodeValue);
                    if (!preg_match('#^https?://#i', $href)) { $rm[] = $name; continue; }
                    $node->setAttribute('rel', 'noopener noreferrer');
                    if (!$node->hasAttribute('target')) $node->setAttribute('target', '_blank');
                } else {
                    $rm[] = $name; // quitamos todo lo demás (style, onclick, etc.)
                }
            }
            foreach ($rm as $k) { $node->removeAttribute($k); }
        }
    }

    $wrap = $doc->getElementById('__wrap');
    $out = '';
    if ($wrap) {
        foreach ($wrap->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
    }
    return $out;
}

function forum_sanitize_inline(string $html): string {
    // Para títulos: solo formato inline básico
    $html = strip_tags($html, '<b><strong><i><em><u><br>');
    $html = preg_replace('#javascript:#i', '', $html);
    return $html;
}

function forum_safe_url(?string $url): string {
    $u = trim((string)$url);
    if ($u === '' || !preg_match('#^https?://#i', $u)) return '';
    return htmlspecialchars($u, ENT_QUOTES, 'UTF-8');
}

/**
 * Intenta obtener el id del usuario actual:
 * 1) Por varias claves de $_SESSION (si ya lo guardas)
 * 2) Por la variable $user (usuario/email mostrado en el topbar) -> lookup en DB
 */
function forum_current_user_id_from_any(): ?int {
    // 1) Posibles rutas en $_SESSION
    $candidatos = [
        ['user','id'], ['usuario','id'], ['auth','id'], ['profile','id'],
        ['id_user'], ['user_id'], ['id'],
    ];
    foreach ($candidatos as $path) {
        $v = $_SESSION;
        $ok = true;
        foreach ($path as $k) { if (!is_array($v) || !array_key_exists($k, $v)) { $ok=false; break; } $v = $v[$k]; }
        if ($ok && (int)$v > 0) return (int)$v;
    }

    // 2) Variable $user (lo que se muestra en el topbar) => buscar id en DB
    if (isset($GLOBALS['user']) && is_string($GLOBALS['user']) && trim($GLOBALS['user']) !== '') {
        $usuario = trim($GLOBALS['user']);
        try {
            $pdo = $GLOBALS['pdo'] ?? null;
            if ($pdo) {
                $st = $pdo->prepare("SELECT TOP (1) id FROM [user] WHERE usuario = ?");
                $st->execute([$usuario]);
                $id = (int)$st->fetchColumn();
                if ($id > 0) return $id;
            }
        } catch (Throwable $e) { /* noop */ }
    }

    return null;
}
$currentUserId = forum_current_user_id_from_any();

// ===== POST: Apuntarse =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action__'] ?? '') === 'forum_join') {
    $joinForumId = (int)($_POST['forum_id'] ?? 0);
    if ($joinForumId > 0 && $currentUserId) {
        require_once __DIR__ . '/../models/Forum.php';
        $mdl = new Forum();
        $ok  = $mdl->join($joinForumId, $currentUserId);
        $_SESSION['flash_forum_ok']  = $ok ? '¡Apuntado!' : 'No se ha podido completar la acción.';
    } else {
        $_SESSION['flash_forum_err'] = 'Debes iniciar sesión para apuntarte.';
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '#forum');
    exit;
}

// ===== Locale/formatter y carga de eventos (pasa $currentUserId) =====
$locale = (defined('DEFAULT_LANG') && strtolower(DEFAULT_LANG) === 'eu') ? 'eu_ES' : 'es_ES';
$fmtMes = null;
if (class_exists('IntlDateFormatter')) {
    $fmtMes = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, 'MMM');
}
if (!isset($events)) {
    require_once __DIR__ . '/../models/Forum.php';
    $mdl = new Forum();
    $lang = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
    $fallback = ($lang === 'es') ? 'eu' : 'es';
    $events = $mdl->getAll($lang, $fallback, $currentUserId);
}

$ok  = $_SESSION['flash_forum_ok']  ?? null;
$err = $_SESSION['flash_forum_err'] ?? null;
unset($_SESSION['flash_forum_ok'], $_SESSION['flash_forum_err']);
?>


<section id="forum" class="forum-hero">
  <h1><?= htmlspecialchars($language['forum']['title'] ?? 'FORO OREKA') ?></h1>
  <p><?= htmlspecialchars($language['forum']['subtitle'] ?? 'Aquí encontrarás todos nuestros webinars, talleres y actividades. ¡Participa!') ?></p>
</section>

<?php if ($ok): ?><div class="flash ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<section class="forum-list">
  <?php if (empty($events)): ?>
    <div class="empty"><?= htmlspecialchars($language['forum']['empty'] ?? 'No hay eventos publicados todavía.') ?></div>
  <?php else: ?>
    <?php foreach ($events as $e):
      // Claves: id, forum_title, description, url, date_start, date_finish, joined_by_user
      $dtStart = !empty($e['date_start'])  ? new DateTime($e['date_start'])  : null;
      $dtEnd   = !empty($e['date_finish']) ? new DateTime($e['date_finish']) : null;

      // Fecha/Hora
      $fechaIni = $dtStart ? $dtStart->format('d/m/Y') : '';
      $fechaFin = $dtEnd   ? $dtEnd->format('d/m/Y')   : '';
      $horaIni  = $dtStart ? $dtStart->format('H:i')   : '';
      $horaFin  = $dtEnd   ? $dtEnd->format('H:i')     : '';

      // Cabecera “tarjeta” (día/mes corto)
      if ($dtStart) {
          $day = $dtStart->format('d');
          $mon = $fmtMes ? strtoupper($fmtMes->format($dtStart)) : strtoupper($dtStart->format('M'));
      } else {
          $day = '--'; $mon = '--';
      }

      // Misma fecha → mostrar solo una vez
      $sameDay = $dtStart && $dtEnd && $fechaIni === $fechaFin;
      $franja  = $sameDay
                  ? ($fechaIni . ' — ' . trim($horaIni . ($horaFin ? ' - ' . $horaFin : '')) . 'h')
                  : (trim($fechaIni . ' ' . $horaIni) . 'h' . ' → ' . trim($fechaFin . ' ' . $horaFin) . 'h');

      $url     = trim($e['url'] ?? '');
      $hasUrl  = forum_safe_url($url) !== '';
      $joined  = !empty($e['joined_by_user']);
    ?>
      <article class="forum-item">
        <div class="forum-date">
          <div class="day"><?= htmlspecialchars($day) ?></div>
          <div class="mon"><?= htmlspecialchars($mon) ?></div>
        </div>

        <div class="forum-body">
          <?php if ($franja): ?>
            <div class="forum-meta">
              <span class="meta">⏰ <?= htmlspecialchars($franja) ?></span>
            </div>
          <?php endif; ?>

          <!-- TÍTULO con formato inline permitido -->
          <h3 class="forum-title"><?= forum_sanitize_inline($e['forum_title'] ?? '') ?></h3>

          <!-- DESCRIPCIÓN permitiendo HTML seguro -->
          <?php if (!empty($e['description'])): ?>
            <div class="forum-desc"><?= forum_sanitize_html($e['description']) ?></div>
          <?php endif; ?>

          <!-- URL saneada (http/https) -->
          <?php if ($hasUrl): ?>
            <?php $safeUrl = forum_safe_url($url); ?>
            <div class="forum-link">
              <a href="<?= $safeUrl ?>" target="_blank" rel="noopener"><?= $safeUrl ?></a>
            </div>
          <?php endif; ?>
        </div>
        <?php $joined = !empty($e['joined_by_user']); ?>
        <div class="forum-cta">
          <?php if ($joined): ?>
            <button class="btn forum-join joined" type="button" disabled>
              <?= htmlspecialchars($language['forum']['joined'] ?? 'Apuntado') ?>
            </button>
          <?php else: ?>
            <form method="post" action="" style="margin:0">
              <input type="hidden" name="__action__" value="forum_join">
              <input type="hidden" name="forum_id"  value="<?= (int)$e['id'] ?>">
              <button class="btn forum-join" type="submit">
                <?= htmlspecialchars($language['forum']['cta'] ?? 'Apuntarme') ?>
              </button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<style>
.forum-join.joined{
  background:#d8ffd8; color:#155724; border:1px solid #28a745;
}
</style>
