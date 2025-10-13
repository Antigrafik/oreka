<?php
global $language;
?>

<section id="learn" class="title-section">
  <h1><?php echo $language['learn']['title']; ?></h1>
  <p><?php echo $language['learn']['subtitle']; ?></p>
</section>
 
<?php if (empty($learns)): ?>
  <div class="empty"><?php echo $language['learn']['empty']; ?></div>
<?php else: ?>
<section class="learn-slider">
  <span class="nav prev" aria-label="Anterior"></span>
 
  <div class="viewport">
    <ul class="track">
      <?php foreach ($learns as $c): ?>
      
        <li class="slide">
          <article class="card">
            <div class="card-media">
              <img class="card-media-img"
                  src="<?= htmlspecialchars($c['image_url']) ?>"
                  alt=""
                  loading="lazy" decoding="async">
            </div>
            <div class="card-body">
              <h3 class="card-title"><?= htmlspecialchars($c['learn_title']) ?></h3>

              <?php if ($c['duration'] !== null && $c['duration'] !== ''): ?>
                <div class="meta duration">
                  <?php echo $language['learn']['duration']; ?> <?= is_numeric($c['duration']) ? (int)$c['duration'].' min' : htmlspecialchars($c['duration']) ?>
                </div>
              <?php endif; ?>
 
              <div class="progress"><div class="bar"></div></div>

              <a class="btn" href="<?= htmlspecialchars($c['module_url']) ?>" target="_blank" rel="noopener"><?php echo $language['learn']['button_go']; ?></a>

            </div>
          </article>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
 
  <span class="nav next" aria-label="Siguiente"></span>
</section>
<?php endif; ?>
