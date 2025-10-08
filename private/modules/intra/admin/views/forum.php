<?php
global $language;
$moduleFlags = $GLOBALS['moduleFlags'] ?? [];
$checked = !empty($moduleFlags['forum']);

$ok  = $_SESSION['flash_success_admin'] ?? null;
$err = $_SESSION['flash_error_admin']   ?? null;
unset($_SESSION['flash_success_admin'], $_SESSION['flash_error_admin']);

$pdo = $GLOBALS['pdo'] ?? null;

/* ==== Helpers (prefijo forum_ para no colisionar) ==== */
function forum_dt_or_null($s): ?DateTime {
  if ($s===null) return null;
  $s=trim((string)$s);
  if($s==='')return null;
  try{ return new DateTime($s);}catch(Throwable $e){return null;}
}

function forum_status_label(array $r, DateTime $now): string {
  global $language, $language_es;
  $L=$language['admin_banner']??[]; $Les=isset($language_es)?($language_es['admin_banner']??[]):[];
  $t=function(string $k,string $fb)use($L,$Les){return htmlspecialchars($L[$k]??$Les[$k]??$fb);};
  $status=strtolower(trim((string)($r['status']??'draft')));
  $start=forum_dt_or_null($r['date_start']??null);
  $finish=forum_dt_or_null($r['date_finish']??null);

  if($status==='draft')     return $t('draft_status','Borrador');
  if($status==='finished')  return $t('finished_status','Finalizado');
  if($status==='running')   return $t('running_status','Ejecutándose');
  if($start && $now<$start) return $t('scheduled_status','Programado');
  if($start && $finish && $now>=$start && $now<=$finish) return $t('running_status','Ejecutándose');
  if($finish && $now>$finish) return $t('finished_status','Finalizado');
  return $t('scheduled_status','Programado');
}

if (!function_exists('forum_sanitize_inline')) {
  function forum_sanitize_inline($html) {
    // Solo formato inline básico seguro para títulos
    $html = strip_tags((string)$html, '<b><strong><i><em><u><br>');
    // Defensa extra: elimina esquemas javascript:
    $html = preg_replace('~javascript:~i', '', $html);
    return $html;
  }
}

/* ==== Carga histórico (sin depender obligatoriamente de forum_attendee) ==== */
$history = [];
$history_error = null;
$attendeeTableExists = false;

try {
  if ($pdo) {
    $sql = "
      SELECT f.id, f.date_start, f.date_finish, f.status, f.url,
             es.title AS title_es, eu.title AS title_eu
      FROM forum f
      LEFT JOIN link l         ON l.id_forum = f.id
      LEFT JOIN translation es ON es.id_link = l.id AND es.lang = 'es'
      LEFT JOIN translation eu ON eu.id_link = l.id AND eu.lang = 'eu'
      ORDER BY f.id DESC";
    $history = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // ¿Existe tabla de asistentes?
    $q = $pdo->query("SELECT 1 FROM sys.objects WHERE name = 'forum_attendee' AND type = 'U'");
    $attendeeTableExists = (bool)$q->fetchColumn();

    // Si existe, añadimos contadores
    if ($attendeeTableExists && !empty($history)) {
      $ids = array_map(fn($r) => (int)$r['id'], $history);
      $ids = array_values(array_unique(array_filter($ids)));
      if (!empty($ids)) {
        $place = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT id_forum, COUNT(*) AS c FROM forum_attendee WHERE id_forum IN ($place) GROUP BY id_forum");
        $st->execute($ids);
        $counts = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
          $counts[(int)$row['id_forum']] = (int)$row['c'];
        }
        foreach ($history as &$h) {
          $id = (int)$h['id'];
          $h['attendees'] = $counts[$id] ?? 0;
        }
        unset($h);
      }
    }
  }
} catch (Throwable $e) {
  $history = [];
  $history_error = $e->getMessage();
}

