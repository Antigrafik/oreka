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

<script>
document.addEventListener('DOMContentLoaded', () => {
  const slider   = document.querySelector('.learn-slider');
  if (!slider) return;

  const viewport = slider.querySelector('.viewport');
  const track    = slider.querySelector('.track');
  const prevBtn  = slider.querySelector('.prev');
  const nextBtn  = slider.querySelector('.next');

  // Estado
  const state = { index: 0, total: 0, anim: true, step: 0, slideW: 0 };

  let perView = 3;
  let GUTTER  = 24;   // 12px + 12px
  let SIDE    = 12;   // padding lateral del track

  const setPerView = () => {
    const w = window.innerWidth;
    perView = (w <= 640) ? 1 : (w <= 1024 ? 2 : 3);
    GUTTER  = (w <= 640) ? 16 : 24;
    SIDE    = (w <= 640) ? 8  : 12;
  };

  const computeSizes = () => {
    // acolchado lateral para que no se corte el borde del 1º y 3º
    track.style.padding = `0 ${SIDE}px`;

    // ancho útil = viewport - padding izq/der - gutters visibles
    const inner = viewport.clientWidth - (SIDE * 2) - ((perView - 1) * GUTTER);
    const slideWidth = Math.floor(inner / perView);

    // fijar ancho y evitar shrink
    Array.from(track.children).forEach(li => {
      li.style.flex     = `0 0 ${slideWidth}px`;
      li.style.minWidth = `${slideWidth}px`;
      li.style.maxWidth = `${slideWidth}px`;
    });

    state.slideW = slideWidth;
    state.step   = slideWidth + GUTTER; // paso = tarjeta + gutter
  };

  const rebuild = () => {
    // quitar clones antiguos
    const originals = Array.from(track.querySelectorAll('.slide:not(.clone)'));
    track.innerHTML = '';
    originals.forEach(el => track.appendChild(el));

    // clones para bucle
    const slides = Array.from(track.children);
    const headClones = slides.slice(0, perView).map(s => { const c = s.cloneNode(true); c.classList.add('clone'); return c; });
    const tailClones = slides.slice(-perView).map(s => { const c = s.cloneNode(true); c.classList.add('clone'); return c; });

    tailClones.forEach(c => track.insertBefore(c, track.firstChild));
    headClones.forEach(c => track.appendChild(c));

    state.total = slides.length;     // solo originales
    state.index = perView;           // empezar en el 1º real

    computeSizes();
    jump();
  };

  const jump = () => {
    track.style.transition = state.anim ? 'transform .35s ease' : 'none';
    const x = -(state.index * state.step);
    track.style.transform = `translate3d(${x}px,0,0)`;
  };

  const next = () => { state.anim = true; state.index++; jump(); };
  const prev = () => { state.anim = true; state.index--; jump(); };

  track.addEventListener('transitionend', () => {
    const maxIndex = state.total + perView - 1;
    if (state.index > maxIndex) {
      state.anim = false;
      state.index = perView;                     // volver al primero real
      jump();
    } else if (state.index < perView) {
      state.anim = false;
      state.index = state.total + perView - 1;   // saltar al último real
      jump();
    }
  });

  nextBtn.addEventListener('click', next);
  prevBtn.addEventListener('click', prev);

  const onResize = () => { setPerView(); computeSizes(); jump(); };
  window.addEventListener('resize', onResize);

  setPerView();   // <-- ahora sí existe
  rebuild();
});
</script>


