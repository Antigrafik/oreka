<?php
global $language;

global $pdo;
$show = true;
try {
  $st = $pdo->prepare("SELECT show_module FROM [module_toggle] WHERE module_key = 'meeting'");
  $st->execute();
  $v = $st->fetchColumn();
  if ($v !== false) $show = ((int)$v === 1);
} catch (Throwable $e) {}
if (!$show) return;

$flash  = $_SESSION['flash_msg_meeting'] ?? null;
$errors = $_SESSION['errors_meeting']    ?? [];
$old    = $_SESSION['old_meeting']       ?? [];
unset($_SESSION['flash_msg_meeting'], $_SESSION['errors_meeting'], $_SESSION['old_meeting']);

$L = $language['meeting'] ?? [];
?>

<section id="community-meeting" class="subtitle-section">
  <h2><?= htmlspecialchars($L['title'] ?? 'Quedadas deportivas') ?></h2>
  <p class="lead"><?= htmlspecialchars($L['subtitle'] ?? '¿Buscas compañeras/os para tu equipo, organizar un torneo o una quedada?') ?></p>
 
  <?php if (empty($meetings)): ?>
    <div class="empty-color"><?= htmlspecialchars($L['empty'] ?? 'No hay quedadas todavía.') ?></div>
  <?php else: ?>
    <section class="learn-slider meeting-slider">
      <button class="nav prev" aria-label="Anterior">‹</button>
      <div class="viewport">
        <ul class="track">
          <?php foreach ($meetings as $m): ?>
            <li class="slide" data-date="<?= htmlspecialchars($m['date_start']) ?>">
              <article class="card">
                <div class="card-body">
                  <h3 class="card-title"><?= htmlspecialchars($m['activity'] ?: ($L['activity_fallback'] ?? 'Actividad')) ?></h3>
                  <p class="card-subtitle"><?= htmlspecialchars($m['place'] ?: '—') ?></p>
                  <p class="card-text">
                    <strong><?= htmlspecialchars($L['date'] ?? 'Fecha') ?>:</strong>
                    <?= htmlspecialchars($m['date_start']) ?><br>
                    <?php if (!empty($m['email'])): ?>
                      <strong><?= htmlspecialchars($L['contact'] ?? 'Contacto') ?>:</strong>
                      <a href="mailto:<?= htmlspecialchars($m['email']) ?>"><?= htmlspecialchars($m['email']) ?></a>
                    <?php endif; ?>
                  </p>
                </div>
              </article>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <button class="nav next" aria-label="Siguiente">›</button>
    </section>
  <?php endif; ?>
</section>
 
<!-- === Crear nueva quedada === -->
<section class="meeting-create">
  <button id="btn-meeting" class="btn-toggle-meeting" type="button">
    <span class="plus">+</span>
    <span class="text">Crear quedada</span>
  </button>
 
  <?php if (!empty($flash)): ?>
    <div class="alert success"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>
 
  <?php if (!empty($errors)): ?>
    <div class="alert error">
      <ul>
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
 
  <form id="form-meeting" class="rec-form hidden" method="post" action="">
 
    <input type="hidden" name="form" value="meeting_submit">
 
    <div class="rec-grid">
      <!-- Idioma -->
      <label class="field">
        <span><?= htmlspecialchars($L['lang'] ?? 'Idioma') ?></span>
        <?php $post_lang = $old['lang'] ?? 'es'; ?>
        <select name="lang" required>
          <option value="es" <?= ($post_lang === 'es' ? 'selected' : '') ?>>Español</option>
          <option value="eu" <?= ($post_lang === 'eu' ? 'selected' : '') ?>>Euskera</option>
        </select>
      </label>
 
      <!-- Actividad -->
      <label class="field span-2">
        <span><?= htmlspecialchars($L['activity_label'] ?? 'Actividad deportiva') ?></span>
        <input type="text" name="activity" required
               placeholder="<?= htmlspecialchars($L['activity_ph'] ?? 'Ej. Partido de futbito / Caminata...') ?>"
               value="<?= htmlspecialchars($old['activity'] ?? '') ?>">
      </label>
 
      <!-- Lugar -->
      <label class="field">
        <span><?= htmlspecialchars($L['place_label'] ?? 'Lugar') ?></span>
        <input type="text" name="place" required value="<?= htmlspecialchars($old['place'] ?? '') ?>">
      </label>
 
      <!-- Fecha -->
      <label class="field">
        <span><?= htmlspecialchars($L['date_label'] ?? 'Fecha') ?></span>
        <input type="date" name="date" required value="<?= htmlspecialchars($old['date'] ?? '') ?>">
      </label>
 
      <!-- Horario -->
      <label class="field">
        <span><?= htmlspecialchars($L['time_label'] ?? 'Horario') ?></span>
        <input type="time" name="time" value="<?= htmlspecialchars($old['time'] ?? '') ?>">
      </label>
 
      <!-- Email -->
      <label class="field">
        <span><?= htmlspecialchars($L['email_label'] ?? 'Email de contacto') ?></span>
        <div class="flex items-center gap-2">
          <input type="text" name="email_local"
                 placeholder="<?= htmlspecialchars($L['email_placeholder'] ?? 'tu.usuario') ?>"
                 value="<?= htmlspecialchars($old['email_local'] ?? '') ?>">
          <span><?= htmlspecialchars($L['email_domain'] ?? '@kutxabank.es') ?></span>
        </div>
      </label>
    </div>
 
    <button class="btn" type="submit"><?= htmlspecialchars($L['submit'] ?? 'Publicar quedada') ?></button>
  </form>
</section>
 