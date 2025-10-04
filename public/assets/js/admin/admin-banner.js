(() => {
  'use strict';

  const BANNERS_INDEX = (window.BANNERS_INDEX || []).map(h => ({
    id: Number(h.id),
    date_start: h.date_start,
    date_finish: h.date_finish,
  }));
  const IS_EDITING = !!window.IS_EDITING;

  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const has = v => (v ?? '').toString().trim().length > 0;

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

  function textFromCE(root, sel) {
    const el = $(sel, root);
    if (!el) return '';
    return (el.innerText || '').replace(/\u00A0/g, ' ').trim();
  }
  function reallyShow(el){
    if (!el) return;
    el.removeAttribute('hidden');
    el.style.removeProperty('display');
    const cs = getComputedStyle(el);
    if (cs.display === 'none') el.style.display = 'block';
  }
  function focusCurrentLang(){
    const activeBtn = document.querySelector('.tab-btn.active') || document.querySelector('.tab-btn[data-tab="es"]');
    const lang = activeBtn?.dataset.tab || 'es';
    const el = (lang === 'es')
      ? (document.querySelector('#ed-es') || document.querySelector('#title-es'))
      : (document.querySelector('#ed-eu') || document.querySelector('#title-eu'));
    el?.focus();
  }

  // Refs
  let form, errorBox, typeInput, prizeWrap, btnRaffle, btnAd, btnCancel;

  function cacheElements(){
    form      = $('#banner-form');
    errorBox  = $('#banner-errors');
    typeInput = $('#banner-type');
    prizeWrap = $('#prize-wrap');
    btnRaffle = $('#btn-new-raffle');
    btnAd     = $('#btn-new-ad');
    btnCancel = $('#btn-cancel');
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
    const ds = $('input[name="date_start"]');
    const df = $('input[name="date_finish"]');
    const pr = $('input[name="prize"]');
    if (ds) ds.value = '';
    if (df) df.value = '';
    if (pr) pr.value = '';
    const te = $('#title-es');
    const tu = $('#title-eu');
    const ce = $('#ed-es');
    const cu = $('#ed-eu');
    if (te) te.innerHTML = '';
    if (tu) tu.innerHTML = '';
    if (ce) ce.innerHTML = '';
    if (cu) cu.innerHTML = '';
  }

  function showForm(type){
    if (!form) return;
    form.classList.remove('is-hidden');
    if (typeInput) typeInput.value = type;
    if (prizeWrap) prizeWrap.style.display = (type === 'raffle') ? '' : 'none';
    clearIfNew();
    const top = form.getBoundingClientRect().top + window.pageYOffset - 100;
    window.scrollTo({ top, behavior: 'smooth' });
  }

  function validateForm(){
    const errs = [];

    const idRaw = (form.querySelector('input[name="id"]')?.value || '').trim();
    const currentId = idRaw === '' ? null : parseInt(idRaw, 10);

    const type  = (typeInput?.value || 'ad').toLowerCase();
    const d1Str = form.querySelector('input[name="date_start"]')?.value || '';
    const d2Str = form.querySelector('input[name="date_finish"]')?.value || '';
    const prize = form.querySelector('input[name="prize"]')?.value || '';

    const tEs = textFromCE(form, '#title-es');
    const cEs = textFromCE(form, '#ed-es');
    const tEu = textFromCE(form, '#title-eu');
    const cEu = textFromCE(form, '#ed-eu');

    if (!has(d1Str) || !has(d2Str)) errs.push('Indica fecha y hora de inicio y fin.');
    if (!has(tEs) || !has(cEs)) errs.push('Completa Título y Contenido en Español.');
    if (!has(tEu) || !has(cEu)) errs.push('Completa Título y Contenido en Euskera.');
    if (type === 'raffle' && !has(prize)) errs.push('El premio es obligatorio para Sorteo.');

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

    if (!errs.length && dStart && dFinish && Array.isArray(BANNERS_INDEX)){
      for (const it of BANNERS_INDEX){
        if (currentId !== null && it.id === currentId) continue;
        const bs = toDateLocal(it.date_start);
        const bf = toDateLocal(it.date_finish);
        if (!bs || !bf) continue;
        if (overlaps(dStart, dFinish, bs, bf)){
          errs.push(`Ya hay un banner programado en esas fechas (#${it.id}: ${it.date_start} → ${it.date_finish}).`);
          break;
        }
      }
    }

    return { valid: errs.length === 0, errs };
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

    const d1 = form.querySelector('input[name="date_start"]');
    const d2 = form.querySelector('input[name="date_finish"]');
    const prize = form.querySelector('input[name="prize"]');

    if (!has(d1?.value)) d1?.focus();
    else if (!has(d2?.value)) d2?.focus();
    else if (!has(textFromCE(form, '#title-es'))) $('#title-es')?.focus();
    else if (!has(textFromCE(form, '#ed-es')))   $('#ed-es')?.focus();
    else if (!has(textFromCE(form, '#title-eu'))) $('#title-eu')?.focus();
    else if (!has(textFromCE(form, '#ed-eu')))   $('#ed-eu')?.focus();
    else if ((typeInput?.value || '').toLowerCase() === 'raffle' && !has(prize?.value)) prize?.focus();

    reallyShow(form);
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    focusCurrentLang();
  }

  function bindEvents(){
    if (!form) return;

    btnRaffle?.addEventListener('click', () => showForm('raffle'));
    btnAd?.addEventListener('click',     () => showForm('ad'));

    const baseUrlNoQuery = window.location.pathname + '#admin/banner';
    btnCancel?.addEventListener('click', (e) => {
      e.preventDefault();
      form?.classList.add('is-hidden');
      window.location.replace(baseUrlNoQuery);
    });

    form.addEventListener('submit', (ev) => {
      const { valid, errs } = validateForm();
      if (!valid){
        ev.preventDefault();
        ev.stopPropagation();
        ev.stopImmediatePropagation?.();
        renderErrors(errs);
        return false;
      }

      renderErrors([]);
      form.style.pointerEvents = 'none';
      form.style.opacity = '0.5';
      setTimeout(() => { form.classList.add('is-hidden'); }, 120);
    }, { capture: true });
  }
  
  document.addEventListener('DOMContentLoaded', () => {
    cacheElements();
    initEditors();
    bindEvents();
  });
})();
