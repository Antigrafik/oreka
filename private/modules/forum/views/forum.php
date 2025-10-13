<section id="forum" class="title-section">
  <h1><?= htmlspecialchars($language['forum']['title'] ?? 'FORO OREKA') ?></h1>
  <p><?= htmlspecialchars($language['forum']['subtitle'] ?? 'Aquí encontrarás todos nuestros webinars, talleres y actividades. ¡Participa!') ?></p>
</section>
 
<?php
  // Recoger fechas de eventos para el calendario
  $eventDates = [];
  foreach ($events ?? [] as $e) {
    if (!empty($e['date_start'])) {
      $eventDates[] = (new DateTime($e['date_start']))->format('Y-m-d');
    }
  }

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
 
<div class="forum-wrapper">
  <section class="forum-list">
    <?php if (empty($events)): ?>
      <div class="empty"><?= htmlspecialchars($language['forum']['empty'] ?? 'No hay eventos publicados todavía.') ?></div>
    <?php else: ?>
      <?php foreach ($events as $e):
        $dtStart = !empty($e['date_start'])  ? new DateTime($e['date_start'])  : null;
        $dtEnd   = !empty($e['date_finish']) ? new DateTime($e['date_finish']) : null;
        if ($dtEnd && $dtEnd <= new DateTime()) continue;
 
        $day = $dtStart ? $dtStart->format('d') : '--';
        $mon = $dtStart ? strtoupper($fmtMes ? $fmtMes->format($dtStart) : $dtStart->format('M')) : '--';
        $horaIni = $dtStart ? $dtStart->format('H:i') : '';
        $horaFin = $dtEnd   ? $dtEnd->format('H:i')   : '';
        $url     = trim($e['url'] ?? '');
        $hasUrl  = $url !== '';
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
            <?php if ($hasUrl): ?>
              <a class="btn forum-join" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">
                <?= htmlspecialchars($language['forum']['cta'] ?? 'Apuntarme') ?>
              </a>
            <?php else: ?>
              <button class="btn forum-join" type="button" disabled>
                <?= htmlspecialchars($language['forum']['cta'] ?? 'Apuntarme') ?>
              </button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
 
  <?php if (!empty($eventDates)): ?>
    <aside class="forum-calendar" data-event-days='<?= json_encode($eventDates) ?>'>
      <h2 id="calendar-month-title">...</h2>
      <div id="calendar-container"></div>
    </aside>
  <?php endif; ?>
</div>
