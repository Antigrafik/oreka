// ==== TOGGLE FORMULARIOS DE RECOMENDACIÓN Y QUEDADA ====
document.addEventListener('DOMContentLoaded', () => {

  function toggleForm(btnSelector, formId, sectionSelector, hideText, otherBtnSelector, otherFormId) {
    const btn = document.querySelector(btnSelector);
    const form = document.getElementById(formId);

    if (!btn || !form) return;

    btn.addEventListener('click', () => {
      const isHidden = form.classList.toggle('hidden');

      // Fallback visual: forzar display
      form.style.display = isHidden ? 'none' : 'block';

      // Cambiar símbolo y texto
      const plusEl = btn.querySelector('.plus');
      const textEl = btn.querySelector('.text');

      if (plusEl) plusEl.textContent = isHidden ? '+' : '–';
      if (textEl) {
        if (!btn.dataset.originalText) btn.dataset.originalText = textEl.textContent.trim();
        textEl.textContent = isHidden ? btn.dataset.originalText : hideText;
      }

      // Cerrar el otro formulario si está abierto
      const otherForm = document.getElementById(otherFormId);
      const otherBtn = document.querySelector(otherBtnSelector);
      if (!isHidden && otherForm && !otherForm.classList.contains('hidden')) {
        otherForm.classList.add('hidden');
        otherForm.style.display = 'none'; // <- forzar ocultar
        if (otherBtn) {
          const p = otherBtn.querySelector('.plus');
          const t = otherBtn.querySelector('.text');
          if (p) p.textContent = '+';
          if (t && otherBtn.dataset.originalText) t.textContent = otherBtn.dataset.originalText;
        }
      }

      // Scroll al bloque del botón
      if (!isHidden) {
        const section = document.querySelector(sectionSelector);
        const offset = 90;
        if (section) {
          const top = section.getBoundingClientRect().top + window.scrollY - offset;
          window.scrollTo({ top, behavior: 'smooth' });
        }
      }
    });
  }

  // === RECOMENDACIÓN ===
  toggleForm(
    '.btn-toggle-recommendation',
    'form-recommendation',
    '.recommendation-create',
    'Ocultar recomendación',
    '.btn-toggle-meeting',
    'form-meeting'
  );

  // === QUEDADA ===
  toggleForm(
    '.btn-toggle-meeting',
    'form-meeting',
    '.meeting-create',
    'Ocultar quedada',
    '.btn-toggle-recommendation',
    'form-recommendation'
  );

// ==== COMPORTAMIENTO LEER MÁS / LEER MENOS ====
  document.querySelectorAll('.rec-text-wrapper').forEach(wrapper => {
    const text = wrapper.querySelector('.rec-text');
    const button = wrapper.querySelector('.read-more-btn');
    if (!text || !button) return;

    // Detectar si hay texto oculto
    const isOverflowing = text.scrollHeight > text.clientHeight;

    if (!isOverflowing) {
      button.style.display = 'none';
    }

    button.addEventListener('click', () => {
      text.classList.toggle('expanded');
      button.textContent = text.classList.contains('expanded')
        ? 'Leer menos'
        : 'Leer más';
    });
  });
});
