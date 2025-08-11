<section class="hero">
  <h1>LEARN OREKA</h1>
  <p>Fórmate a tu ritmo y gana puntos. El conocimiento es bienestar.</p>
</section>

<section id="learn" class="grid-cursos">
  <?php if (empty($learns)): ?>
    <div class="empty">Todavía no hay cursos disponibles.</div>
  <?php else: ?>
    <?php foreach ($learns as $c): ?>
      <article class="card">
        <div class="card-media">
          <?php if (!empty($c['image_path'])): ?>
            <img src="<?= htmlspecialchars($c['image_path']) ?>" alt="Imagen del curso">
          <?php else: ?>
            <div class="media-placeholder">Learn</div>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <span class="badge">Bienestar</span>
          <h3 class="card-title"><?= htmlspecialchars($c['title']) ?></h3>
          <p class="card-text">
            <?= nl2br(htmlspecialchars($c['description'] ?: 'Curso disponible en la sección Learn Oreka.')) ?>
          </p>
          <div class="progress"><div class="bar" style="width:0%"></div></div>
          <?php if (!empty($c['url'])): ?>
            <a class="btn" href="<?= htmlspecialchars($c['url']) ?>" target="_blank" rel="noopener">Ir al curso</a>
          <?php else: ?>
            <button class="btn" disabled>Próximamente</button>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>