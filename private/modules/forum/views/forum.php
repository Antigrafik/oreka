<?php
// Locale según idioma activo
$locale = (defined('DEFAULT_LANG') && strtolower(DEFAULT_LANG) === 'eu') ? 'eu_ES' : 'es_ES';

// Formateador de mes corto (Ene/Jan → siempre mayúsculas)
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
?>

<section id="forum" class="forum-hero">
  <h1><?= htmlspecialchars($language['forum']['title'] ?? 'FORO OREKA') ?></h1>
  <p><?= htmlspecialchars($language['forum']['subtitle'] ?? 'Aquí encontrarás todos nuestros webinars, talleres y actividades. ¡Participa!') ?></p>
</section>

<section class="forum-list">
  <?php if (empty($events)): ?>
    <div class="empty"><?= htmlspecialchars($language['forum']['empty'] ?? 'No hay eventos publicados todavía.') ?></div>
  <?php else: ?>
    <?php foreach ($events as $e):
      // Claves esperadas: category_name, forum_title, description, url, date_start, date_finish
      $dtStart = !empty($e['date_start'])  ? new DateTime($e['date_start'])  : null;
      $dtEnd   = !empty($e['date_finish']) ? new DateTime($e['date_finish']) : null;

      // (Por si el filtro de la SQL fallara) excluir eventos terminados
      if ($dtEnd && $dtEnd <= new DateTime()) { continue; }

      if ($dtStart) {
          $day = $dtStart->format('d');
          $mon = $fmtMes ? strtoupper($fmtMes->format($dtStart)) : strtoupper($dtStart->format('M'));
      } else {
          $day = '--'; $mon = '--';
      }

      $horaIni = $dtStart ? $dtStart->format('H:i') : '';
      $horaFin = $dtEnd   ? $dtEnd->format('H:i')   : '';
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
            <?php if ($horaIni || $horaFin): ?>
              <span class="meta">⏰ <?= htmlspecialchars(trim($horaIni . ($horaFin ? ' - ' . $horaFin : ''))) ?>h</span>
            <?php endif; ?>
          </div>

          <h3 class="forum-title"><?= htmlspecialchars($e['forum_title'] ?? '') ?></h3>

          <?php if (!empty($e['description'])): ?>
            <p class="forum-desc"><?= nl2br(htmlspecialchars($e['description'])) ?></p>
          <?php endif; ?>
        </div>

        <div class="forum-cta">
            <a class="btn forum-join" href="" target="_blank" rel="noopener">
              <?= htmlspecialchars($language['forum']['cta'] ?? 'Apuntarme') ?>
            </a>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

