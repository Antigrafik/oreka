<?php
/* ===========================
   RECOMENDACIONES (PÚBLICO)
   Se oculta si module_toggle.show_module = 0
   =========================== */

global $language, $pdo;


global $pdo;
$show = true;
try {
  $st = $pdo->prepare("SELECT show_module FROM [module_toggle] WHERE module_key = 'recommendations'");
  $st->execute();
  $v = $st->fetchColumn();
  if ($v !== false) $show = ((int)$v === 1);
} catch (Throwable $e) {}
if (!$show) return;

/** @var array $recommendations */
/** @var array $themes */
/** @var array $supports */
/** @var array $language */

$flash  = $_SESSION['flash_msg_reco'] ?? null;
$errors = $_SESSION['errors_reco']    ?? [];
$old    = $_SESSION['old_reco']       ?? [];

unset($_SESSION['flash_msg_reco'], $_SESSION['errors_reco'], $_SESSION['old_reco']);

if (!function_exists('lower_ascii')) {
  function lower_ascii(string $s): string { return strtolower(trim($s)); }
}
?>

<section id="community-recommendations" class="subtitle-section">
  <h2><?= htmlspecialchars($language['recommendations']['title'] ?? '') ?></h2>
  <p class="lead"><?= htmlspecialchars($language['recommendations']['subtitle'] ?? '') ?></p>
 
  <?php if (empty($recommendations)): ?>
    <div class="empty"><?= htmlspecialchars($language['recommendations']['empty'] ?? '') ?></div>
  <?php else: ?>
 
  <div class="filters">
    <label class="filter">
      <select id="filter-tema">
        <option value=""><?= htmlspecialchars($language['recommendations']['select_theme'] ?? 'Selecciona un tema') ?></option>
        <?php foreach (($themes ?? []) as $t): ?>
          <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
 
    <label class="filter">
      <select id="filter-soporte">
        <option value=""><?= htmlspecialchars($language['recommendations']['select_support'] ?? 'Elige un soporte') ?></option>
        <?php foreach (($supports ?? []) as $s): ?>
          <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
 
        <label class="filter">
        <select id="order-by">
            <option value=""><?= htmlspecialchars($language['recommendations']['all'] ?? 'Todos') ?></option>
            <option value="likes"><?= htmlspecialchars($language['recommendations']['more_like'] ?? 'Más likes') ?></option>
            <option value="recent"><?= htmlspecialchars($language['recommendations']['recent'] ?? 'Más recientes') ?></option>
        </select>
        </label>
 
 
    <label class="filter search">
      <input id="search-recs" type="search"
            placeholder="<?= htmlspecialchars($language['recommendations']['search'] ?? 'Buscar...') ?>"
            autocomplete="off">
    </label>
  </div>
 
    <div class="recommendation-feed">
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
                <article class="recommendation-item"
                        data-tema-id="<?= $tema_id ?>"
                        data-soporte-id="<?= $soporte_id ?>"
                        data-likes="<?= $likes ?>"
                        data-date="<?= htmlspecialchars($dateSort) ?>">
 
                <header class="rec-meta">
                    <span class="rec-tema"><?= htmlspecialchars($tema) ?></span>
                    <span class="rec-soporte"><?= htmlspecialchars($soporte) ?></span>
                </header>
 
                <div class="rec-body">
                    <?php if ($author || $title): ?>
                    <p class="rec-header-line">
                        <?= htmlspecialchars($author) ?>
                        <?php if ($author && $title): ?> / <?php endif; ?>
                        <?= htmlspecialchars($title) ?>
                    </p>
                    <?php endif; ?>
 
                    <?php if ($desc): ?>
                      <div class="rec-text-wrapper">
                        <p class="rec-text"><?= nl2br(htmlspecialchars($desc)) ?></p>
                        <button class="read-more-btn">Leer más</button>
                      </div>
                    <?php endif; ?>
 
                </div>
 
                <footer class="rec-footer">
                    <span class="rec-user"><?= htmlspecialchars($by_user) ?></span>
                    <button class="rec-like"
                            data-rec-id="<?= (int)$r['recommendation_id'] ?>"
                            aria-pressed="false">
                    <i class="fa-regular fa-heart"></i>
                    <span class="like-count"><?= (int)$likes ?></span>
                    </button>
                </footer>
                </article>
            <?php endforeach; ?>
            </div>
            <p class="no-results" style="display:none;">
                <i class="fa-regular fa-face-frown"></i>
                No hay recomendaciones que coincidan con los filtros seleccionados.
            </p>
 
            <!-- Texto “Mostrar más” -->
            <div class="load-more-wrapper">
            <p class="load-more-text">
                Mostrar más
            </p>
          </div>
 
 
  <?php endif; ?>
