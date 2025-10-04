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

$flashOk  = $_SESSION['flash_success_admin'] ?? null;
$flashErr = $_SESSION['flash_error_admin']   ?? null;
unset($_SESSION['flash_success_admin'], $_SESSION['flash_error_admin']);

$checked = !empty($moduleFlags['legal']);
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

<form method="post" action="" class="mod-toggle" style="margin-bottom:14px">
  <input type="hidden" name="__action__"  value="toggle_module">
  <input type="hidden" name="module_key"  value="legal">
  <input type="hidden" name="redirect"    value="legal">
  <label class="toggle">
    <input type="checkbox" name="visible" value="1" <?= $checked ? 'checked' : '' ?>>
    <?= htmlspecialchars($language['admin_toggle']['label'] ?? 'Mostrar este módulo (y su menú)') ?>
  </label>
  <button class="btn btn-red" type="submit">
    <?= htmlspecialchars($language['admin_toggle']['save'] ?? 'Guardar') ?>
  </button>
</form>

<form method="post" action="" id="legal-form">
  <input type="hidden" name="__action__" value="save_legal">
  <input type="hidden" name="admin_legal_id" value="<?= (int)($legal['admin_legal_id'] ?? 0) ?>">
  <input type="hidden" name="link_id"        value="<?= (int)($legal['link_id'] ?? 0) ?>">

  <nav style="display:flex;gap:6px;margin-bottom:10px">
    <button type="button" class="tab-btn" data-tab="es">
      <?= htmlspecialchars($language['legal_editor']['tab_es'] ?? 'Español') ?>
    </button>
    <button type="button" class="tab-btn" data-tab="eu">
      <?= htmlspecialchars($language['legal_editor']['tab_eu'] ?? 'Euskera') ?>
    </button>
    <span style="margin-left:auto;opacity:.7">
      <?= $currStat ? htmlspecialchars($language['legal_editor']['published_badge'] ?? 'Publicado')
                    : htmlspecialchars($language['legal_editor']['draft_badge'] ?? 'Borrador') ?>
    </span>
  </nav>

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
  </div>

  <section class="tab tab-es">
    <label><?= htmlspecialchars($language['legal_editor']['title_es_label'] ?? 'Título (ES)') ?></label>
    <input name="title_es" type="text" value="<?= htmlspecialchars($esTitle) ?>"
           style="width:100%;padding:.6rem;border:1px solid #c00;border-radius:10px;margin:.25rem 0 .75rem">

    <label><?= htmlspecialchars($language['legal_editor']['content_es_label'] ?? 'Contenido (ES)') ?></label>
    <div id="ed-es" class="editor" contenteditable="true"><?= $esContent ?></div>
    <textarea name="content_es" id="tx-es" hidden></textarea>
  </section>

  <section class="tab tab-eu" hidden>
    <label><?= htmlspecialchars($language['legal_editor']['title_eu_label'] ?? 'Izenburua (EU)') ?></label>
    <input name="title_eu" type="text" value="<?= htmlspecialchars($euTitle) ?>"
           style="width:100%;padding:.6rem;border:1px solid #c00;border-radius:10px;margin:.25rem 0 .75rem">

    <label><?= htmlspecialchars($language['legal_editor']['content_eu_label'] ?? 'Edukia (EU)') ?></label>
    <div id="ed-eu" class="editor" contenteditable="true"><?= $euContent ?></div>
    <textarea name="content_eu" id="tx-eu" hidden></textarea>
  </section>

  <div style="display:flex;gap:8px;margin-top:12px">
    <button type="submit" class="btn btn-outline" name="mode" value="save" id="btn-save">
      <?= htmlspecialchars($language['legal_editor']['save'] ?? 'Guardar') ?>
    </button>
    <button type="submit" class="btn btn-red" name="mode" value="publish" id="btn-publish">
      <?= htmlspecialchars($language['legal_editor']['publish'] ?? 'Publicar') ?>
    </button>
    <span id="save-msg" style="margin-left:auto;color:#0a0"></span>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  initRichEditor({
    root: document.getElementById('legal-form'),
    langs: ['es','eu'],
    toolbarSelector: '.toolbar',
    tabBtnSelector: '.tab-btn',
    tabs: { es: '.tab-es', eu: '.tab-eu' },
    title: {
      esInput: 'input[name="title_es"]',
      euInput: 'input[name="title_eu"]'
    },
    content: {
      esCtt: '#ed-es',  esField: '#tx-es',
      euCtt: '#ed-eu',  euField: '#tx-eu'
    }
  });
});
</script>

<script src="/assets/js/admin/admin-editor.js" defer></script>
