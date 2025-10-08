(() => {
  'use strict';

  const FORUM_INDEX = (window.FORUM_INDEX || []).map(h => ({
    id: Number(h.id),
    date_start: h.date_start,
    date_finish: h.date_finish,
  }));
  const IS_EDITING = !!window.IS_FORUM_EDITING;

  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const has = v => (v ?? '').toString().trim().length > 0;

  // Texto robusto desde contenteditable
  function textFromCE(root, sel) {
    const el = $(sel, root);
    if (!el) return '';
    let html = (el.innerHTML || '').replace(/\u00A0/g, ' ').trim();
    let txt  = html.replace(/<br\s*\/?>/gi, '\n')
                   .replace(/<\/(p|div|h\d)>/gi, '\n')
                   .replace(/<[^>]+>/g, '')
                   .replace(/\s+/g, ' ')
                   .trim();
    if (!txt && el.innerText) txt = el.innerText.replace(/\u00A0/g, ' ').replace(/\s+/g, ' ').trim();
    return txt;
  }

  function toDateLocal(v) {
    if (!v) return null;
    let s = String(v).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) s += 'T00:00';
    s = s.replace(' ', 'T');
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[T ]?(\d{2})?:?(\d{2})?/);
    if (m) {
      const [, yy, mm, dd, HH = '00', MM = '00'] = m;
      return new Date(Number(yy), Number(mm) - 1, Number(dd), Number(HH), Number(MM), 0, 0);
    }
    const d = new Date(s);
    return isNaN(d) ? null : d;
  }
  const overlaps = (aStart, aEnd, bStart, bEnd) => (aStart < bEnd) && (aEnd > bStart);

  let form, errorBox, btnNew;

  function cacheElements(){
    form      = $('#forum-form');
    errorBox  = $('#forum-errors');
    btnNew    = $('#btn-new-activity');
  }

  function initEditors(){
    if (!form) return;
    if (typeof initRichEditor === 'function') {
      initRichEditor({
        root: form,
        langs: ['es','eu'],
        toolbarSelector: '.toolbar',
        tabBtnSelector: '.tab-btn',
        tabs: { es: '.tab-es', eu: '.tab-eu' },
        title: {
          esCtt: '#title-es',
          euCtt: '#title-eu',
          esField: '#tx-title-es',
          euField: '#tx-title-eu',
          singleLine: true
        },
        content: {
          esCtt: '#ed-es',  esField: '#tx-es',
          euCtt: '#ed-eu',  euField: '#tx-eu'
        }
      });
    }
  }

  function clearIfNew(){
    if (IS_EDITING) return;
    ['date_start','date_finish','url'].forEach(n => {
      const el = form.querySelector(`[name="${n}"]`);
      if (el) el.value = '';
    });
    ['#title-es','#title-eu','#ed-es','#ed-eu'].forEach(sel => {
      const el = $(sel);
      if (el) el.innerHTML = '';
    });
  }

  function focusCurrent(){
    const el = $('#title-es') || $('#ed-es') || $('#title-eu') || $('#ed-eu');
    setTimeout(() => el?.focus(), 30);
  }

  function showForm(){
    if (!form) return;
    form.classList.remove('is-hidden');
    clearIfNew();
    focusCurrent();
    const top = form.getBoundingClientRect().top + window.pageYOffset - 100;
    window.scrollTo({ top, behavior: 'smooth' });
  }

  function hideForm(){
    if (!form) return;
    form.classList.add('is-hidden');
    try {
      // limpia cualquier ?edit= y deja la ancla correcta
      const baseUrlNoQuery = window.location.pathname + '#admin/forum';
      window.history.replaceState({}, '', baseUrlNoQuery);
    } catch(_) {}
  }

  function renderErrors(errs){
    if (!errorBox) return;
    if (!errs.length){
      errorBox.style.display = 'none';
      errorBox.innerHTML = '';
      return;
    }
    errorBox.innerHTML = errs.map(e => `<div>• ${e}</div>`).join('');
    errorBox.style.display = 'block';
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function validateForm(){
    const errs = [];

    const idRaw = (form.querySelector('input[name="id"]')?.value || '').trim();
    const currentId = idRaw === '' ? null : parseInt(idRaw, 10);

    const d1Str = form.querySelector('input[name="date_start"]')?.value || '';
    const d2Str = form.querySelector('input[name="date_finish"]')?.value || '';
    const url   = form.querySelector('input[name="url"]')?.value || '';

    const tEs = textFromCE(form, '#title-es');
    const cEs = textFromCE(form, '#ed-es');
    const tEu = textFromCE(form, '#title-eu');
    const cEu = textFromCE(form, '#ed-eu');

    if (!has(d1Str) || !has(d2Str)) errs.push('Indica fecha y hora de inicio y fin.');
    if (!has(tEs) || !has(cEs)) errs.push('Completa Título y Contenido en Español.');
    if (!has(tEu) || !has(cEu)) errs.push('Completa Título y Contenido en Euskera.');

    if (has(url) && !/^https?:\/\//i.test(url)) {
      errs.push('La URL debe comenzar por http:// o https://');
    }

    let dStart = null, dFinish = null;
    if (has(d1Str) && has(d2Str)){
      dStart  = toDateLocal(d1Str);
      dFinish = toDateLocal(d2Str);
      const now = new Date(); now.setSeconds(0,0);

      if (!dStart || !dFinish) {
        errs.push('Formato de fecha/hora no válido.');
      } else {
        if (dStart < now)      errs.push('La fecha de inicio no puede ser anterior a la fecha actual');
        if (dFinish < now)     errs.push('La fecha de fin no puede ser anterior a la fecha actual');
        if (dStart >= dFinish) errs.push('La fecha de inicio debe ser anterior a la fecha de fin');
      }
    }

    if (!errs.length && dStart && dFinish && Array.isArray(FORUM_INDEX)){
      for (const it of FORUM_INDEX){
        if (currentId !== null && it.id === currentId) continue;
        const bs = toDateLocal(it.date_start);
        const bf = toDateLocal(it.date_finish);
        if (!bs || !bf) continue;
        if (overlaps(dStart, dFinish, bs, bf)){
          errs.push(`Ya hay una actividad programada en esas fechas (#${it.id}: ${it.date_start} → ${it.date_finish}).`);
          break;
        }
      }
    }

    return { valid: errs.length === 0, errs };
  }

  function fillHiddenFromEditors(){
    const map = [
      ['#title-es', '#tx-title-es'],
      ['#title-eu', '#tx-title-eu'],
      ['#ed-es',    '#tx-es'],
      ['#ed-eu',    '#tx-eu'],
    ];
    for (const [ceSel, txSel] of map) {
      const ce = $(ceSel); const tx = $(txSel);
      if (ce && tx) tx.value = (ce.innerHTML || '').trim();
    }
  }

  function bindEvents(){
    if (!form) return;

    // Mostrar formulario
    btnNew?.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      showForm();
    });

    // Ocultar formulario con cualquier click sobre #btn-cancel (delegación y captura)
    const hideCancel = (e) => {
      if (e.target && (e.target.id === 'btn-cancel' || e.target.closest?.('#btn-cancel'))) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation?.();
        hideForm();
        return false;
      }
    };
    document.addEventListener('click', hideCancel, true);  // captura
    document.addEventListener('click', hideCancel, false); // burbujeo

    // También ocultar al enviar (Programar/Borrador)
    form.addEventListener('submit', (ev) => {
      const { valid, errs } = validateForm();
      if (!valid){
        ev.preventDefault();
        ev.stopPropagation();
        ev.stopImmediatePropagation?.();
        renderErrors(errs);
        return false;
      }
      fillHiddenFromEditors();
      renderErrors([]);
      form.style.pointerEvents = 'none';
      form.style.opacity = '0.5';
      setTimeout(() => { hideForm(); }, 120);
    }, { capture: true });
  }

  document.addEventListener('DOMContentLoaded', () => {
    cacheElements();
    initEditors();
    bindEvents();
  });
})();
