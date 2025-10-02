<?php
global $language;
require_once PRIVATE_PATH . '/modules/intra/admin/models/Admin.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$adm = new Admin();

/* Toggle módulo */
$checked = !empty($moduleFlags['banner']);

/* Flash */
$ok  = $_SESSION['flash_success_admin'] ?? null;
$err = $_SESSION['flash_error_admin']   ?? null;
unset($_SESSION['flash_success_admin'], $_SESSION['flash_error_admin']);

/* Datos */
$history = $adm->getBannerHistory();

/* Si se viene a editar (por querystring opcional) */
$editId  = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editing = $editId ? $adm->getBanner($editId) : null;

$def = [
  'type'        => 'ad',
  'is_raffle'   => 0,
  'prize'       => '',
  'date_start'  => '',
  'date_finish' => '',
  'es' => ['title'=>'','content'=>''],
  'eu' => ['title'=>'','content'=>'']
];
$bn = $editing ? array_merge($def, $editing, [
        'type' => $editing['is_raffle'] ? 'raffle' : 'ad'
      ]) : $def;
?>

<h2>Banner</h2>

<!-- Toggle del módulo -->
<form method="post" action="" class="mod-toggle" style="margin-bottom:14px">
  <input type="hidden" name="__action__"  value="toggle_module">
  <input type="hidden" name="module_key"  value="banner">
  <input type="hidden" name="redirect"    value="banner">
  <label class="toggle">
    <input type="checkbox" name="visible" value="1" <?= $checked ? 'checked' : '' ?>>
    <?= htmlspecialchars($language['admin_toggle']['label'] ?? 'Mostrar este módulo (y su menú)') ?>
  </label>
  <button class="btn btn-red" type="submit">
    <?= htmlspecialchars($language['admin_toggle']['save'] ?? 'Guardar') ?>
  </button>
</form>

<?php if ($ok):  ?><div class="flash ok"><?= htmlspecialchars($ok)  ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Botonera modo creación -->
<div style="display:flex;gap:8px;margin:12px 0">
  <button type="button" class="btn" id="btn-new-raffle">Añadir Sorteo</button>
  <button type="button" class="btn" id="btn-new-ad">Añadir Anuncio</button>
</div>