/* ==== Edición vía ?edit=ID ==== */
$editData = null;
$editId   = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0 && $pdo) {
  try {
    $st = $pdo->prepare("
      SELECT TOP (1) f.*, l.id AS link_id
      FROM forum f
      LEFT JOIN link l ON l.id_forum = f.id
      WHERE f.id = ?");
    $st->execute([$editId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      $editData = [
        'id'          => (int)$row['id'],
        'date_start'  => (string)$row['date_start'],
        'date_finish' => (string)$row['date_finish'],
        'url'         => (string)($row['url'] ?? ''),
        'es' => ['title'=>'','content'=>''],
        'eu' => ['title'=>'','content'=>''],
      ];
      if (!empty($row['link_id'])) {
        $tq = $pdo->prepare("SELECT lang, title, content FROM translation WHERE id_link = ? AND lang IN ('es','eu')");
        $tq->execute([(int)$row['link_id']]);
        foreach ($tq->fetchAll(PDO::FETCH_ASSOC) as $t) {
          $lng = strtolower($t['lang']);
          $editData[$lng] = ['title'=>$t['title'] ?? '', 'content'=>$t['content'] ?? ''];
        }
      }
    }
  } catch (Throwable $e) {}
}

$now = new DateTime('now');
?>

<link rel="stylesheet" href="/assets/css/admin/forum.css">

<h2><?= htmlspecialchars($language['menu_admin']['forum'] ?? 'Foro') ?></h2>

<form method="post" action="" class="mod-toggle" style="margin-bottom:14px">
  <input type="hidden" name="__action__"  value="toggle_module">
  <input type="hidden" name="module_key"  value="forum">
  <input type="hidden" name="redirect"    value="forum">
  <label class="toggle">
    <input type="checkbox" name="visible" value="1" <?= $checked ? 'checked' : '' ?>>
    <?= htmlspecialchars($language['admin_toggle']['label'] ?? 'Mostrar este módulo') ?>
  </label>
  <button class="btn btn-red" type="submit">
    <?= htmlspecialchars($language['admin_toggle']['save'] ?? 'Guardar') ?>
  </button>
</form>

<?php
// --- Leer puntos vigentes del módulo 'forum' ---
$forumPoints = 0;
try {
  if ($pdo) {
    $st = $pdo->prepare("
      SELECT TOP (1) points
      FROM dbo.point_modules
      WHERE module_code = 'forum' AND effective_to IS NULL
      ORDER BY id DESC
    ");
    $st->execute();
    $forumPoints = (int)($st->fetchColumn() ?? 0);
  }
} catch (Throwable $e) {
  // opcional: log del error
}
?>

<!-- Bloque puntos del módulo Forum -->
<div id="forum-points-box" style="display:flex;gap:10px;align-items:center;margin:8px 0 18px">
  <div>
    <span><?= htmlspecialchars($language['admin_forum']['points_label'] ?? 'Puntos por actividad del módulo:') ?></span>
    <strong id="forum-points-text"><?= (int)$forumPoints ?></strong>
  </div>

  <form method="post" action="" id="forum-points-form" style="display:inline-flex;gap:8px;align-items:center">
    <input type="hidden" name="__action__" value="forum_update_points">
    <input type="hidden" name="module_key" value="forum">
    <input type="number" name="points" id="forum-points-input"
           value="<?= (int)$forumPoints ?>" min="0" step="1"
           style="width:90px;display:none;padding:.35rem;border:1px solid #0aa;border-radius:8px">
    <button type="button" class="btn" id="btn-edit-points">
      <?= htmlspecialchars($language['admin_forum']['edit_points'] ?? 'Modificar puntos') ?>
    </button>
    <button type="submit" class="btn" id="btn-save-points" style="display:none">
      <?= htmlspecialchars($language['admin_forum']['save_points'] ?? 'Guardar puntos') ?>
    </button>
  </form>
</div>

<script>
(() => {
  const txt   = document.getElementById('forum-points-text');
  const input = document.getElementById('forum-points-input');
  const bEdit = document.getElementById('btn-edit-points');
  const bSave = document.getElementById('btn-save-points');
  const form  = document.getElementById('forum-points-form');

  if (bEdit && bSave && txt && input && form) {
    bEdit.addEventListener('click', () => {
      txt.style.display   = 'none';
      input.style.display = '';
      bEdit.style.display = 'none';
      bSave.style.display = '';
      input.focus();
      input.select?.();
    });

    form.addEventListener('submit', (e) => {
      const v = Number(input.value);
      if (!Number.isFinite(v) || v < 0) {
        e.preventDefault();
        alert('Introduce un número de puntos válido (>= 0).');
      }
    });
  }
})();
</script>



<?php if ($ok):  ?><div class="flash ok"><?= htmlspecialchars($ok)  ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div style="display:flex;gap:8px;margin:12px 0">
  <button type="button" class="btn" id="btn-new-activity">
    <?= htmlspecialchars($language['admin_forum']['add_activity'] ?? 'Añadir Actividad') ?>
  </button>
</div>

<form method="post" action="" id="forum-form"
      class="<?= $editData ? '' : 'is-hidden' ?>"
      style="border:1px solid #0aa;border-radius:12px;padding:12px;margin-bottom:18px">
  <input type="hidden" name="__action__" value="forum_save">
  <input type="hidden" name="id" value="<?= $editData ? (int)$editData['id'] : '' ?>">

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
    <label><?= htmlspecialchars($language['admin_banner']['start_date'] ?? 'Inicio (fecha y hora)') ?>
      <input type="datetime-local" name="date_start"
             value="<?= $editData && $editData['date_start'] ? date('Y-m-d\TH:i', strtotime($editData['date_start'])) : '' ?>"
             style="width:100%;padding:.45rem;border:1px solid #0aa;border-radius:10px" required>
    </label>
    <label><?= htmlspecialchars($language['admin_banner']['end_date'] ?? 'Fin (fecha y hora)') ?>
      <input type="datetime-local" name="date_finish"
             value="<?= $editData && $editData['date_finish'] ? date('Y-m-d\TH:i', strtotime($editData['date_finish'])) : '' ?>"
             style="width:100%;padding:.45rem;border:1px solid #0aa;border-radius:10px" required>
    </label>
  </div>

  <label><?= htmlspecialchars($language['admin_forum']['url'] ?? 'URL') ?>
    <input type="url" name="url" value="<?= $editData ? htmlspecialchars($editData['url'] ?? '') : '' ?>"
           placeholder="https://…"
           style="width:100%;padding:.45rem;border:1px solid #0aa;border-radius:10px;margin-bottom:10px">
  </label>

  <nav style="display:flex;gap:6px;margin-bottom:10px">
    <button type="button" class="tab-btn" data-tab="es"><?= htmlspecialchars($language['admin_banner']['es'] ?? 'Español') ?></button>
    <button type="button" class="tab-btn" data-tab="eu"><?= htmlspecialchars($language['admin_banner']['eu'] ?? 'Euskera') ?></button>
  </nav>

  <div class="toolbar" style="display:flex;gap:6px;margin:.25rem 0 8px">
    <button type="button" data-cmd="bold"><b>B</b></button>
    <button type="button" data-cmd="italic"><i>I</i></button>
    <button type="button" data-cmd="underline"><u>U</u></button>
    <span style="width:1px;background:#0aa;opacity:.5"></span>
    <button type="button" data-block="h2">H2</button>
    <button type="button" data-block="h3">H3</button>
    <button type="button" data-cmd="insertUnorderedList">• <?= htmlspecialchars($language['admin_banner']['list'] ?? 'Lista') ?></button>
    <button type="button" data-cmd="insertOrderedList">1. <?= htmlspecialchars($language['admin_banner']['list'] ?? 'Lista') ?></button>
    <button type="button" id="btn-link"><?= htmlspecialchars($language['admin_banner']['link'] ?? 'Enlace') ?></button>
    <button type="button" id="btn-clear"><?= htmlspecialchars($language['admin_banner']['clear_format'] ?? 'Quitar formato') ?></button>
  </div>

  <section class="tab tab-es">
    <label><?= htmlspecialchars($language['admin_banner']['title'] ?? 'Título (ES)') ?></label>
    <div id="title-es" class="editor editor-title" contenteditable="true" style="border:1px solid #0aa;border-radius:10px;padding:.6rem;min-height:40px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $editData ? ($editData['es']['title'] ?? '') : '' ?></div>
    <input type="hidden" name="title_es" id="tx-title-es">

    <label><?= htmlspecialchars($language['admin_banner']['content'] ?? 'Contenido (ES)') ?></label>
    <div id="ed-es" class="editor" contenteditable="true" style="border:1px solid #0aa;border-radius:10px;padding:.6rem;min-height:180px;"><?= $editData ? ($editData['es']['content'] ?? '') : '' ?></div>
    <textarea name="content_es" id="tx-es" hidden></textarea>
  </section>

  <section class="tab tab-eu" hidden>
    <label><?= htmlspecialchars($language['admin_banner']['title'] ?? 'Izenburua (EU)') ?></label>
    <div id="title-eu" class="editor editor-title" contenteditable="true" style="border:1px solid #0aa;border-radius:10px;padding:.6rem;min-height:40px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $editData ? ($editData['eu']['title'] ?? '') : '' ?></div>
    <input type="hidden" name="title_eu" id="tx-title-eu">

    <label><?= htmlspecialchars($language['admin_banner']['content'] ?? 'Edukia (EU)') ?></label>
    <div id="ed-eu" class="editor" contenteditable="true" style="border:1px solid #0aa;border-radius:10px;padding:.6rem;min-height:180px;"><?= $editData ? ($editData['eu']['content'] ?? '') : '' ?></div>
    <textarea name="content_eu" id="tx-eu" hidden></textarea>
  </section>

  <div id="forum-errors" class="flash err" style="display:none"></div>
  <div style="display:flex;gap:8px;margin-top:12px">
    <button type="submit" class="btn btn-red"   name="mode" value="schedule"><?= htmlspecialchars($language['admin_banner']['program'] ?? 'Programar') ?></button>
    <button type="submit" class="btn"           name="mode" value="draft"><?= htmlspecialchars($language['admin_banner']['draft'] ?? 'Borrador') ?></button>
    <button type="button" class="btn" id="btn-cancel"><?= htmlspecialchars($language['admin_banner']['cancel'] ?? 'Cancelar') ?></button>
  </div>
</form>

<h3 style="margin:.5rem 0"><?= htmlspecialchars($language['admin_banner']['historical'] ?? 'Histórico') ?></h3>
<?php if ($history_error): ?>
  <div class="flash err" style="margin-bottom:8px">Aviso: no se ha podido cargar el histórico (<?= htmlspecialchars($history_error) ?>).</div>
<?php endif; ?>
<div class="list" style="display:grid;gap:10px">
  <?php if (empty($history)): ?>
    <div class="card"><div class="card-body"><?= htmlspecialchars($language['admin_banner']['no_records'] ?? 'Sin registros.') ?></div></div>
  <?php else: ?>
    <?php foreach ($history as $h): ?>
      <?php
        $label   = forum_status_label($h, $now);
        $dsTxt   = $h['date_start']  ? date('d/m/Y H:i', strtotime($h['date_start']))  : '—';
        $dfTxt   = $h['date_finish'] ? date('d/m/Y H:i', strtotime($h['date_finish'])) : '—';
        $titleEs = $h['title_es'] ?? ($language['admin_banner']['untitled_ES'] ?? 'Sin título (ES)');
        $titleEu = $h['title_eu'] ?? ($language['admin_banner']['untitled_EU'] ?? 'Sin título (EU)');

        // finished => no editable
        $isFinished = (htmlspecialchars_decode($label, ENT_QUOTES) === ($language['admin_banner']['finished_status'] ?? 'Finalizado'));
        $editHref   = '?edit=' . (int)$h['id'] . '#admin/forum';

        // asistentes (si existe la tabla)
        $att = isset($h['attendees']) ? (int)$h['attendees'] : null;
      ?>
      <div class="card">
        <div class="card-body" style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center">
          <div>
            <div><strong>#<?= (int)$h['id'] ?></strong> · <?= forum_sanitize_inline($titleEs) ?> / <?= forum_sanitize_inline($titleEu) ?></div>
            <div><?= htmlspecialchars($language['admin_banner']['status:'] ?? 'Estado: ') ?><strong><?= $label ?></strong></div>
            <div><?= htmlspecialchars($language['admin_banner']['period:'] ?? 'Periodo: ') ?><?= $dsTxt ?> - <?= $dfTxt ?></div>
            <?php if (!empty($h['url'])): ?>
              <div>URL: <a href="<?= htmlspecialchars($h['url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($h['url']) ?></a></div>
            <?php endif; ?>
            <?php if ($att !== null): ?>
              <div>
                <?= htmlspecialchars($language['admin_forum']['attendees'] ?? 'Apuntados:') ?>
                <a href="?show_attendees=<?= (int)$h['id'] ?>#admin/forum" class="link-like">(<?= $att ?>)</a>
              </div>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:6px">
            <?php if (!$isFinished): ?>
              <a class="btn" href="<?= $editHref ?>"><?= htmlspecialchars($language['admin_banner']['edit'] ?? 'Editar') ?></a>
            <?php endif; ?>
            <form method="post" action="" onsubmit="return confirm('<?= htmlspecialchars($language['admin_banner']['confirm_delete'] ?? '¿Seguro que quieres eliminar?') ?>');">
              <input type="hidden" name="__action__" value="forum_delete">
              <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
              <button class="btn" type="submit"><?= htmlspecialchars($language['admin_banner']['delete'] ?? 'Borrar') ?></button>
            </form>
          </div>
        </div>

        <?php if ($att !== null && isset($_GET['show_attendees']) && (int)$_GET['show_attendees'] === (int)$h['id']): ?>
          <?php
            $list = [];
            try {
              $stA = $pdo->prepare("
                SELECT a.id_user, u.usuario, u.nombre, u.email, a.joined_at
                FROM forum_attendee a
                LEFT JOIN [user] u ON u.id = a.id_user
                WHERE a.id_forum = ?
                ORDER BY a.joined_at DESC
              ");
              $stA->execute([(int)$h['id']]);
              $list = $stA->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) { $list = []; }
          ?>
          <div class="card-body" style="border-top:1px dashed #0aa">
            <strong><?= htmlspecialchars($language['admin_forum']['attendee_list'] ?? 'Listado de usuarios apuntados') ?></strong>
            <?php if (empty($list)): ?>
              <div style="opacity:.7;margin-top:.25rem"><?= htmlspecialchars($language['admin_forum']['no_attendees'] ?? 'Sin apuntados.') ?></div>
            <?php else: ?>
              <ul style="margin:.5rem 0 0 1rem">
                <?php foreach ($list as $u): ?>
                  <li>#<?= (int)$u['id_user'] ?> — <?= htmlspecialchars($u['nombre'] ?? $u['usuario'] ?? '') ?> (<?= htmlspecialchars($u['email'] ?? '') ?>)</li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  // Variables globales para el JS (igual que banner.php)
  window.FORUM_INDEX = <?= json_encode(array_map(function($h){
    return [
      'id'          => (int)$h['id'],
      'date_start'  => $h['date_start'] ?? null,
      'date_finish' => $h['date_finish'] ?? null,
    ];
  }, $history ?? [])); ?>;
  window.IS_FORUM_EDITING = <?= json_encode((bool)$editData) ?>;
</script>

<script src="/assets/js/admin/admin-editor.js" defer></script>
<script src="/assets/js/admin/admin-forum.js" defer></script>
