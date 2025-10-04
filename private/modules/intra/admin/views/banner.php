<?php
global $language;
require_once PRIVATE_PATH . '/modules/intra/admin/models/Admin.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$adm = new Admin();

$checked = !empty($moduleFlags['banner']);

$ok  = $_SESSION['flash_success_admin'] ?? null;
$err = $_SESSION['flash_error_admin']   ?? null;
unset($_SESSION['flash_success_admin'], $_SESSION['flash_error_admin']);

$history = $adm->getBannerHistory();

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

<div style="display:flex;gap:8px;margin:12px 0">
  <button type="button" class="btn" id="btn-new-raffle">Añadir Sorteo</button>
  <button type="button" class="btn" id="btn-new-ad">Añadir Anuncio</button>
</div>

<form method="post" action="" id="banner-form"
      class="<?= $editing ? '' : 'is-hidden' ?>"
      style="border:1px solid #c00;border-radius:12px;padding:12px;margin-bottom:18px">
  <input type="hidden" name="__action__" value="banner_save">
  <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : '' ?>">
  <input type="hidden" name="type" id="banner-type" value="<?= htmlspecialchars($bn['type']) ?>">

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

  <div id="prize-wrap" style="margin-bottom:10px; <?= ($bn['type']==='raffle' ? '' : 'display:none') ?>">
    <label>Premio (solo Sorteo)
      <input type="text" name="prize" value="<?= htmlspecialchars($bn['prize'] ?? '') ?>"
             placeholder="Ej.: Tablet 10''"
             style="width:100%;padding:.45rem;border:1px solid #c00;border-radius:10px">
    </label>
  </div>

  <nav style="display:flex;gap:6px;margin-bottom:10px">
    <button type="button" class="tab-btn" data-tab="es">Español</button>
    <button type="button" class="tab-btn" data-tab="eu">Euskera</button>
  </nav>

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

  <section class="tab tab-es">
    <label>Título (ES)</label>
    <div id="title-es" class="editor editor-title" contenteditable="true" style="border:1px solid #c00;border-radius:10px;padding:.6rem;min-height:40px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $bn['es']['title'] ?></div>
    <input type="hidden" name="title_es" id="tx-title-es">

    <label>Contenido (ES)</label>
    <div id="ed-es" class="editor" contenteditable="true" style="border:1px solid #c00;border-radius:10px;padding:.6rem;min-height:180px;"><?= $bn['es']['content'] ?></div>
    <textarea name="content_es" id="tx-es" hidden></textarea>
  </section>

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
    <button type="submit" class="btn btn-red"   name="mode" value="schedule">Programar</button>
    <button type="submit" class="btn"           name="mode" value="draft">Borrador</button>

    <a href="#admin/banner" class="btn" id="btn-cancel">Cancelar</a>
    <span style="margin-left:auto;opacity:.7"><?= $editing ? 'Editando #'.$editing['id'] : 'Nuevo' ?></span>
  </div>
</form>

<?php
$now = new DateTime('now');

function dt_or_null($s): ?DateTime {
    if ($s === null) return null;
    $s = trim((string)$s);
    if ($s === '') return null;
    try {
        return new DateTime($s);
    } catch (Throwable $e) {
        return null;
    }
}

function banner_status_label(array $r, DateTime $now): string {
    $status = strtolower(trim((string)($r['status'] ?? 'draft')));
    $start  = dt_or_null($r['date_start']  ?? null);
    $finish = dt_or_null($r['date_finish'] ?? null);

    switch ($status) {
        case 'draft':
            return 'Borrador';

        case 'scheduled':
            if ($start && $now < $start) return 'Programado';
            if ($start && $finish && $now >= $start && $now <= $finish) return 'Ejecutándose';
            if ($finish && $now > $finish) return 'Finalizado';
            return 'Programado';

        case 'running':
            return 'Ejecutándose';

        case 'finished':
            return 'Finalizado';
    }
    return 'Borrador';
}

function banner_is_finished(array $r, DateTime $now): bool {
    $status = strtolower(trim((string)($r['status'] ?? 'draft')));
    if ($status === 'finished') return true;

    if ($status === 'scheduled') {
        $finish = dt_or_null($r['date_finish'] ?? null);
        if ($finish && $now > $finish) return true;
    }
    return false;
}
?>


<h3 style="margin:.5rem 0">Histórico</h3>
<div class="list" style="display:grid;gap:10px">
  <?php if (empty($history)): ?>
    <div class="card"><div class="card-body">Sin registros.</div></div>
  <?php else: ?>
    <?php foreach ($history as $h): ?>
      <?php
        $label      = banner_status_label($h, $now);
        $isFinished = banner_is_finished($h, $now);
        $isRaffle   = filter_var(($h['is_raffle'] ?? false), FILTER_VALIDATE_BOOLEAN);

        $ds = dt_or_null($h['date_start']  ?? null);
        $df = dt_or_null($h['date_finish'] ?? null);
        $dsTxt = $ds ? $ds->format('d/m/Y H:i') : '—';
        $dfTxt = $df ? $df->format('d/m/Y H:i') : '—';

        $titleEs = $h['title_es'] ?? '(sin título ES)';
        $titleEu = $h['title_eu'] ?? '(sin título EU)';
        $prize   = trim((string)($h['prize'] ?? ''));
      ?>
      <div class="card">
        <div class="card-body" style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center">
          <div>
            <div>
              <strong>#<?= (int)$h['id'] ?></strong> · <?= $isRaffle ? 'SORTEO' : 'ANUNCIO' ?>
              <?php if ($isRaffle && $prize !== ''): ?>
                — Premio: <?= htmlspecialchars($prize) ?>
              <?php endif; ?>
            </div>

            <div>Estado: <strong><?= htmlspecialchars($label) ?></strong></div>

            <div><?= htmlspecialchars($titleEs) ?> / <?= htmlspecialchars($titleEu) ?></div>
            <div>Del <?= $dsTxt ?> al <?= $dfTxt ?></div>
          </div>

          <div style="display:flex;gap:6px">
            <?php if (!$isFinished): ?>
              <a class="btn" href="?edit=<?= (int)$h['id'] ?>#admin/banner">Editar</a>
            <?php endif; ?>
            <form method="post" action="" onsubmit="return confirm('¿Eliminar este registro?');">
              <input type="hidden" name="__action__" value="banner_delete">
              <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
              <button class="btn" type="submit">Borrar</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  window.BANNERS_INDEX = <?= json_encode(array_map(function($h){
    return [
      'id'          => (int)$h['id'],
      'date_start'  => $h['date_start'],
      'date_finish' => $h['date_finish'],
    ];
  }, $history ?? [])); ?>;
  window.IS_EDITING = <?= json_encode((bool)$editing) ?>;
</script>

<script src="/assets/js/admin/admin-editor.js" defer></script>
<script src="/assets/js/admin/admin-banner.js" defer></script>