<!-- Formulario crear/editar -->
<form method="post" action="" id="banner-form"
      class="<?= $editing ? '' : 'is-hidden' ?>"
      style="border:1px solid #c00;border-radius:12px;padding:12px;margin-bottom:18px">
  <input type="hidden" name="__action__" value="banner_save">
  <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : '' ?>">
  <input type="hidden" name="type" id="banner-type" value="<?= htmlspecialchars($bn['type']) ?>">

  <!-- Fechas -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
    <label>Inicio (fecha y hora)
      <input type="datetime-local" name="date_start"
             value="<?= $bn['date_start'] ? date('Y-m-d\TH:i', strtotime($bn['date_start'])) : '' ?>"
             style="width:100%;padding:.45rem;border:1px solid #c00;border-radius:10px" required>
    </label>
    <label>Fin (fecha y hora)
      <input type="datetime-local" name="date_finish"
             value="<?= $bn['date_finish'] ? date('Y-m-d\TH:i', strtotime($bn['date_finish'])) : '' ?>"
             style="width:100%;padding:.45rem;border:1px solid #c00;border-radius:10px" required>
    </label>
  </div>

  <!-- Premio (solo sorteo) -->
  <div id="prize-wrap" style="margin-bottom:10px; <?= ($bn['type']==='raffle' ? '' : 'display:none') ?>">
    <label>Premio (solo Sorteo)
      <input type="text" name="prize" value="<?= htmlspecialchars($bn['prize'] ?? '') ?>"
             placeholder="Ej.: Tablet 10''"
             style="width:100%;padding:.45rem;border:1px solid #c00;border-radius:10px">
    </label>
  </div>

  <!-- Tabs idiomas -->
  <nav style="display:flex;gap:6px;margin-bottom:10px">
    <button type="button" class="tab-btn" data-tab="es">Español</button>
    <button type="button" class="tab-btn" data-tab="eu">Euskera</button>
  </nav>

  <!-- Toolbar -->
  <div class="toolbar" style="display:flex;gap:6px;margin:.25rem 0 8px">
    <button type="button" data-cmd="bold"><b>B</b></button>
    <button type="button" data-cmd="italic"><i>I</i></button>
    <button type="button" data-cmd="underline"><u>U</u></button>
    <span style="width:1px;background:#c00;opacity:.5"></span>
    <button type="button" data-block="h2">H2</button>
    <button type="button" data-block="h3">H3</button>
    <button type="button" data-cmd="insertUnorderedList">• Lista</button>
    <button type="button" data-cmd="insertOrderedList">1. Lista</button>
    <button type="button" id="btn-link">Enlace</button>
    <button type="button" id="btn-clear">Quitar formato</button>
  </div>

  <!-- ES -->
  <section class="tab tab-es">
    <label>Título (ES)</label>
    <div id="title-es" class="editor editor-title" contenteditable="true" style="border:1px solid #c00;border-radius:10px;padding:.6rem;min-height:40px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $bn['es']['title'] ?></div>
    <input type="hidden" name="title_es" id="tx-title-es">

    <label>Contenido (ES)</label>
    <div id="ed-es" class="editor" contenteditable="true" style="border:1px solid #c00;border-radius:10px;padding:.6rem;min-height:180px;"><?= $bn['es']['content'] ?></div>
    <textarea name="content_es" id="tx-es" hidden></textarea>
  </section>

  <!-- EU -->
  <section class="tab tab-eu" hidden>
    <label>Izenburua (EU)</label>
    <div id="title-eu" class="editor editor-title" contenteditable="true" style="border:1px solid #c00;border-radius:10px;padding:.6rem;min-height:40px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $bn['eu']['title'] ?></div>
    <input type="hidden" name="title_eu" id="tx-title-eu">

    <label>Edukia (EU)</label>
    <div id="ed-eu" class="editor" contenteditable="true" style="border:1px solid #c00;border-radius:10px;padding:.6rem;min-height:180px;"><?= $bn['eu']['content'] ?></div>
    <textarea name="content_eu" id="tx-eu" hidden></textarea>
  </section>

  <div id="banner-errors" class="flash err" style="display:none"></div>
  <div style="display:flex;gap:8px;margin-top:12px">
    <button type="submit" class="btn btn-red">Guardar</button>
    <a href="#admin/banner" class="btn" id="btn-cancel">Cancelar</a>
    <span style="margin-left:auto;opacity:.7"><?= $editing ? 'Editando #'.$editing['id'] : 'Nuevo' ?></span>
  </div>
</form>

