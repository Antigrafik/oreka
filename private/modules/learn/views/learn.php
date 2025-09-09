<?php
global $language;

if (!function_exists('learnCategoryImage')) {
  function learnCategoryImage(array $c): string {
    $slug = $c['category_slug'] ?? '';
    if (!$slug) {
      $name = $c['category_name'] ?? '';
      $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
      $slug = strtolower($slug);
      $slug = preg_replace('/[^a-z0-9]+/', '', $slug);
    }

    // Mapear slugs ES/EU a una clave base de imagen
    $map = [
      // bienestar
      'bienestar'       => 'bienestar',
      'ongizatea'       => 'bienestar',
      // productividad
      'productividad'   => 'productividad',
      'produktibitatea' => 'productividad',
      // nutrición
      'nutricion'       => 'nutricion',
      'nutrizioa'       => 'nutricion',
    ];

    $key = $map[$slug] ?? 'bienestar';
    return "/assets/images/{$key}.png";
  }
}
?>


<section id="learn" class="hero">
  <h1><?php echo $language['learn']['title']; ?></h1>
  <p><?php echo $language['learn']['subtitle']; ?></p>
</section>

<?php if (empty($learns)): ?>
  <div class="empty"><?php echo $language['learn']['empty']; ?></div>
<?php else: ?>
<section class="learn-slider">
  <button class="nav prev" aria-label="Anterior">‹</button>

  <div class="viewport">
    <ul class="track">
      <?php foreach ($learns as $c): ?>
        <?php $imgUrl = learnCategoryImage($c); ?>
        <li class="slide">
          <article class="card">
            <div class="card-media">
              <img class="card-media-img"
                  src="<?= htmlspecialchars($imgUrl) ?>"
                  alt="Categoría: <?= htmlspecialchars($c['category_name'] ?? '') ?>"
                  loading="lazy" decoding="async">
            </div>
            <div class="card-body">
              <span class="badge"><?= htmlspecialchars($c['category_name'] ?? 'Bienestar') ?></span>
              <h3 class="card-title"><?= htmlspecialchars($c['title']) ?></h3>
              <p class="card-text">
                <?= nl2br(htmlspecialchars($c['description'] ?: $language['learn']['description'])) ?>
              </p>

              <?php if ($c['duration'] !== null && $c['duration'] !== ''): ?>
                <div class="meta duration">
                  <?php echo $language['learn']['duration']; ?> <?= is_numeric($c['duration']) ? (int)$c['duration'].' min' : htmlspecialchars($c['duration']) ?>
                </div>
              <?php endif; ?>

              <div class="progress"><div class="bar" style="width:0%"></div></div>

              <?php if (!empty($c['url'])): ?>
                <a class="btn" href="<?= htmlspecialchars($c['url']) ?>" target="_blank" rel="noopener"><?php echo $language['learn']['button_go']; ?></a>
              <?php else: ?>
                <button class="btn" disabled><?php echo $language['learn']['soon']; ?></button>
              <?php endif; ?>
            </div>
          </article>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <button class="nav next" aria-label="Siguiente">›</button>
</section>
<?php endif; ?>