</section>
 
 
<!-- === Crear nueva recomendación === -->
<section class="recommendation-create">
  <button class="btn-toggle-form" type="button">
    <span class="plus">+</span>
    <span class="text">Añadir recomendación</span>
  </button>
 
  <form id="form-recommendation" class="rec-form hidden" method="post" action="">
    <input type="hidden" name="form" value="new_recommendation">
    <?php if (!empty($_SESSION['csrf'])): ?>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
    <?php endif; ?>
 
    <div class="rec-grid">
      <!-- Idioma -->
      <label class="field">
        <span><?= htmlspecialchars($language['recommendations']['lang'] ?? 'Idioma') ?></span>
        <?php $post_lang = $old['lang'] ?? ($lang ?? 'es'); ?>
        <select name="lang" required>
          <option value="es" <?= ($post_lang === 'es' ? 'selected' : '') ?>>Español</option>
          <option value="eu" <?= ($post_lang === 'eu' ? 'selected' : '') ?>>Euskera</option>
        </select>
      </label>
 
      <!-- Tema -->
      <label class="field">
        <span><?= htmlspecialchars($language['recommendations']['theme'] ?? 'Tema') ?></span>
        <?php $post_tema = isset($old['tema_id']) ? (int)$old['tema_id'] : 0; ?>
        <select name="tema_id" required>
          <option value=""><?= htmlspecialchars($language['recommendations']['select'] ?? 'Selecciona un tema') ?></option>
          <?php foreach (($themes ?? []) as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= ($post_tema === (int)$t['id'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($t['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
 
      <!-- Soporte -->
      <label class="field">
        <span><?= htmlspecialchars($language['recommendations']['support'] ?? 'Soporte') ?></span>
        <?php $post_soporte = isset($old['soporte_id']) ? (int)$old['soporte_id'] : 0; ?>
        <select name="soporte_id" required>
          <option value=""><?= htmlspecialchars($language['recommendations']['select'] ?? 'Selecciona') ?></option>
          <?php foreach (($supports ?? []) as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= ($post_soporte === (int)$s['id'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
 
      <!-- Título -->
      <label class="field">
        <span><?= htmlspecialchars($language['recommendations']['title_label'] ?? 'Título') ?></span>
        <input type="text" name="title"
               placeholder="<?= htmlspecialchars($language['recommendations']['title_ph'] ?? 'Título de la obra') ?>"
               maxlength="255" required
               value="<?= htmlspecialchars($old['title'] ?? '') ?>">
      </label>
 
      <!-- Autor -->
      <label class="field">
        <span><?= htmlspecialchars($language['recommendations']['author_label'] ?? 'Autor') ?></span>
        <input type="text" name="author"
               placeholder="<?= htmlspecialchars($language['recommendations']['author_ph'] ?? 'Autor / Creador') ?>"
               maxlength="255" required
               value="<?= htmlspecialchars($old['author'] ?? '') ?>">
      </label>
 
      <!-- Comentario -->
      <label class="field span-2">
        <span><?= htmlspecialchars($language['recommendations']['comment_label'] ?? 'Comentario') ?></span>
        <textarea name="comment" rows="3"
                  placeholder="<?= htmlspecialchars($language['recommendations']['comment_ph'] ?? '¿Por qué lo recomiendas?') ?>"
                  maxlength="2000" required><?= htmlspecialchars($old['comment'] ?? '') ?></textarea>
      </label>
    </div>
 
    <button class="btn" type="submit"><?= htmlspecialchars($language['recommendations']['submit'] ?? 'Enviar recomendación') ?></button>
  </form>
</section>