<!-- Histórico -->
<h3 style="margin:.5rem 0">Histórico</h3>
<div class="list" style="display:grid;gap:10px">
  <?php if (empty($history)): ?>
    <div class="card"><div class="card-body">Sin registros.</div></div>
  <?php else: foreach ($history as $h): ?>
    <div class="card">
      <div class="card-body" style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center">
        <div>
          <div>
            <strong>#<?= (int)$h['id'] ?></strong> · <?= $h['is_raffle'] ? 'SORTEO' : 'ANUNCIO' ?>
            <?php if ($h['is_raffle'] && !empty($h['prize'])): ?>
              — Premio: <?= htmlspecialchars($h['prize']) ?>
            <?php endif; ?>
          </div>
          <div>Estado: <strong><?= htmlspecialchars($h['status'] ?? 'draft') ?></strong></div>
          <div><?= htmlspecialchars($h['title_es'] ?? '(sin título ES)') ?> / <?= htmlspecialchars($h['title_eu'] ?? '(sin título EU)') ?></div>
          <div>Del <?= date('d/m/Y H:i', strtotime($h['date_start'])) ?> al <?= date('d/m/Y H:i', strtotime($h['date_finish'])) ?></div>
        </div>
        <div style="display:flex;gap:6px">
          <a class="btn" href="?edit=<?= (int)$h['id'] ?>#admin/banner">Editar</a>
          <form method="post" action="" onsubmit="return confirm('¿Eliminar este registro?');">
            <input type="hidden" name="__action__" value="banner_delete">
            <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
            <button class="btn" type="submit">Borrar</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  initRichEditor({
    root: document.getElementById('banner-form'),
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
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form      = document.getElementById('banner-form');
  const errorBox  = document.getElementById('banner-errors');

  // utilidades que ya tienes:
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

  if (form) {
    form.addEventListener('submit', function (e) {
      let errors = [];

      const titleEs   = document.querySelector('#title-es')?.innerText.trim();
      const contentEs = document.querySelector('#ed-es')?.innerText.trim();
      const titleEu   = document.querySelector('#title-eu')?.innerText.trim();
      const contentEu = document.querySelector('#ed-eu')?.innerText.trim();
      const dateStart = document.querySelector('input[name="date_start"]')?.value;
      const dateFinish= document.querySelector('input[name="date_finish"]')?.value;

      // 1) Vacíos
      if (!titleEs)   errors.push("Falta el título en Español");
      if (!contentEs) errors.push("Falta el contenido en Español");
      if (!titleEu)   errors.push("Falta el título en Euskera");
      if (!contentEu) errors.push("Falta el contenido en Euskera");
      if (!dateStart) errors.push("Falta la fecha de inicio");
      if (!dateFinish)errors.push("Falta la fecha de fin");

      // 2) Fechas (si están)
      if (dateStart && dateFinish) {
        const now    = new Date();
        now.setSeconds(0,0);
        const start  = new Date(dateStart);
        const finish = new Date(dateFinish);

        if (start < now) {
          errors.push("La fecha de inicio no puede ser anterior a la fecha actual");
        }
        if (finish < now) {
          errors.push("La fecha de fin no puede ser anterior a la fecha actual");
        }
        if (start >= finish) {
          errors.push("La fecha de inicio debe ser anterior a la fecha de fin");
        }
      }

      if (errors.length > 0) {
        // BLOQUEA TOTALMENTE el submit y cualquier otro handler
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();

        // Mantén el editor visible y muestra errores
        reallyShow(form);
        if (errorBox) {
          errorBox.style.display = "block";
          errorBox.innerHTML = errors.map(err => `<div>${err}</div>`).join("");
        }
        // Lleva el foco de nuevo al editor
        focusCurrentLang();

        return false; // corta aquí
      } else {
        // sin errores: oculta el box y deja que el submit siga su curso
        if (errorBox) {
          errorBox.style.display = "none";
          errorBox.innerHTML = "";
        }
      }
    }, true); // <- captura: nos aseguramos de interceptar antes que otros listeners
  }
});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
  const form       = document.getElementById('banner-form');
  const btnRaffle  = document.getElementById('btn-new-raffle');
  const btnAd      = document.getElementById('btn-new-ad');
  const btnCancel  = document.getElementById('btn-cancel');
  const typeInput  = document.getElementById('banner-type');
  const prizeWrap  = document.getElementById('prize-wrap');
  const isEditing = <?= json_encode((bool)$editing) ?>;

  function clearIfNew(){
    if (isEditing) return;
    const $ = (s) => document.querySelector(s);
    $('input[name="date_start"]') && ( $('input[name="date_start"]').value = '' );
    $('input[name="date_finish"]') && ( $('input[name="date_finish"]').value = '' );
    $('input[name="prize"]') && ( $('input[name="prize"]').value = '' );
    const te = document.getElementById('title-es');
    const tu = document.getElementById('title-eu');
    const ce = document.getElementById('ed-es');
    const cu = document.getElementById('ed-eu');
    te && (te.innerHTML = '');
    tu && (tu.innerHTML = '');
    ce && (ce.innerHTML = '');
    cu && (cu.innerHTML = '');
  }

  function showForm(type){
    if (!form) return;
    form.classList.remove('is-hidden');
    if (typeInput) typeInput.value = type;             // 'raffle' | 'ad'
    if (prizeWrap) prizeWrap.style.display = (type === 'raffle') ? '' : 'none';
    clearIfNew();
    const top = form.getBoundingClientRect().top + window.pageYOffset - 100;
    window.scrollTo({ top, behavior: 'smooth' });
  }

  // Abrir para crear
  btnRaffle?.addEventListener('click', () => showForm('raffle'));
  btnAd?.addEventListener('click',     () => showForm('ad'));

  // URL base sin query para limpiar ?edit=...
  const baseUrlNoQuery = window.location.pathname + '#admin/banner';

  // Cancelar: SIEMPRE limpia la URL y oculta
  btnCancel?.addEventListener('click', (e) => {
    e.preventDefault();
    form?.classList.add('is-hidden');
    // navegación a la URL sin ?edit
    window.location.replace(baseUrlNoQuery);
  });

  // Al enviar: oculta de inmediato y deja que el servidor redirija a la URL limpia
  form?.addEventListener('submit', () => {
    form.classList.add('is-hidden');
  });

  // Si vienes con ?edit=ID, PHP no mete la clase is-hidden y se ve;
  // tras guardar/borrar el controlador ya redirige a la URL sin ?edit.
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('banner-form');
  if (!form) return;

  form.addEventListener('submit', () => {
    // feedback visual inmediato (sin impedir el envío)
    form.style.pointerEvents = 'none';
    form.style.opacity = '0.5';
    // ocultar un pelín después para no interferir con el volcado del editor
    setTimeout(() => { form.classList.add('is-hidden'); }, 120);
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('banner-form');
  if (!form) return;

  // Oculta el editor justo al enviar (no cancela el submit)
  form.addEventListener('submit', () => {
    form.style.display = 'none';
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form   = document.getElementById('banner-form');
  const errBox = document.getElementById('banner-errors');
  if (!form || !errBox) return;

  function textFromCE(sel) {
    const el = form.querySelector(sel);
    if (!el) return '';
    // .innerText es mejor para ver si realmente hay contenido visible
    return (el.innerText || '').replace(/\u00A0/g, ' ').trim();
  }
  function has(v) { return (v || '').trim().length > 0; }

  form.addEventListener('submit', (ev) => {
    // Limpia error visual anterior
    errBox.style.display = 'none';
    errBox.innerHTML = '';

    const type   = (form.querySelector('#banner-type')?.value || 'ad').toLowerCase();
    const d1     = form.querySelector('input[name="date_start"]')?.value || '';
    const d2     = form.querySelector('input[name="date_finish"]')?.value || '';
    const prize  = form.querySelector('input[name="prize"]')?.value || '';
    const tEs    = textFromCE('#title-es');
    const cEs    = textFromCE('#ed-es');
    const tEu    = textFromCE('#title-eu');
    const cEu    = textFromCE('#ed-eu');

    const errs = [];
    if (!has(d1) || !has(d2)) errs.push('Indica fecha y hora de inicio y fin.');
    if (!has(tEs) || !has(cEs)) errs.push('Completa Título y Contenido en Español.');
    if (!has(tEu) || !has(cEu)) errs.push('Completa Título y Contenido en Euskera.');
    if (type === 'raffle' && !has(prize)) errs.push('El premio es obligatorio para Sorteo.');

    if (errs.length) {
      // BLOQUEA por completo el submit y cualquier otro handler subsiguiente
      ev.preventDefault();
      ev.stopImmediatePropagation();

      // Pinta errores dentro del formulario
      errBox.innerHTML = errs.map(e => `<div>• ${e}</div>`).join('');
      errBox.style.display = 'block';

      // Enfoca el primer campo que falte
      if (!has(d1)) { form.querySelector('input[name="date_start"]')?.focus(); }
      else if (!has(d2)) { form.querySelector('input[name="date_finish"]')?.focus(); }
      else if (!has(tEs)) { form.querySelector('#title-es')?.focus(); }
      else if (!has(cEs)) { form.querySelector('#ed-es')?.focus(); }
      else if (!has(tEu)) { form.querySelector('#title-eu')?.focus(); }
      else if (!has(cEu)) { form.querySelector('#ed-eu')?.focus(); }
      else if (type === 'raffle' && !has(prize)) { form.querySelector('input[name="prize"]')?.focus(); }

      // Asegura que el formulario siga visible y en pantalla
      form.style.display = '';
      form.removeAttribute('hidden');
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      return false;
    }

    // Si no hay errores, dejamos que el submit siga su curso normal
    // (no tocar nada más aquí: tus flujos de “añadir/editar y ocultar al guardar”
    // ya se encargan con la redirección y los flashes).
  }, { capture: true });
});
</script>


