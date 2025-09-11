document.addEventListener('DOMContentLoaded', () => {
  // Soporta ambos: Aula (.learn-slider) y Comunidad (.recomendation-slider)
  const sliders = document.querySelectorAll('.learn-slider, .recomendation-slider');
  if (!sliders.length) return;

  sliders.forEach((slider) => {
    const viewport = slider.querySelector('.viewport');
    const track    = slider.querySelector('.track');
    const prevBtn  = slider.querySelector('.prev');
    const nextBtn  = slider.querySelector('.next');
    if (!viewport || !track || !prevBtn || !nextBtn) return;

    const state = { index: 0, total: 0, anim: true, step: 0, slideW: 0 };

    let perView = 3;
    let GUTTER  = 24;
    let SIDE    = 12;

    const setPerView = () => {
      const w = window.innerWidth;
      perView = (w <= 640) ? 1 : (w <= 1024 ? 2 : 3);
      GUTTER  = (w <= 640) ? 16 : 24;
      SIDE    = (w <= 640) ? 8  : 12;
    };

    const computeSizes = () => {
      track.style.padding = `0 ${SIDE}px`;
      const inner = viewport.clientWidth - (SIDE * 2) - ((perView - 1) * GUTTER);
      const slideWidth = Math.floor(inner / perView);
      Array.from(track.children).forEach(li => {
        li.style.flex     = `0 0 ${slideWidth}px`;
        li.style.minWidth = `${slideWidth}px`;
        li.style.maxWidth = `${slideWidth}px`;
        li.style.margin   = `0 ${Math.floor(GUTTER/2)}px`;
      });
      state.slideW = slideWidth;
      state.step   = slideWidth + GUTTER;
    };

    const jump = () => {
      track.style.transition = state.anim ? 'transform .35s ease' : 'none';
      const x = -(state.index * state.step);
      track.style.transform = `translate3d(${x}px,0,0)`;
    };

    const rebuild = () => {
      const originals = Array.from(track.querySelectorAll('.slide:not(.clone)'));
      if (!originals.length) return;

      track.innerHTML = '';
      originals.forEach(el => track.appendChild(el));

      const slides = Array.from(track.children);
      const headClones = slides.slice(0, perView).map(s => { const c = s.cloneNode(true); c.classList.add('clone'); return c; });
      const tailClones = slides.slice(-perView).map(s => { const c = s.cloneNode(true); c.classList.add('clone'); return c; });

      tailClones.forEach(c => track.insertBefore(c, track.firstChild));
      headClones.forEach(c => track.appendChild(c));

      state.total = slides.length;
      state.index = perView;

      computeSizes();
      jump();
    };

    const next = () => { state.anim = true; state.index++; jump(); };
    const prev = () => { state.anim = true; state.index--; jump(); };

    track.addEventListener('transitionend', () => {
      const maxIndex = state.total + perView - 1;
      if (state.index > maxIndex) {
        state.anim = false;
        state.index = perView;
        jump();
      } else if (state.index < perView) {
        state.anim = false;
        state.index = state.total + perView - 1;
        jump();
      }
    });

    const onResize = () => { setPerView(); computeSizes(); jump(); };

    nextBtn.addEventListener('click', next);
    prevBtn.addEventListener('click', prev);
    window.addEventListener('resize', onResize);

    setPerView();
    rebuild();
  });
});
