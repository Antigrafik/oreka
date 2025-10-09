<?php
if (ob_get_level() === 0) { ob_start(); }
global $language;
$checked = !empty($moduleFlags['recommendations']);

$pdo = $GLOBALS['pdo'] ?? null;

// --- Leer puntos vigentes del módulo 'recommendation' (singular en DB) ---
$recPoints = 0;
try {
  if ($pdo) {
    $st = $pdo->prepare("
      SELECT TOP (1) points
      FROM dbo.point_modules
      WHERE module_code = 'recommendation' AND effective_to IS NULL
      ORDER BY id DESC
    ");
    $st->execute();
    $recPoints = (int)($st->fetchColumn() ?? 0);
  }
} catch (Throwable $e) { /* opcional: log */ }
?>
<h2><?= htmlspecialchars($language['modules']['recommendations'] ?? 'Recomendaciones') ?></h2>

<form method="post" action="" class="mod-toggle">
  <input type="hidden" name="__action__"  value="toggle_module">
  <input type="hidden" name="module_key"  value="recommendations">
  <input type="hidden" name="redirect"    value="community/recommendations">
  <label class="toggle">
    <input type="checkbox" name="visible" value="1" <?= $checked ? 'checked' : '' ?>>
    <?= htmlspecialchars($language['admin_toggle']['label'] ?? 'Mostrar este módulo') ?>
  </label>
  <button class="btn btn-red" type="submit"><?= htmlspecialchars($language['admin_toggle']['save'] ?? 'Guardar') ?></button>
</form>

<!-- === PUNTOS DEL MÓDULO RECOMMENDATION === -->
<div id="rec-points-box" style="display:flex;gap:10px;align-items:center;margin:10px 0 18px">
  <div>
    <span><?= htmlspecialchars($language['admin_recommendations']['points_label'] ?? 'Puntos por actividad del módulo:') ?></span>
    <strong id="rec-points-text"><?= (int)$recPoints ?></strong>
  </div>

  <form method="post" action="" id="rec-points-form" style="display:inline-flex;gap:8px;align-items:center">
    <input type="hidden" name="__action__" value="recommendations_update_points">
    <input type="hidden" name="module_key" value="recommendations"><!-- plural en UI -->
    <input type="number" name="points" id="rec-points-input"
           value="<?= (int)$recPoints ?>" min="0" step="1"
           style="width:90px;display:none;padding:.35rem;border:1px solid #0aa;border-radius:8px">
    <button type="button" class="btn" id="btn-edit-rec-points">
      <?= htmlspecialchars($language['admin_recommendations']['edit_points'] ?? 'Modificar puntos') ?>
    </button>
    <button type="submit" class="btn" id="btn-save-rec-points" style="display:none">
      <?= htmlspecialchars($language['admin_recommendations']['save_points'] ?? 'Guardar puntos') ?>
    </button>
  </form>
</div>

<script>
(() => {
  const txt   = document.getElementById('rec-points-text');
  const input = document.getElementById('rec-points-input');
  const bEdit = document.getElementById('btn-edit-rec-points');
  const bSave = document.getElementById('btn-save-rec-points');
  const form  = document.getElementById('rec-points-form');

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

<?php
/* ===== CONFIG: IDs padre para Tema y Soporte =====
   Según tu mensaje: 10 (Tema), 11 (Soporte).
   Si cambian en el futuro, basta con actualizar estas constantes. */
$TOPIC_PARENT_ID   = 10; // Tema
$SUPPORT_PARENT_ID = 11; // Soporte

// Helper para leer hijos de un padre, incluyendo traducciones ES/EU
function fetchChildrenWithTrs(PDO $pdo, int $parentId): array {
  $sql = "
    SELECT
      c.id,
      c.status,
      c.created_at,
      es.name  AS name_es,
      es.slug  AS slug_es,
      eu.name  AS name_eu,
      eu.slug  AS slug_eu
    FROM dbo.category_relation cr
    JOIN dbo.category c           ON c.id = cr.id_child
    LEFT JOIN dbo.category_translation es
           ON es.id_category = c.id AND es.lang = 'es'
    LEFT JOIN dbo.category_translation eu
           ON eu.id_category = c.id AND eu.lang = 'eu'
    WHERE cr.id_parent = ?
      AND c.status = 'publicado'   -- << SOLO publicadas
    ORDER BY c.created_at DESC, c.id DESC
  ";
  $st = $pdo->prepare($sql);
  $st->execute([$parentId]);
  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$topics   = $pdo ? fetchChildrenWithTrs($pdo, $TOPIC_PARENT_ID)   : [];
$supports = $pdo ? fetchChildrenWithTrs($pdo, $SUPPORT_PARENT_ID) : [];
?>

<?php
// Devuelve "ES / EU" si hay ambos; si no, solo el que exista
function pair_label(?string $es, ?string $eu): string {
  $es = trim((string)$es);
  $eu = trim((string)$eu);
  if ($es !== '' && $eu !== '') return $es.' / '.$eu;
  return $es !== '' ? $es : $eu;
}

// ¿Hay contenido en ES?
function has_es(array $r): bool {
  return (isset($r['title_es'])   && trim((string)$r['title_es'])   !== '')
      || (isset($r['content_es']) && trim((string)$r['content_es']) !== '');
}

// ¿Hay contenido en EU?
function has_eu(array $r): bool {
  return (isset($r['title_eu'])   && trim((string)$r['title_eu'])   !== '')
      || (isset($r['content_eu']) && trim((string)$r['content_eu']) !== '');
}

function fmt_dt(?string $s): string {
  if (!$s) return '';
  try { return (new DateTime($s))->format('d/m/Y H:i'); }
  catch (Throwable $e) { return (string)$s; }
}

?>


<style>
  .admin-table { width:100%; border-collapse: collapse; margin:8px 0 20px }
  .admin-table th, .admin-table td { border:1px solid #ddd; padding:.55rem; text-align:left }
  .admin-table th { background:#f7f7f7 }
  .inline-form { display:flex; gap:8px; align-items:center; margin:8px 0 }
  .inline-form input[type="text"]{ padding:.4rem .55rem; border:1px solid #aaa; border-radius:8px }
  .muted{ color:#666; font-size:.92em }
  .subheader{ display:flex; align-items:center; justify-content:space-between; margin-top:20px }
</style>

<!-- ==================== TEMA ==================== -->
<div class="subheader">
  <h3 style="margin:0">Tema</h3>
  <div style="display:flex; gap:8px; align-items:center">
    <button type="button" class="btn" id="btn-toggle-topic" aria-expanded="false">Ver</button>
    <button type="button" class="btn" id="btn-add-topic">Añadir</button>
  </div>
</div>

<div id="topic-add-form" class="inline-form" style="display:none">
  <form method="post" action="">
    <input type="hidden" name="__action__" value="recommendations_add_category">
    <input type="hidden" name="parent_id" value="<?= (int)$TOPIC_PARENT_ID ?>">
    <input type="text" name="name_es" placeholder="Nombre (es)" required>
    <input type="text" name="name_eu" placeholder="Izena (eu)" required>
    <button type="submit" class="btn">Guardar</button>
    <button type="button" class="btn btn-red" data-cancel="#topic-add-form">Cancelar</button>
  </form>
</div>

<div id="topic-table-wrap" style="display:none">
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre (es)</th>
        <th>Nombre (eu)</th>
        <th>Estado</th>
        <th style="width:1%">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($topics)): ?>
        <tr><td colspan="7" class="muted">Sin elementos.</td></tr>
      <?php else: foreach ($topics as $row): ?>
        <tr>
          <td><?= (int)$row['id'] ?></td>
          <td><?= htmlspecialchars($row['name_es'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['name_eu'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['status'] ?? '') ?></td>
          <td>
            <form method="post" action=""
                  onsubmit="return confirm('¿Seguro que quieres eliminar este elemento?');"
                  style="margin:0">
              <input type="hidden" name="__action__" value="recommendations_soft_delete">
              <input type="hidden" name="category_id" value="<?= (int)$row['id'] ?>">
              <button type="submit" class="btn btn-red">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- ==================== SOPORTE ==================== -->
<div class="subheader">
  <h3 style="margin:0">Soporte</h3>
  <div style="display:flex; gap:8px; align-items:center">
    <button type="button" class="btn" id="btn-toggle-support" aria-expanded="false">Ver</button>
    <button type="button" class="btn" id="btn-add-support">Añadir</button>
  </div>
</div>

<div id="support-add-form" class="inline-form" style="display:none">
  <form method="post" action="">
    <input type="hidden" name="__action__" value="recommendations_add_category">
    <input type="hidden" name="parent_id" value="<?= (int)$SUPPORT_PARENT_ID ?>">
    <input type="text" name="name_es" placeholder="Nombre (es)" required>
    <input type="text" name="name_eu" placeholder="Izena (eu)" required>
    <button type="submit" class="btn">Guardar</button>
    <button type="button" class="btn btn-red" data-cancel="#support-add-form">Cancelar</button>
  </form>
</div>

<div id="support-table-wrap" style="display:none">
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre (es)</th>
        <th>Nombre (eu)</th>
        <th>Estado</th>
        <th style="width:1%">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($supports)): ?>
        <tr><td colspan="7" class="muted">Sin elementos.</td></tr>
      <?php else: foreach ($supports as $row): ?>
        <tr>
          <td><?= (int)$row['id'] ?></td>
          <td><?= htmlspecialchars($row['name_es'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['name_eu'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['status'] ?? '') ?></td>
          <td>
            <form method="post" action=""
                  onsubmit="return confirm('¿Seguro que quieres eliminar este elemento?');"
                  style="margin:0">
              <input type="hidden" name="__action__" value="recommendations_soft_delete">
              <input type="hidden" name="category_id" value="<?= (int)$row['id'] ?>">
              <button type="submit" class="btn btn-red">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
  const $ = (sel)=>document.querySelector(sel);
  const on = (el,evt,cb)=>el&&el.addEventListener(evt,cb);

  on($('#btn-add-topic'), 'click', ()=> { $('#topic-add-form').style.display='flex'; });
  on($('#btn-add-support'), 'click', ()=> { $('#support-add-form').style.display='flex'; });

  document.querySelectorAll('[data-cancel]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const box = document.querySelector(btn.getAttribute('data-cancel'));
      if (box) box.style.display='none';
      // limpiar inputs
      box.querySelectorAll('input[type="text"]').forEach(i=> i.value='');
    });
  });

  // Validación mínima: ambos idiomas obligatorios
  document.querySelectorAll('#topic-add-form form, #support-add-form form').forEach(form=>{
    form.addEventListener('submit', (e)=>{
      const es = form.querySelector('input[name="name_es"]')?.value.trim();
      const eu = form.querySelector('input[name="name_eu"]')?.value.trim();
      if (!es || !eu) {
        e.preventDefault();
        alert('Debes rellenar el nombre en Español y en Euskera.');
      }
    });
  });

  // Toggle Ver/Ocultar para Tema
  on($('#btn-toggle-topic'), 'click', () => {
    const wrap = $('#topic-table-wrap');
    const btn  = $('#btn-toggle-topic');
    if (!wrap || !btn) return;
    const isHidden = (wrap.style.display === 'none' || wrap.style.display === '');
    wrap.style.display = isHidden ? 'block' : 'none';
    btn.textContent = isHidden ? 'Ocultar' : 'Ver';
    btn.setAttribute('aria-expanded', String(isHidden));
  });

  // Toggle Ver/Ocultar para Soporte
  on($('#btn-toggle-support'), 'click', () => {
    const wrap = $('#support-table-wrap');
    const btn  = $('#btn-toggle-support');
    if (!wrap || !btn) return;
    const isHidden = (wrap.style.display === 'none' || wrap.style.display === '');
    wrap.style.display = isHidden ? 'block' : 'none';
    btn.textContent = isHidden ? 'Ocultar' : 'Ver';
    btn.setAttribute('aria-expanded', String(isHidden));
  });

  // Estado inicial: oculto y botones en "Ver"
  (function initTablesToggle() {
    const topic = $('#topic-table-wrap');
    const support = $('#support-table-wrap');
    if (topic) topic.style.display = 'none';
    if (support) support.style.display = 'none';
    const b1 = $('#btn-toggle-topic');
    const b2 = $('#btn-toggle-support');
    if (b1) { b1.textContent = 'Ver'; b1.setAttribute('aria-expanded', 'false'); }
    if (b2) { b2.textContent = 'Ver'; b2.setAttribute('aria-expanded', 'false'); }
  })();
</script>

<?php
/* ============================================================
   LISTADO DE RECOMENDACIONES (filtros + paginación + tabla)
   — PÉGALO AL FINAL DE community_recommendations.php —
   ============================================================ */

if (!isset($pdo) || !$pdo) {
  echo '<p>No hay conexión a la base de datos.</p>';
  return;
}

/* ---- Parámetros de filtros/paginación ---- */
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = min(100, max(5, (int)($_GET['per_page'] ?? 10)));
$order     = ($_GET['order'] ?? 'recent'); // recent | likes
$topicId   = (int)($_GET['topic'] ?? 0);   // categoría hija de Tema (id_parent=10)
$supportId = (int)($_GET['support'] ?? 0); // categoría hija de Soporte (id_parent=11)
$q         = trim((string)($_GET['q'] ?? ''));

$offset = ($page - 1) * $perPage;

/* ---- Usamos $topics y $supports que ya calculaste arriba ---- */
/* Si no existen (por si moviste el bloque), los recalculamos:  */
if (!isset($topics) || !is_array($topics))   $topics   = fetchChildrenWithTrs($pdo, $TOPIC_PARENT_ID);
if (!isset($supports) || !is_array($supports)) $supports = fetchChildrenWithTrs($pdo, $SUPPORT_PARENT_ID);

/* ---- WHERE dinámico (compartido por COUNT y SELECT) ---- */
$where  = ["r.id IS NOT NULL"];
$params = [];

/* filtrar por Tema */
if ($topicId > 0) {
  $where[] = "EXISTS (
      SELECT 1
        FROM dbo.category_link cl
        JOIN dbo.category_relation cr ON cr.id_child = cl.id_category
       WHERE cl.id_link = l.id
         AND cr.id_parent = ?
         AND cl.id_category = ?
  )";
  array_push($params, $TOPIC_PARENT_ID, $topicId);
}
/* filtrar por Soporte */
if ($supportId > 0) {
  $where[] = "EXISTS (
      SELECT 1
        FROM dbo.category_link cl
        JOIN dbo.category_relation cr ON cr.id_child = cl.id_category
       WHERE cl.id_link = l.id
         AND cr.id_parent = ?
         AND cl.id_category = ?
  )";
  array_push($params, $SUPPORT_PARENT_ID, $supportId);
}
/* buscador */
if ($q !== '') {
  $where[] = "(
      es.title LIKE ? OR eu.title LIKE ?
   OR es.[content] LIKE ? OR eu.[content] LIKE ?
   OR r.author LIKE ?
   OR u.usuario LIKE ?
  )";
  $like = '%'.$q.'%';
  array_push($params, $like,$like,$like,$like,$like,$like);
}

/* ---- ORDER BY ---- */
$orderSql = ($order === 'likes')
  ? "ORDER BY r.likes DESC, r.created_at DESC"
  : "ORDER BY r.created_at DESC, r.id DESC";

/* ---- TOTAL ---- */
$sqlCount = "
  ;WITH base AS (
    SELECT DISTINCT r.id
      FROM dbo.recommendation r
      JOIN dbo.[link] l ON l.id_recommendation = r.id
      LEFT JOIN dbo.translation es ON es.id_link = l.id AND es.lang='es'
      LEFT JOIN dbo.translation eu ON eu.id_link = l.id AND eu.lang='eu'
      OUTER APPLY (
        SELECT TOP (1) u.*
          FROM dbo.point p
          JOIN dbo.user_activity ua ON ua.id_point = p.id
          JOIN dbo.[user] u        ON u.id = ua.id_user
         WHERE p.id_link = l.id
         ORDER BY ua.id DESC
      ) u
     WHERE ".implode(' AND ', $where)."
  )
  SELECT COUNT(*) FROM base";
try {
  $st = $pdo->prepare($sqlCount);
  $st->execute($params);
  $total = (int)$st->fetchColumn();
} catch (Throwable $e) {
  echo '<pre style="color:#b00;background:#fee;padding:8px;border:1px solid #f99">'
     . 'ERROR (count): ' . htmlspecialchars($e->getMessage()) . '</pre>';
  $total = 0;
}

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

/* ---- SELECT paginado ---- */
$sql = "
  SELECT
      r.id             AS rec_id,
      r.author         AS rec_author,
      r.likes          AS rec_likes,
      r.created_at     AS rec_created_at,
      r.status         AS rec_status,
      l.id             AS link_id,

      es.title         AS title_es,
      eu.title         AS title_eu,
      es.[content]     AS content_es,
      eu.[content]     AS content_eu,

      t_es.name        AS topic_es,
      t_eu.name        AS topic_eu,
      s_es.name        AS support_es,
      s_eu.name        AS support_eu,

      u.usuario        AS username
  FROM dbo.recommendation r
  JOIN dbo.[link] l
    ON l.id_recommendation = r.id
  LEFT JOIN dbo.translation es
    ON es.id_link = l.id AND es.lang='es'
  LEFT JOIN dbo.translation eu
    ON eu.id_link = l.id AND eu.lang='eu'

  /* Tema (ES/EU) */
  OUTER APPLY (
    SELECT TOP (1) ct.*
      FROM dbo.category_link cl
      JOIN dbo.category_relation cr ON cr.id_child = cl.id_category AND cr.id_parent = ?
      JOIN dbo.category_translation ct ON ct.id_category = cl.id_category AND ct.lang='es'
     WHERE cl.id_link = l.id
     ORDER BY ct.name ASC, ct.id_category ASC
  ) t_es
  OUTER APPLY (
    SELECT TOP (1) ct.*
      FROM dbo.category_link cl
      JOIN dbo.category_relation cr ON cr.id_child = cl.id_category AND cr.id_parent = ?
      JOIN dbo.category_translation ct ON ct.id_category = cl.id_category AND ct.lang='eu'
     WHERE cl.id_link = l.id
     ORDER BY ct.name ASC, ct.id_category ASC
  ) t_eu

  /* Soporte (ES/EU) */
  OUTER APPLY (
    SELECT TOP (1) ct.*
      FROM dbo.category_link cl
      JOIN dbo.category_relation cr ON cr.id_child = cl.id_category AND cr.id_parent = ?
      JOIN dbo.category_translation ct ON ct.id_category = cl.id_category AND ct.lang='es'
     WHERE cl.id_link = l.id
     ORDER BY ct.name ASC, ct.id_category ASC
  ) s_es
  OUTER APPLY (
    SELECT TOP (1) ct.*
      FROM dbo.category_link cl
      JOIN dbo.category_relation cr ON cr.id_child = cl.id_category AND cr.id_parent = ?
      JOIN dbo.category_translation ct ON ct.id_category = cl.id_category AND ct.lang='eu'
     WHERE cl.id_link = l.id
     ORDER BY ct.name ASC, ct.id_category ASC
  ) s_eu

  /* Usuario (quien registró la actividad del point) */
  OUTER APPLY (
    SELECT TOP (1) u.*
      FROM dbo.point p
      JOIN dbo.user_activity ua ON ua.id_point = p.id
      JOIN dbo.[user] u        ON u.id = ua.id_user
     WHERE p.id_link = l.id
     ORDER BY ua.id DESC
  ) u

  WHERE ".implode(' AND ', $where)."
  $orderSql
  OFFSET CAST(? AS INT) ROWS FETCH NEXT CAST(? AS INT) ROWS ONLY";

$paramsPage = array_merge(
  [$TOPIC_PARENT_ID, $TOPIC_PARENT_ID, $SUPPORT_PARENT_ID, $SUPPORT_PARENT_ID],
  $params,
  [$offset, $perPage]
);
try {
  $st = $pdo->prepare($sql);
  $st->execute($paramsPage);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  echo '<pre style="color:#b00;background:#fee;padding:8px;border:1px solid #f99">'
     . 'ERROR (select): ' . htmlspecialchars($e->getMessage()) . '</pre>';
  $rows = [];
}

?>

<?php
$__baseUrlPath = (function () {
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  if ($uri === '' || $uri === null) { $uri = $_SERVER['PHP_SELF'] ?? ''; }
  $path = parse_url($uri, PHP_URL_PATH);
  return $path ?: '';
})();
?>

<?php
// ==== Endpoint AJAX: devuelve solo tbody + pager como JSON ==== //
if (($_GET['ajax'] ?? '') === 'reclist') {
  ob_start();
  if (empty($rows)) {
    echo '<tr><td colspan="11" class="reclist-muted">No hay recomendaciones que cumplan los criterios.</td></tr>';
  } else {
    foreach ($rows as $r) {
      $topicLbl   = htmlspecialchars(pair_label($r['topic_es'] ?? '',   $r['topic_eu'] ?? ''), ENT_QUOTES);
      $supportLbl = htmlspecialchars(pair_label($r['support_es'] ?? '', $r['support_eu'] ?? ''), ENT_QUOTES);

      // Fila ES solo si hay datos en ES
      if (has_es($r)) {
        $statusRaw = strtolower((string)($r['rec_status'] ?? 'publicado'));
        $statusTxt = ($statusRaw === 'borrador') ? 'borrador' : 'publicado';
        $toggleLabel = ($statusTxt === 'publicado') ? 'Borrador' : 'Publicar';

        echo '<tr>';
        echo '<td class="small">'.$topicLbl.'</td>';
        echo '<td class="small">'.$supportLbl.'</td>';
        echo '<td>es</td>';
        echo '<td>'.htmlspecialchars($r['title_es'] ?? '', ENT_QUOTES).'</td>';
        echo '<td>'.nl2br(htmlspecialchars($r['content_es'] ?? '', ENT_QUOTES)).'</td>';
        echo '<td>'.htmlspecialchars($r['rec_author'] ?? '', ENT_QUOTES).'</td>';
        echo '<td>'.htmlspecialchars($r['username'] ?? '', ENT_QUOTES).'</td>';
        echo '<td class="nowrap">'.(int)($r['rec_likes'] ?? 0).'</td>';
        echo '<td class="nowrap">'.htmlspecialchars(fmt_dt($r['rec_created_at'] ?? ''), ENT_QUOTES).'</td>';

        // NUEVA celda: ESTADO
        echo '<td class="nowrap">'.htmlspecialchars($statusTxt, ENT_QUOTES).'</td>';

        // Celda: ACCIONES
        echo '<td class="nowrap">';
        echo '  <form method="post" action="" class="rec-action" style="display:inline">';
        echo '    <input type="hidden" name="__action__" value="recommendations_toggle_status">';
        echo '    <input type="hidden" name="rec_id" value="'.(int)$r['rec_id'].'">';
        echo '    <button type="submit" class="btn">'.htmlspecialchars($toggleLabel, ENT_QUOTES).'</button>';
        echo '  </form>';
        echo '  <form method="post" action="" class="rec-action" style="display:inline" onsubmit="return confirm(\'¿Seguro que quieres eliminar esta recomendación y sus relaciones?\');">';
        echo '    <input type="hidden" name="__action__" value="recommendations_hard_delete">';
        echo '    <input type="hidden" name="rec_id" value="'.(int)$r['rec_id'].'">';
        echo '    <button type="submit" class="btn btn-red">Eliminar</button>';
        echo '  </form>';
        echo '</td>';
        echo '</tr>';
      }

      // Fila EU solo si hay datos en EU
      if (has_eu($r)) {
        $statusRaw = strtolower((string)($r['rec_status'] ?? 'publicado'));
        $statusTxt = ($statusRaw === 'borrador') ? 'borrador' : 'publicado';
        $toggleLabel = ($statusTxt === 'publicado') ? 'Borrador' : 'Publicar';

        echo '<tr>';
        echo '<td class="small">'.$topicLbl.'</td>';
        echo '<td class="small">'.$supportLbl.'</td>';
        echo '<td>eu</td>';
        echo '<td>'.htmlspecialchars($r['title_eu'] ?? '', ENT_QUOTES).'</td>';
        echo '<td>'.nl2br(htmlspecialchars($r['content_eu'] ?? '', ENT_QUOTES)).'</td>';
        echo '<td>'.htmlspecialchars($r['rec_author'] ?? '', ENT_QUOTES).'</td>';
        echo '<td>'.htmlspecialchars($r['username'] ?? '', ENT_QUOTES).'</td>';
        echo '<td class="nowrap">'.(int)($r['rec_likes'] ?? 0).'</td>';
        echo '<td class="nowrap">'.htmlspecialchars(fmt_dt($r['rec_created_at'] ?? ''), ENT_QUOTES).'</td>';

        // NUEVA celda: ESTADO
        echo '<td class="nowrap">'.htmlspecialchars($statusTxt, ENT_QUOTES).'</td>';

        // Celda: ACCIONES
        echo '<td class="nowrap">';
        echo '  <form method="post" action="" class="rec-action" style="display:inline">';
        echo '    <input type="hidden" name="__action__" value="recommendations_toggle_status">';
        echo '    <input type="hidden" name="rec_id" value="'.(int)$r['rec_id'].'">';
        echo '    <button type="submit" class="btn">'.htmlspecialchars($toggleLabel, ENT_QUOTES).'</button>';
        echo '  </form>';
        echo '  <form method="post" action="" class="rec-action" style="display:inline" onsubmit="return confirm(\'¿Seguro que quieres eliminar esta recomendación y sus relaciones?\');">';
        echo '    <input type="hidden" name="__action__" value="recommendations_hard_delete">';
        echo '    <input type="hidden" name="rec_id" value="'.(int)$r['rec_id'].'">';
        echo '    <button type="submit" class="btn btn-red">Eliminar</button>';
        echo '  </form>';
        echo '</td>';
        echo '</tr>';
      }

    }
  }
  $tbodyHtml = ob_get_clean();

  // Render del pager a string (mismo que abajo, pero sin <div>)
  ob_start();
  ?>
  <span>Total: <?= (int)$total ?> (pág. <?= (int)$page ?> / <?= (int)$totalPages ?>)</span>
  <?php
    $build = function (int $p) use ($__baseUrlPath) {
      $qs = $_GET; $qs['page'] = $p; unset($qs['ajax']);
      return htmlspecialchars($__baseUrlPath . '?' . http_build_query($qs));
    };
  ?>
  <a href="<?= $build(max(1, $page-1)) ?>" data-page="<?= max(1, $page-1) ?>" <?= $page<=1?'aria-disabled="true"':'' ?>>Anterior</a>
  <?php
    $win = 2;
    $start = max(1, $page-$win);
    $end   = min($totalPages, $page+$win);
    if ($start > 1) echo '<a href="'.$build(1).'" data-page="1">1</a><span>…</span>';
    for ($p=$start; $p<=$end; $p++) {
      if ($p===$page) echo '<span class="active">'.$p.'</span>';
      else echo '<a href="'.$build($p).'" data-page="'.$p.'">'.$p.'</a>';
    }
    if ($end < $totalPages) echo '<span>…</span><a href="'.$build($totalPages).'" data-page="'.$totalPages.'">'.$totalPages.'</a>';
  ?>
  <a href="<?= $build(min($totalPages, $page+1)) ?>" data-page="<?= min($totalPages, $page+1) ?>" <?= $page>=$totalPages?'aria-disabled="true"':'' ?>>Siguiente</a>
  <?php
  $pagerHtml = ob_get_clean();

  while (ob_get_level() > 0) { ob_end_clean(); }

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['tbody'=>$tbodyHtml, 'pager'=>$pagerHtml, 'page'=>$page, 'totalPages'=>$totalPages, 'total'=>$total]);
  exit;
}
?>

<style>
.reclist-wrap{margin:24px 0}
.reclist-filters{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:14px 0}
.reclist-filters select,.reclist-filters input[type="text"]{padding:.45rem .6rem;border:1px solid #bbb;border-radius:8px}
.reclist-table{width:100%;border-collapse:collapse}
.reclist-table th,.reclist-table td{border:1px solid #e2e2e2;padding:.55rem;vertical-align:top;text-align:left}
.reclist-table th{background:#f8f8f8}
.reclist-table td .btn { margin: 0 4px 0 0; }
.reclist-muted{color:#666}
.pager{display:flex;gap:8px;align-items:center;justify-content:flex-end;margin-top:10px}
.pager a,.pager span{padding:.38rem .7rem;border:1px solid #ccc;border-radius:8px;text-decoration:none}
.pager .active{background:#eee;font-weight:600}
.nowrap{white-space:nowrap}
.small{font-size:.92em}
</style>

<hr>
<h3 style="margin:12px 0">Listado de recomendaciones</h3>


<form id="reclist-filters" class="reclist-filters" onsubmit="return false;">
  <label>
    Tema:
    <select name="topic" id="flt-topic">
      <option value="0">Todos</option>
      <?php foreach ($topics as $t): ?>
        <option value="<?= (int)$t['id'] ?>" <?= ((int)($_GET['topic']??0)===(int)$t['id'])?'selected':'' ?>>
          <?= htmlspecialchars(($t['name_es']?:'').' / '.($t['name_eu']?:'')) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>
    Soporte:
    <select name="support" id="flt-support">
      <option value="0">Todos</option>
      <?php foreach ($supports as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= ((int)($_GET['support']??0)===(int)$s['id'])?'selected':'' ?>>
          <?= htmlspecialchars(($s['name_es']?:'').' / '.($s['name_eu']?:'')) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>
    Orden:
    <select name="order" id="flt-order">
      <option value="recent" <?= (($_GET['order']??'recent')==='recent')?'selected':'' ?>>Más recientes</option>
      <option value="likes"  <?= (($_GET['order']??'recent')==='likes')?'selected':''  ?>>Más likes</option>
    </select>
  </label>

  <label>
    Por página:
    <select name="per_page" id="flt-perpage">
      <?php foreach ([10,20,30,50,100] as $n): ?>
        <option value="<?= $n ?>" <?= ((int)($_GET['per_page']??10)===$n)?'selected':'' ?>><?= $n ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <input type="text" name="q" id="flt-q" style="min-width:260px"
         placeholder="Buscar (título, contenido, autor o usuario)"
         value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
</form>



<table class="reclist-table">
  <thead>
    <tr>
      <th class="nowrap">Tema (es/eu)</th>
      <th class="nowrap">Soporte (es/eu)</th>
      <th class="nowrap">Idioma</th>
      <th>Título</th>
      <th>Contenido</th>
      <th>Autor</th>
      <th class="nowrap">Usuario</th>
      <th class="nowrap">Likes</th>
      <th class="nowrap">Creación</th>
      <th class="nowrap">Estado</th>
      <th style="width:1%">Acciones</th>
    </tr>
  </thead>
  <tbody id="reclist-body">
    <?php if (empty($rows)): ?>
      <tr><td colspan="9" class="reclist-muted">No hay recomendaciones que cumplan los criterios.</td></tr>
    <?php else: foreach ($rows as $r):
      $topicLbl   = htmlspecialchars(pair_label($r['topic_es'] ?? '',   $r['topic_eu'] ?? ''), ENT_QUOTES);
      $supportLbl = htmlspecialchars(pair_label($r['support_es'] ?? '', $r['support_eu'] ?? ''), ENT_QUOTES);
    ?>

      <?php if (has_es($r)): 
        $statusRaw = strtolower((string)($r['rec_status'] ?? 'publicado'));
        $statusTxt = ($statusRaw === 'borrador') ? 'borrador' : 'publicado';
        $toggleLabel = ($statusTxt === 'publicado') ? 'Borrador' : 'Publicar';
      ?>
        <tr>
          <td class="small"><?= $topicLbl ?></td>
          <td class="small"><?= $supportLbl ?></td>
          <td>es</td>
          <td><?= htmlspecialchars($r['title_es'] ?? '', ENT_QUOTES) ?></td>
          <td><?= nl2br(htmlspecialchars($r['content_es'] ?? '', ENT_QUOTES)) ?></td>
          <td><?= htmlspecialchars($r['rec_author'] ?? '', ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($r['username'] ?? '', ENT_QUOTES) ?></td>
          <td class="nowrap"><?= (int)($r['rec_likes'] ?? 0) ?></td>
          <td class="nowrap"><?= htmlspecialchars(fmt_dt($r['rec_created_at'] ?? ''), ENT_QUOTES) ?></td>

          <!-- NUEVA celda: ESTADO -->
          <td class="nowrap"><?= htmlspecialchars($statusTxt, ENT_QUOTES) ?></td>

          <!-- Celda: ACCIONES -->
          <td class="nowrap">
            <form method="post" action="" class="rec-action" style="display:inline">
              <input type="hidden" name="__action__" value="recommendations_toggle_status">
              <input type="hidden" name="rec_id" value="<?= (int)$r['rec_id'] ?>">
              <button type="submit" class="btn"><?= $toggleLabel ?></button>
            </form>
            <form method="post" action="" class="rec-action" style="display:inline"
                  onsubmit="return confirm('¿Seguro que quieres eliminar esta recomendación y sus relaciones?');">
              <input type="hidden" name="__action__" value="recommendations_hard_delete">
              <input type="hidden" name="rec_id" value="<?= (int)$r['rec_id'] ?>">
              <button type="submit" class="btn btn-red">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endif; ?>


      <?php if (has_eu($r)): 
        $statusRaw = strtolower((string)($r['rec_status'] ?? 'publicado'));
        $statusTxt = ($statusRaw === 'borrador') ? 'borrador' : 'publicado';
        $toggleLabel = ($statusTxt === 'publicado') ? 'Borrador' : 'Publicar';
      ?>
        <tr>
          <td class="small"><?= $topicLbl ?></td>
          <td class="small"><?= $supportLbl ?></td>
          <td>eu</td>
          <td><?= htmlspecialchars($r['title_eu'] ?? '', ENT_QUOTES) ?></td>
          <td><?= nl2br(htmlspecialchars($r['content_eu'] ?? '', ENT_QUOTES)) ?></td>
          <td><?= htmlspecialchars($r['rec_author'] ?? '', ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($r['username'] ?? '', ENT_QUOTES) ?></td>
          <td class="nowrap"><?= (int)($r['rec_likes'] ?? 0) ?></td>
          <td class="nowrap"><?= htmlspecialchars(fmt_dt($r['rec_created_at'] ?? ''), ENT_QUOTES) ?></td>

          <!-- NUEVA celda: ESTADO -->
          <td class="nowrap"><?= htmlspecialchars($statusTxt, ENT_QUOTES) ?></td>

          <!-- Celda: ACCIONES -->
          <td class="nowrap">
            <form method="post" action="" class="rec-action" style="display:inline">
              <input type="hidden" name="__action__" value="recommendations_toggle_status">
              <input type="hidden" name="rec_id" value="<?= (int)$r['rec_id'] ?>">
              <button type="submit" class="btn"><?= $toggleLabel ?></button>
            </form>
            <form method="post" action="" class="rec-action" style="display:inline"
                  onsubmit="return confirm('¿Seguro que quieres eliminar esta recomendación y sus relaciones?');">
              <input type="hidden" name="__action__" value="recommendations_hard_delete">
              <input type="hidden" name="rec_id" value="<?= (int)$r['rec_id'] ?>">
              <button type="submit" class="btn btn-red">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endif; ?>


    <?php endforeach; endif; ?>
  </tbody>
</table>



<?php
/* ---- paginador compacto ---- */
$build = function (int $p) use ($__baseUrlPath) {
  $qs = $_GET; $qs['page'] = $p;
  return htmlspecialchars($__baseUrlPath . '?' . http_build_query($qs));
};
?>
<div class="pager" id="reclist-pager">
  <span>Total: <?= (int)$total ?> (pág. <?= (int)$page ?> / <?= (int)$totalPages ?>)</span>

  <a href="<?= $build(max(1, $page-1)) ?>"
     data-page="<?= max(1, $page-1) ?>"
     <?= $page<=1?'aria-disabled="true"':'' ?>>Anterior</a>

  <?php
    $win = 2;
    $start = max(1, $page-$win);
    $end   = min($totalPages, $page+$win);
    if ($start > 1) {
      echo '<a href="'.$build(1).'" data-page="1">1</a><span>…</span>';
    }
    for ($p=$start; $p<=$end; $p++) {
      if ($p===$page) {
        echo '<span class="active">'.$p.'</span>';
      } else {
        echo '<a href="'.$build($p).'" data-page="'.$p.'">'.$p.'</a>';
      }
    }
    if ($end < $totalPages) {
      echo '<span>…</span><a href="'.$build($totalPages).'" data-page="'.$totalPages.'">'.$totalPages.'</a>';
    }
  ?>

  <a href="<?= $build(min($totalPages, $page+1)) ?>"
     data-page="<?= min($totalPages, $page+1) ?>"
     <?= $page>=$totalPages?'aria-disabled="true"':'' ?>>Siguiente</a>
</div>

<script>
(function () {
  const basePath = window.location.pathname;

  const topic    = document.getElementById('flt-topic');
  const support  = document.getElementById('flt-support');
  const order    = document.getElementById('flt-order');
  const perpage  = document.getElementById('flt-perpage');
  const q        = document.getElementById('flt-q');
  const tbody    = document.getElementById('reclist-body');
  const pager    = document.getElementById('reclist-pager');

  if (!topic || !support || !order || !perpage || !q || !tbody || !pager) {
    console.warn('Filtros/tabla/paginador no presentes todavía');
    return;
  }

  const urlWithParams = (page='1') => {
    const params = new URLSearchParams(window.location.search);
    params.set('topic',   topic.value || '0');
    params.set('support', support.value || '0');
    params.set('order',   order.value  || 'recent');
    params.set('per_page',perpage.value|| '10');
    const val = q.value.trim();
    if (val) params.set('q', val); else params.delete('q');
    params.set('page', page);
    params.set('ajax', 'reclist');
    params.set('__t', Date.now().toString());
    return basePath + '?' + params.toString();
  };

  const updateUrlBar = (page) => {
    const params = new URLSearchParams(window.location.search);
    params.set('topic',   topic.value || '0');
    params.set('support', support.value || '0');
    params.set('order',   order.value  || 'recent');
    params.set('per_page',perpage.value|| '10');
    const val = q.value.trim();
    if (val) params.set('q', val); else params.delete('q');
    params.set('page', page);
    history.replaceState(null, '', basePath + '?' + params.toString());
  };

  const loadPage = async (page='1') => {
    try {
      const res = await fetch(urlWithParams(page), {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const ct = (res.headers.get('content-type') || '').toLowerCase();
      if (!ct.includes('application/json')) {
        const text = await res.text();
        console.error('Respuesta no JSON:', text);
        return;
      }
      const data = await res.json();
      tbody.innerHTML = data.tbody;
      pager.innerHTML = data.pager;
      updateUrlBar(String(data.page));
    } catch (e) {
      console.error(e);
    }
  };

  const go = () => loadPage('1');
  topic.addEventListener('change', go);
  support.addEventListener('change', go);
  order.addEventListener('change', go);
  perpage.addEventListener('change', go);

  let t = null;
  q.addEventListener('input', () => { clearTimeout(t); t = setTimeout(go, 250); });

  pager.addEventListener('click', (ev) => {
    const a = ev.target.closest('a');
    if (!a) return;
    ev.preventDefault();
    if (a.getAttribute('aria-disabled') === 'true') return;
    const p = a.getAttribute('data-page') || '1';
    loadPage(p);
  });

  // ===== interceptar formularios de acción y recargar manteniendo filtros/página =====
  const currPage = () => (new URLSearchParams(window.location.search).get('page') || '1');

  tbody.addEventListener('submit', async (ev) => {
    const form = ev.target.closest('form.rec-action');
    if (!form) return;

    // Si el onsubmit="return confirm(...)" canceló, no hagas nada
    if (ev.defaultPrevented) return;

    ev.preventDefault();

    try {
      const res = await fetch(basePath, {
        method: 'POST',
        body: new FormData(form),
        headers: {'X-Requested-With': 'XMLHttpRequest'}
      });

      // Si hubo redirección, asumimos que no es JSON (algún guard o error general)
      if (res.redirected) {
        console.warn('Respuesta redirigida a:', res.url);
        alert('Error realizando la acción.');
        return;
      }

      const text = await res.text();
      let data = null;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error('Respuesta no JSON (Eliminar):', text);
        alert('Error realizando la acción.');
        return;
      }

      if (!data || !data.ok) {
        console.error('Payload error:', data);
        alert(data && data.error ? data.error : 'Error realizando la acción.');
        return;
      }

      // OK -> recarga la tabla manteniendo filtros y página
      await loadPage(currPage());
    } catch (e) {
      console.error(e);
      alert('Error realizando la acción.');
    }
  });
  // ===== FIN =====

})();
</script>
