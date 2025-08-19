<section id="community" class="community-hero">
  <h1><?php echo $language['community']['title']; ?></h1>
  <p><?php echo $language['community']['subtitle']; ?></p>
</section>

<section class="recs-block">
  <h2 class="block-title"><?php echo $language['recommendations']['title']; ?></h2>
  <p class="block-sub"><?php echo $language['recommendations']['subtitle']; ?></p>

  <!-- Filtros -->
  <div class="recs-filters">
    <label><?php echo $language['recommendations']['theme']; ?>
      <select id="rec-filter-theme">
        <option value=""><?php echo $language['recommendations']['all']; ?></option>
        <?php foreach ($themes as $t): ?>
          <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label><?php echo $language['recommendations']['support']; ?>
      <select id="rec-filter-support">
        <option value=""><?php echo $language['recommendations']['all']; ?></option>
        <?php foreach ($supports as $s): ?>
          <option value="<?= htmlspecialchars($s['name']) ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label><?php echo $language['recommendations']['sort_by']; ?>
      <select id="rec-sort">
        <option value="recent"><?php echo $language['recommendations']['recent']; ?></option>
        <option value="az">A-Z</option>
        <option value="za">Z-A</option>
      </select>
    </label>

    <input id="rec-search" type="search" placeholder="<?php echo $language['recommendations']['search']; ?>">
  </div>

  <!-- Carrusel -->
  <?php if (empty($recs)): ?>
    <div class="empty"><?php echo $language['recommendations']['empty']; ?></div>
  <?php else: ?>
  <section class="recs-slider">
    <button class="nav prev" aria-label="Anterior">‹</button>
    <div class="viewport">
      <ul class="track" id="rec-track">
        <?php foreach ($recs as $r): 
          $title   = $r['title'] ?: ('Recomendación #' . $r['id']);
          $author  = $r['author'] ?: '';
          $excerpt = $r['content'] ? mb_substr($r['content'], 0, 140) . (mb_strlen($r['content'])>140?'…':'') : '';
          $theme   = $r['theme'] ?: 'General';
          $support = $r['support'] ?: 'Otro';
          $dateISO = !empty($r['created_at']) ? (new DateTime($r['created_at']))->format('Y-m-d') : '';
        ?>
        <li class="slide"
            data-theme="<?= htmlspecialchars($theme) ?>"
            data-support="<?= htmlspecialchars($support) ?>"
            data-title="<?= htmlspecialchars(mb_strtolower($title)) ?>"
            data-author="<?= htmlspecialchars(mb_strtolower($author)) ?>"
            data-date="<?= htmlspecialchars($dateISO) ?>">
          <article class="card">
            <div class="card-media">
              <div class="media-placeholder">❤️</div>
            </div>
            <div class="card-body">
              <div class="pill-row">
                <span class="badge"><?= htmlspecialchars($theme) ?></span>
                <span class="badge outline"><?= htmlspecialchars($support) ?></span>
              </div>
              <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
              <?php if ($author): ?><p class="meta">Por <?= htmlspecialchars($author) ?></p><?php endif; ?>
              <?php if ($excerpt): ?><p class="card-text"><?= htmlspecialchars($excerpt) ?></p><?php endif; ?>
              <div class="progress"><div class="bar" style="width:0%"></div></div>
              <button class="btn"><?php echo $language['recommendations']['like']; ?></button>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
  // ------ Filtros / Orden / Búsqueda (en cliente) ------
  const track   = document.getElementById('rec-track');
  if (!track) return;
  const slides  = Array.from(track.children);
  const fTheme  = document.getElementById('rec-filter-theme');
  const fSup    = document.getElementById('rec-filter-support');
  const fSort   = document.getElementById('rec-sort');
  const fSearch = document.getElementById('rec-search');

  function applyFilters(){
    const theme  = (fTheme?.value || '').toLowerCase();
    const sup    = (fSup?.value   || '').toLowerCase();
    const q      = (fSearch?.value|| '').trim().toLowerCase();

    slides.forEach(li => {
      const okTheme = !theme || (li.dataset.theme || '').toLowerCase() === theme;
      const okSup   = !sup   || (li.dataset.support|| '').toLowerCase() === sup;
      const hay     = (li.dataset.title + ' ' + li.dataset.author).includes(q);
      li.style.display = (okTheme && okSup && hay) ? '' : 'none';
    });
  }

  function applySort(){
    const mode = fSort?.value || 'recent';
    const visibles = slides.filter(li => li.style.display !== 'none');
    visibles.sort((a,b)=>{
      if (mode === 'az' || mode === 'za') {
        const ta = (a.dataset.title||'').localeCompare(b.dataset.title||'','es',{sensitivity:'base'});
        return mode==='az' ? ta : -ta;
      } else { // recent
        return (b.dataset.date||'').localeCompare(a.dataset.date||'');
      }
    });
    visibles.forEach(li => track.appendChild(li));
  }

  [fTheme,fSup,fSearch].forEach(el => el && el.addEventListener('input', ()=>{applyFilters(); applySort();}));
  fSort && fSort.addEventListener('change', ()=>{applySort();});

  // Primera aplicación
  applyFilters(); applySort();

  // ------ Carrusel 3/2/1 como Learn ------
  const slider   = document.querySelector('.recs-slider');
  const viewport = slider?.querySelector('.viewport');
  const prevBtn  = slider?.querySelector('.prev');
  const nextBtn  = slider?.querySelector('.next');

  if (!slider || !viewport) return;

  const state = { index: 0, total: 0, anim: true, step: 0 };
  let perView = 3, GUTTER = 24, SIDE = 12;

  const setPerView = () => {
    const w = window.innerWidth;
    perView = (w <= 640) ? 1 : (w <= 1024 ? 2 : 3);
    GUTTER  = (w <= 640) ? 16 : 24;
    SIDE    = (w <= 640) ? 8  : 12;
  };

  const computeSizes = () => {
    const items = Array.from(track.children);
    track.style.padding = `0 ${SIDE}px`;

    const inner = viewport.clientWidth - (SIDE*2) - ((perView-1)*GUTTER);
    const slideWidth = Math.floor(inner / perView);

    items.forEach(li=>{
      li.style.flex     = `0 0 ${slideWidth}px`;
      li.style.minWidth = `${slideWidth}px`;
      li.style.maxWidth = `${slideWidth}px`;
      li.style.margin   = `0 ${GUTTER/2}px`;
    });

    state.step = slideWidth + GUTTER;
  };

  const rebuild = () => {
    // quitar clones
    const originals = Array.from(track.querySelectorAll('.slide:not(.clone)'));
    track.innerHTML = '';
    originals.forEach(el => track.appendChild(el));

    const slidesAll = Array.from(track.children);
    const head = slidesAll.slice(0, perView).map(s=>{const c=s.cloneNode(true); c.classList.add('clone'); return c;});
    const tail = slidesAll.slice(-perView).map(s=>{const c=s.cloneNode(true); c.classList.add('clone'); return c;});
    tail.forEach(c => track.insertBefore(c, track.firstChild));
    head.forEach(c => track.appendChild(c));

    state.total = slidesAll.length;
    state.index = perView;

    computeSizes(); jump();
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
      state.anim = false; state.index = perView; jump();
    } else if (state.index < perView) {
      state.anim = false; state.index = state.total + perView - 1; jump();
    }
  });
  nextBtn?.addEventListener('click', next);
  prevBtn?.addEventListener('click', prev);
  window.addEventListener('resize', ()=>{ setPerView(); computeSizes(); jump(); });

  setPerView(); rebuild();
});
</script>
