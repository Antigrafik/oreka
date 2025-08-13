<?php
// ---- Formateador de mes corto en español (p.ej., JUN) ----
$fmtMes = null;
if (class_exists('IntlDateFormatter')) {
    $fmtMes = new IntlDateFormatter(
        'es_ES',
        IntlDateFormatter::NONE,
        IntlDateFormatter::NONE,
        date_default_timezone_get(),
        IntlDateFormatter::GREGORIAN,
        'MMM'
    );
}
?>

<section id="forum" class="forum-hero">
  <h1>FORO OREKA</h1>
  <p>Aquí encontrarás todos nuestros webinars, talleres y actividades. ¡Participa!</p>
</section>

<section class="forum-list">
  <?php if (empty($events)): ?>
    <div class="empty">No hay eventos publicados todavía.</div>
  <?php else: ?>
    <?php foreach ($events as $e): 
      // Fechas seguras
      $dtStart = !empty($e['date_start'])  ? new DateTime($e['date_start'])  : null;
      $dtEnd   = !empty($e['date_finish']) ? new DateTime($e['date_finish']) : null;

      // Día y mes (sin strftime)
      if ($dtStart) {
          $day = $dtStart->format('d');
          $mon = $fmtMes ? strtoupper($fmtMes->format($dtStart))
               : strtoupper($dtStart->format('M'));

      } else {
          $day = '--';
          $mon = '--';
      }

      // Horas
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
            <span class="tag">Taller</span>
            <?php if ($horaIni || $horaFin): ?>
              <span class="meta">⏰ <?= htmlspecialchars(trim($horaIni . ($horaFin ? ' - ' . $horaFin : ''))) ?>h</span>
            <?php endif; ?>
          </div>

          <h3 class="forum-title"><?= htmlspecialchars($e['title']) ?></h3>

          <?php if (!empty($e['description'])): ?>
            <p class="forum-desc"><?= nl2br(htmlspecialchars($e['description'])) ?></p>
          <?php endif; ?>
        </div>

        <div class="forum-cta">
          <?php if (!empty($e['url'])): ?>
            <a class="btn forum-join" href="<?= htmlspecialchars($e['url']) ?>" target="_blank" rel="noopener">Apuntarme</a>
          <?php else: ?>
            <button class="btn forum-join" disabled>Próximamente</button>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
