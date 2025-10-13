document.addEventListener('DOMContentLoaded', () => {
  const slider = document.querySelector('.learn-slider');
  if (!slider) return;

  const viewport = slider.querySelector('.viewport');
  const track = slider.querySelector('.track');
  const slides = slider.querySelectorAll('.slide');
  const prevBtn = slider.querySelector('.nav.prev');
  const nextBtn = slider.querySelector('.nav.next');

  if (!viewport || slides.length === 0) return;

  let currentIndex = 0;

  // === Función: calcula el desplazamiento por slide + gap ===
  function getScrollAmount() {
    const slide = slides[0];
    const trackStyle = window.getComputedStyle(track);
    const gap = parseFloat(trackStyle.gap) || 0;
    const slideWidth = slide.getBoundingClientRect().width;
    return slideWidth + gap;
  }

  // === Función: mover a una card específica ===
  function scrollToCard(index) {
    if (index < 0) index = 0;
    if (index >= slides.length) index = slides.length - 1;

    const targetSlide = slides[index];
    if (!targetSlide) return;

    viewport.scrollTo({
      left: targetSlide.offsetLeft,
      behavior: 'smooth'
    });

    currentIndex = index;
    updateNavButtons();
  }

  // === Función: mostrar / ocultar flechas según posición ===
  function updateNavButtons() {
    const scrollLeft = viewport.scrollLeft;
    const maxScrollLeft = viewport.scrollWidth - viewport.clientWidth;

    if (scrollLeft <= 0) {
      prevBtn.classList.add('hidden');
    } else {
      prevBtn.classList.remove('hidden');
    }

    if (scrollLeft >= maxScrollLeft - 1) {
      nextBtn.classList.add('hidden');
    } else {
      nextBtn.classList.remove('hidden');
    }
  }

  // === Eventos de navegación ===
  nextBtn.addEventListener('click', () => {
    const amount = getScrollAmount();
    viewport.scrollBy({ left: amount, behavior: 'smooth' });
    setTimeout(updateNavButtons, 400);
  });

  prevBtn.addEventListener('click', () => {
    const amount = getScrollAmount();
    viewport.scrollBy({ left: -amount, behavior: 'smooth' });
    setTimeout(updateNavButtons, 400);
  });

  // === Recalcular índice actual al hacer scroll manual ===
  viewport.addEventListener('scroll', () => {
    let closestIndex = 0;
    let closestDistance = Infinity;

    slides.forEach((slide, index) => {
      const distance = Math.abs(slide.offsetLeft - viewport.scrollLeft);
      if (distance < closestDistance) {
        closestDistance = distance;
        closestIndex = index;
      }
    });

    currentIndex = closestIndex;
    updateNavButtons();
  });

  // === Reajustar al redimensionar ===
  const resizeObserver = new ResizeObserver(() => {
    scrollToCard(currentIndex);
  });
  resizeObserver.observe(viewport);

  // === Inicialización ===
  updateNavButtons();
});
