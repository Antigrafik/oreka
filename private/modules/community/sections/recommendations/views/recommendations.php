<?php
/** @var array $recommendations */
/** @var array $themes */
/** @var array $supports */
/** @var array $language */

// --- Flash / errores / old de ESTA sección (y limpiarlos) ---
$flash  = $_SESSION['flash_msg_reco'] ?? null;
$errors = $_SESSION['errors_reco']    ?? [];
$old    = $_SESSION['old_reco']       ?? [];

unset($_SESSION['flash_msg_reco'], $_SESSION['errors_reco'], $_SESSION['old_reco']);

if (!function_exists('lower_ascii')) {
  function lower_ascii(string $s): string { return strtolower(trim($s)); }
}
?>

<section id="community-recommendations" class="recommendations">
  <h2><?= htmlspecialchars($language['recommendations']['title'] ?? 'Recommendations') ?></h2>
  <p class="lead"><?= htmlspecialchars($language['recommendations']['subtitle'] ?? '') ?></p>

  <?php if (empty($recommendations)): ?>
    <div class="empty"><?= htmlspecialchars($language['recommendations']['empty'] ?? 'No hay recomendaciones.') ?></div>
  <?php else: ?>

    <div class="filters">
      <label class="filter">
        <span><?= htmlspecialchars($language['recommendations']['theme'] ?? 'Tema') ?></span>
        <select id="filter-tema">
          <option value=""><?= htmlspecialchars($language['recommendations']['all'] ?? 'Todos') ?></option>
          <?php foreach (($themes ?? []) as $t): ?>
            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="filter">
        <span><?= htmlspecialchars($language['recommendations']['support'] ?? 'Soporte') ?></span>
        <select id="filter-soporte">
          <option value=""><?= htmlspecialchars($language['recommendations']['all'] ?? 'Todos') ?></option>
          <?php foreach (($supports ?? []) as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="filter">
        <span><?= htmlspecialchars($language['recommendations']['sort_by'] ?? 'Ordenar') ?></span>
        <select id="order-by">
          <option value="likes">Más likes</option>
          <option value="recent"><?= htmlspecialchars($language['recommendations']['recent'] ?? 'Recientes') ?></option>
        </select>
      </label>

      <label class="filter search">
        <input id="search-recs" type="search"
               placeholder="<?= htmlspecialchars($language['recommendations']['search'] ?? 'Buscar...') ?>"
               autocomplete="off">
      </label>
    </div>

    <section class="learn-slider recomendation-slider">
      <button class="nav prev" aria-label="Anterior">‹</button>

      <div class="viewport">
        <ul class="track">
          <?php foreach ($recommendations as $r): ?>
            <?php
              $tema_id    = (int)($r['tema_id'] ?? 0);
              $soporte_id = (int)($r['soporte_id'] ?? 0);
              $tema       = $r['tema'] ?? '';
              $soporte    = $r['soporte'] ?? '';
              $title      = $r['title'] ?? '';
              $desc       = $r['description'] ?? '';
              $author     = $r['content_author'] ?? '';
              $by_user    = $r['recommended_by'] ?? '';
              $likes      = (int)($r['likes'] ?? 0);
              $dateSort   = $r['date_start'] ?? '';
            ?>
            <li class="slide"
                data-tema-id="<?= $tema_id ?>"
                data-soporte-id="<?= $soporte_id ?>"
                data-likes="<?= $likes ?>"
                data-date="<?= htmlspecialchars($dateSort) ?>">
              <article class="card">
                <div class="card-body">
                  <?php if ($tema): ?><span class="badge"><?= htmlspecialchars($tema) ?></span><?php endif; ?>
                  <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
                  <?php if ($soporte): ?><p class="card-subtitle"><?= htmlspecialchars($soporte) ?></p><?php endif; ?>
                  <?php if ($desc): ?><p class="card-text"><?= nl2br(htmlspecialchars($desc)) ?></p><?php endif; ?>
                  <div class="card-footer">
                    <div class="bylines">
                      <span class="byline"><?= 'Autoría de ' . htmlspecialchars($author ?: '—') ?>.</span>
                      <span class="byline"><?= 'Recomendado por ' . htmlspecialchars($by_user ?: '—') ?>.</span>
                    </div>
                    <span class="likes"
                          role="button"
                          title="<?= htmlspecialchars($language['recommendations']['like'] ?? 'Me gusta') ?>"
                          data-rec-id="<?= (int)($r['recommendation_id'] ?? 0) ?>"
                          data-link-id="<?= (int)($r['link_id'] ?? 0) ?>"
                          aria-pressed="false">
                      <svg class="heart" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">
                        <path d="M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 5.65-7 10-7 10z"></path>
                      </svg>
                      <span class="likes-count"><?= number_format($likes, 0, ',', '.') ?></span>
                    </span>
                  </div>
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

<!-- === Crear nueva recomendación === -->
<section class="recommendation-create">
  <h2>Añade tu recomendación</h2>
  <p class="lead">Comparte aquello que te inspira y gana puntos.</p>

  <?php if (!empty($flash)): ?>
    <div class="alert success"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert error">
      <ul style="margin:0; padding-left:18px;">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form id="form-recommendation" class="rec-form" method="post" action="">
    <!-- Identificador para el controller (solo esta sección procesa el POST) -->
    <input type="hidden" name="form" value="new_recommendation">
    <!-- CSRF si lo usas -->
    <?php if (!empty($_SESSION['csrf'])): ?>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
    <?php endif; ?>

    <div class="rec-grid">
      <!-- Idioma -->
      <label class="field">
        <span>Idioma</span>
        <?php $post_lang = $old['lang'] ?? ($lang ?? 'es'); ?>
        <select name="lang" required>
          <option value="es" <?= ($post_lang === 'es' ? 'selected' : '') ?>>Español</option>
          <option value="eu" <?= ($post_lang === 'eu' ? 'selected' : '') ?>>Euskera</option>
        </select>
      </label>

      <!-- Tema (hijos de padre 10) -->
      <label class="field">
        <span>Tema</span>
        <?php $post_tema = isset($old['tema_id']) ? (int)$old['tema_id'] : 0; ?>
        <select name="tema_id" required>
          <option value="">Selecciona</option>
          <?php foreach (($themes ?? []) as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= ($post_tema === (int)$t['id'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($t['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <!-- Soporte (hijos de padre 11) -->
      <label class="field">
        <span>Soporte</span>
        <?php $post_soporte = isset($old['soporte_id']) ? (int)$old['soporte_id'] : 0; ?>
        <select name="soporte_id" required>
          <option value="">Selecciona</option>
          <?php foreach (($supports ?? []) as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= ($post_soporte === (int)$s['id'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <!-- Título -->
      <label class="field">
        <span>Título</span>
        <input
          type="text"
          name="title"
          placeholder="Título de la obra"
          maxlength="255"
          required
          value="<?= htmlspecialchars($old['title'] ?? '') ?>">
      </label>

      <!-- Autor -->
      <label class="field">
        <span>Autor</span>
        <input
          type="text"
          name="author"
          placeholder="Autor / Creador"
          maxlength="255"
          required
          value="<?= htmlspecialchars($old['author'] ?? '') ?>">
      </label>

      <!-- Comentario -->
      <label class="field span-2">
        <span>Comentario</span>
        <textarea
          name="comment"
          rows="3"
          placeholder="¿Por qué lo recomiendas?"
          maxlength="2000"
          required><?= htmlspecialchars($old['comment'] ?? '') ?></textarea>
      </label>
    </div>

    <button class="btn btn-primary" type="submit">Enviar recomendación</button>
  </form>
</section>

<style>
  .recommendation-create { margin-top: 40px; }
  .rec-form .rec-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }
  .rec-form .field { display:flex; flex-direction:column; gap:6px; }
  .rec-form .field.span-2 { grid-column: span 2; }
  .rec-form input, .rec-form select, .rec-form textarea {
    padding: 10px 12px; border:1px solid #ddd; border-radius: 8px; outline: none;
  }
  .rec-form .btn { margin-top: 12px; padding: 10px 16px; border-radius: 10px; border:0; cursor:pointer; }
  .btn-primary { background:#d1102d; color:white; }
  .alert { padding:10px 12px; border-radius:8px; margin:10px 0; }
  .alert.success { background:#e7f7ee; border:1px solid #8ad1a3; color:#216b3a; }
  .alert.error { background:#fdeaea; border:1px solid #f5b5b5; color:#7d1f1f; }
</style>
