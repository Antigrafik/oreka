<?php
global $language;

require_once PRIVATE_PATH . '/modules/intra/admin/models/Admin.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$adm   = new Admin();
$legal = $adm->getLatestLegal();

$esTitle   = $legal['es']['title']   ?? 'Bases / Legal';
$esContent = $legal['es']['content'] ?? '<h1>Bases legales y aviso de privacidad</h1>';
$euTitle   = $legal['eu']['title']   ?? 'Oinarriak / Lege-oharra';
$euContent = $legal['eu']['content'] ?? '<h1>Oinarri legalak eta pribatutasun-oharra</h1>';
$currStat  = isset($legal['status']) ? (int)$legal['status'] : 1;

// Mensajes flash (usamos su presencia para mostrar textos localizados)
$flashOk  = $_SESSION['flash_success_admin'] ?? null;
$flashErr = $_SESSION['flash_error_admin']   ?? null;
unset($_SESSION['flash_success_admin'], $_SESSION['flash_error_admin']);
?>

<h1 style="margin-top:0">
  <?= htmlspecialchars($language['legal_editor']['title'] ?? 'LEGAL') ?>
</h1>

<?php if ($flashOk): ?>
  <div class="flash ok">
    <?= htmlspecialchars($language['legal_editor']['flash_ok'] ?? 'Guardado correctamente.') ?>
  </div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="flash err">
    <?= htmlspecialchars($language['legal_editor']['flash_error'] ?? 'Error al guardar:') ?>
  </div>
<?php endif; ?>

<form method="post" action="" id="legal-form">
  <input type="hidden" name="__action__" value="save_legal">

  <div style="display:flex;gap:10px;align-items:center;margin:.5rem 0 1rem">
    <label style="display:flex;align-items:center;gap:.5rem;border:1px solid #c00;padding:.35rem .6rem;border-radius:10px">
      <input type="checkbox" name="status" value="1" <?= $currStat ? 'checked' : '' ?>>
      <strong><?= htmlspecialchars($language['legal_editor']['publish_label'] ?? 'Publicar (mostrar botón y contenido)') ?></strong>
    </label>
    <span style="opacity:.7">
      <?= htmlspecialchars($language['legal_editor']['publish_hint'] ?? 'Si desmarcas, se guarda como borrador y el botón del topbar puede ocultarse.') ?>
    </span>
  </div>

  <!-- Tabs -->
  <nav style="display:flex;gap:6px;margin-bottom:10px">
    <button type="button" class="tab-btn" data-tab="es">
      <?= htmlspecialchars($language['legal_editor']['tab_es'] ?? 'Español') ?>
    </button>
    <button type="button" class="tab-btn" data-tab="eu">
      <?= htmlspecialchars($language['legal_editor']['tab_eu'] ?? 'Euskera') ?>
    </button>
  </nav>

  <!-- Toolbar -->
  <div class="toolbar" aria-label="Editor toolbar" style="display:flex;gap:6px;margin:.25rem 0 8px">
    <button type="button" data-cmd="bold"><b><?= htmlspecialchars($language['legal_editor']['toolbar_bold'] ?? 'B') ?></b></button>
    <button type="button" data-cmd="italic"><i><?= htmlspecialchars($language['legal_editor']['toolbar_italic'] ?? 'I') ?></i></button>
    <button type="button" data-cmd="underline"><u><?= htmlspecialchars($language['legal_editor']['toolbar_underline'] ?? 'U') ?></u></button>
    <span style="width:1px;background:#c00;opacity:.5"></span>
    <button type="button" data-block="h2"><?= htmlspecialchars($language['legal_editor']['toolbar_h2'] ?? 'H2') ?></button>
    <button type="button" data-block="h3"><?= htmlspecialchars($language['legal_editor']['toolbar_h3'] ?? 'H3') ?></button>
    <button type="button" data-cmd="insertUnorderedList"><?= htmlspecialchars($language['legal_editor']['toolbar_ul'] ?? '• Lista') ?></button>
    <button type="button" data-cmd="insertOrderedList"><?= htmlspecialchars($language['legal_editor']['toolbar_ol'] ?? '1. Lista') ?></button>
    <button type="button" id="btn-link"><?= htmlspecialchars($language['legal_editor']['toolbar_link'] ?? 'Enlace') ?></button>
    <button type="button" id="btn-clear"><?= htmlspecialchars($language['legal_editor']['toolbar_clear'] ?? 'Quitar formato') ?></button>
    <span style="margin-left:auto;opacity:.7">
      <?= htmlspecialchars($language['legal_editor']['toolbar_tip'] ?? 'Consejo: pega texto desde Word y ajusta con la barra.') ?>
    </span>
  </div>

  <!-- ES -->
  <section class="tab tab-es">
    <label><?= htmlspecialchars($language['legal_editor']['title_es_label'] ?? 'Título (ES)') ?></label>
    <input name="title_es" type="text" value="<?= htmlspecialchars($esTitle) ?>"
           style="width:100%;padding:.6rem;border:1px solid #c00;border-radius:10px;margin:.25rem 0 .75rem">

    <label><?= htmlspecialchars($language['legal_editor']['content_es_label'] ?? 'Contenido (ES)') ?></label>
    <div id="ed-es" class="editor" contenteditable="true"><?= $esContent ?></div>
    <textarea name="content_es" id="tx-es" hidden></textarea>
  </section>

  <!-- EU -->
  <section class="tab tab-eu" hidden>
    <label><?= htmlspecialchars($language['legal_editor']['title_eu_label'] ?? 'Izenburua (EU)') ?></label>
    <input name="title_eu" type="text" value="<?= htmlspecialchars($euTitle) ?>"
           style="width:100%;padding:.6rem;border:1px solid #c00;border-radius:10px;margin:.25rem 0 .75rem">

    <label><?= htmlspecialchars($language['legal_editor']['content_eu_label'] ?? 'Edukia (EU)') ?></label>
    <div id="ed-eu" class="editor" contenteditable="true"><?= $euContent ?></div>
    <textarea name="content_eu" id="tx-eu" hidden></textarea>
  </section>

  <div style="display:flex;gap:8px;margin-top:12px">
    <button type="submit" name="mode" value="draft" class="btn btn-outline">
      <?= htmlspecialchars($language['legal_editor']['save_draft'] ?? 'Guardar borrador') ?>
    </button>
    <button type="submit" name="mode" value="publish" class="btn btn-red">
      <?= htmlspecialchars($language['legal_editor']['publish'] ?? 'Publicar') ?>
    </button>
    <span id="save-msg" style="margin-left:auto;color:#0a0"></span>
  </div>
