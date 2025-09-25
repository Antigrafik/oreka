document.addEventListener('DOMContentLoaded', () => {
  const sliders = document.querySelectorAll('.learn-slider, .recomendation-slider');
  if (!sliders.length) return;

  sliders.forEach((slider) => {
    const viewport = slider.querySelector('.viewport');
    const track    = slider.querySelector('.track');
    const prevBtn  = slider.querySelector('.prev');
    const nextBtn  = slider.querySelector('.next');
    if (!viewport || !track || !prevBtn || !nextBtn) return;

    const state = {
      index: 0,
      total: 0,
      anim: true,
      step: 0,
      slideW: 0,
      perView: 1,          // perView efectivo del carrusel
    };

    // breakpoints base
    let basePerView = 3;
    let GUTTER  = 24;
    let SIDE    = 12;

    const setBasePerView = () => {
      const w = window.innerWidth;
      basePerView = (w <= 640) ? 1 : (w <= 1024 ? 2 : 3);
      GUTTER      = (w <= 640) ? 16 : 24;
      SIDE        = (w <= 640) ? 8  : 12;
    };

    const computeSizes = () => {
      // usa el perView EFECTIVO (state.perView)
      track.style.padding = `0 ${SIDE}px`;
      const inner = viewport.clientWidth - (SIDE * 2) - ((state.perView - 1) * GUTTER);
      const slideWidth = Math.max(0, Math.floor(inner / state.perView));

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
      // recoge solo originales (sin clones)
      const originals = Array.from(track.querySelectorAll('.slide:not(.clone)'));
      if (!originals.length) return;

      // perView efectivo según nº de originales
      state.perView = Math.max(1, Math.min(basePerView, originals.length));

      // limpia y reinyecta originales
      track.innerHTML = '';
      originals.forEach(el => track.appendChild(el));

      // clona solo lo que necesitamos (pv efectivos)
      const slides = Array.from(track.children);
      const headClones = slides.slice(0, state.perView).map(s => { const c = s.cloneNode(true); c.classList.add('clone'); return c; });
      const tailClones = slides.slice(-state.perView).map(s => { const c = s.cloneNode(true); c.classList.add('clone'); return c; });

      tailClones.forEach(c => track.insertBefore(c, track.firstChild));
      headClones.forEach(c => track.appendChild(c));

      state.total = slides.length;   // nº de originales
      state.index = state.perView;   // arrancamos tras los tail clones

      computeSizes();
      jump();
    };

    const next = () => { state.anim = true; state.index++; jump(); };
    const prev = () => { state.anim = true; state.index--; jump(); };

    track.addEventListener('transitionend', () => {
      const maxIndex = state.total + state.perView - 1;
      if (state.index > maxIndex) {
        state.anim = false;
        state.index = state.perView;
        jump();
      } else if (state.index < state.perView) {
        state.anim = false;
        state.index = state.total + state.perView - 1;
        jump();
      }
    });

    const onResize = () => {
      const prevBase = basePerView;
      setBasePerView();
      // si cambia el basePerView y afecta al perView efectivo, reconstruimos
      const willBe = Math.max(1, Math.min(basePerView, state.total || 1));
      if (basePerView !== prevBase || willBe !== state.perView) {
        rebuild();
      } else {
        computeSizes();
        jump();
      }
    };

    nextBtn.addEventListener('click', next);
    prevBtn.addEventListener('click', prev);
    window.addEventListener('resize', onResize);

    setBasePerView();
    rebuild();
  });
});
