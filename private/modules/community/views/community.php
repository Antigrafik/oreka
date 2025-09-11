<?php
global $language;

if (!function_exists('lower_ascii')) {
  function lower_ascii(string $s): string { return strtolower(trim($s)); }
}
?>

<section id="community" class="hero">
  <h1><?= htmlspecialchars($language['community']['title']) ?></h1>
  <p><?= htmlspecialchars($language['community']['subtitle']) ?></p>
</section>

<section class="recommendations">
  <h2><?= htmlspecialchars($language['recommendations']['title']) ?></h2>
  <p class="lead"><?= htmlspecialchars($language['recommendations']['subtitle']) ?></p>

  <?php if (empty($recommendations)): ?>
    <div class="empty"><?= htmlspecialchars($language['recommendations']['empty']) ?></div>
  <?php else: ?>

    <div class="filters">
      <label class="filter">
        <span><?= htmlspecialchars($language['recommendations']['theme']) ?></span>
        <select id="filter-tema">
          <option value=""><?= htmlspecialchars($language['recommendations']['all']) ?></option>
          <?php foreach (($themes ?? []) as $t): ?>
            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="filter">
        <span><?= htmlspecialchars($language['recommendations']['support']) ?></span>
        <select id="filter-soporte">
          <option value=""><?= htmlspecialchars($language['recommendations']['all']) ?></option>
          <?php foreach (($supports ?? []) as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="filter">
        <span><?= htmlspecialchars($language['recommendations']['sort_by']) ?></span>
        <select id="order-by">
          <option value="likes">Más likes</option>
          <option value="recent"><?= htmlspecialchars($language['recommendations']['recent']) ?></option>
        </select>
      </label>

      <label class="filter search">
        <input id="search-recs" type="search"
               placeholder="<?= htmlspecialchars($language['recommendations']['search']) ?>"
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
                          title="<?= htmlspecialchars($language['recommendations']['like']) ?>"
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