</form>

<script>
(function(){
  const tabs = { es: document.querySelector('.tab-es'), eu: document.querySelector('.tab-eu') };
  const btns = document.querySelectorAll('.tab-btn');
  const editors = { es: document.getElementById('ed-es'), eu: document.getElementById('ed-eu') };
  const fields  = { es: document.getElementById('tx-es'), eu: document.getElementById('tx-eu') };
  let current = 'es';

  btns.forEach(b => b.addEventListener('click', () => {
    current = b.dataset.tab;
    Object.entries(tabs).forEach(([k,sec]) => sec.hidden = (k!==current));
    btns.forEach(x => x.classList.toggle('active', x===b));
    editors[current].focus();
  }));
  btns[0].click();

  const toolbar = document.querySelector('.toolbar');
  toolbar.addEventListener('click', (e) => {
    const el = e.target.closest('button'); if(!el) return;
    const cmd = el.dataset.cmd; const block = el.dataset.block;
    const ed = editors[current];
    if (cmd) { document.execCommand(cmd, false, null); ed.focus(); return; }
    if (block) { document.execCommand('formatBlock', false, block); ed.focus(); return; }
  });

  document.getElementById('btn-link').addEventListener('click', () => {
    const url = prompt(<?= json_encode($language['legal_editor']['link_prompt'] ?? 'URL del enlace (https://...)') ?>);
    if (url) document.execCommand('createLink', false, url);
    editors[current].focus();
  });
  document.getElementById('btn-clear').addEventListener('click', () => {
    document.execCommand('removeFormat', false, null);
    editors[current].focus();
  });

  document.getElementById('legal-form').addEventListener('submit', () => {
    fields.es.value = editors.es.innerHTML.trim();
    fields.eu.value = editors.eu.innerHTML.trim();
    const mode = (document.activeElement && document.activeElement.name === 'mode')
                   ? document.activeElement.value : 'draft';
    if (mode === 'publish') document.querySelector('input[name="status"]').checked = true;
  });
})();
</script>
