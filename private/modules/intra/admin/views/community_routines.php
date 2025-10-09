<?php
global $language;
$checked = !empty($moduleFlags['routines']);

$pdo = $GLOBALS['pdo'] ?? null;

// --- Leer puntos vigentes del módulo 'routine' (singular en DB) ---
$routinePoints = 0;
try {
  if ($pdo) {
    $st = $pdo->prepare("
      SELECT TOP (1) points
      FROM dbo.point_modules
      WHERE module_code = 'routine' AND effective_to IS NULL
      ORDER BY id DESC
    ");
    $st->execute();
    $routinePoints = (int)($st->fetchColumn() ?? 0);
  }
} catch (Throwable $e) { /* opcional: log */ }
?>

<h2><?= htmlspecialchars($language['modules']['routines'] ?? 'Rutinas') ?></h2>

<form method="post" action="" class="mod-toggle">
  <input type="hidden" name="__action__" value="toggle_module">
  <input type="hidden" name="module_key" value="routines">
  <input type="hidden" name="redirect"   value="community/routines">
  <label class="toggle">
    <input type="checkbox" name="visible" value="1" <?= $checked ? 'checked' : '' ?>>
    <?= htmlspecialchars($language['admin_toggle']['label'] ?? 'Mostrar este módulo') ?>
  </label>
  <button class="btn btn-red" type="submit"><?= htmlspecialchars($language['admin_toggle']['save'] ?? 'Guardar') ?></button>
</form>

<!-- === PUNTOS DEL MÓDULO ROUTINE === -->
<div id="routine-points-box" style="display:flex;gap:10px;align-items:center;margin:10px 0 18px">
  <div>
    <span><?= htmlspecialchars($language['admin_routines']['points_label'] ?? 'Puntos por actividad del módulo:') ?></span>
    <strong id="routine-points-text"><?= (int)$routinePoints ?></strong>
  </div>

  <form method="post" action="" id="routine-points-form" style="display:inline-flex;gap:8px;align-items:center">
    <input type="hidden" name="__action__" value="routines_update_points">
    <input type="hidden" name="module_key" value="routines"> <!-- plural en UI -->
    <input type="number" name="points" id="routine-points-input"
           value="<?= (int)$routinePoints ?>" min="0" step="1"
           style="width:90px;display:none;padding:.35rem;border:1px solid #0aa;border-radius:8px">
    <button type="button" class="btn" id="btn-edit-routine-points">
      <?= htmlspecialchars($language['admin_routines']['edit_points'] ?? 'Modificar puntos') ?>
    </button>
    <button type="submit" class="btn" id="btn-save-routine-points" style="display:none">
      <?= htmlspecialchars($language['admin_routines']['save_points'] ?? 'Guardar puntos') ?>
    </button>
  </form>
</div>

<script>
(() => {
  const txt   = document.getElementById('routine-points-text');
  const input = document.getElementById('routine-points-input');
  const bEdit = document.getElementById('btn-edit-routine-points');
  const bSave = document.getElementById('btn-save-routine-points');
  const form  = document.getElementById('routine-points-form');

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
// ====== IDs padre ======
$INDOOR_PARENT_ID  = 26;
$OUTDOOR_PARENT_ID = 27;

// Leer hijos con traducciones (solo publicadas)
function fetchRoutineChildren(PDO $pdo, int $parentId): array {
  $sql = "
    SELECT
      c.id, c.status, c.created_at,
      es.name AS name_es, eu.name AS name_eu
    FROM dbo.category_relation cr
    JOIN dbo.category c ON c.id = cr.id_child
    LEFT JOIN dbo.category_translation es
           ON es.id_category = c.id AND es.lang = 'es'
    LEFT JOIN dbo.category_translation eu
           ON eu.id_category = c.id AND eu.lang = 'eu'
    WHERE cr.id_parent = ?
      AND c.status = N'publicado'
    ORDER BY COALESCE(es.name, eu.name) ASC, c.id ASC
  ";
  $st = $pdo->prepare($sql);
  $st->execute([$parentId]);
  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$indoorRows  = $pdo ? fetchRoutineChildren($pdo, $INDOOR_PARENT_ID)  : [];
$outdoorRows = $pdo ? fetchRoutineChildren($pdo, $OUTDOOR_PARENT_ID) : [];
?>

<style>
  .admin-table { width:100%; border-collapse: collapse; margin:8px 0 20px }
  .admin-table th, .admin-table td { border:1px solid #ddd; padding:.55rem; text-align:left }
  .admin-table th { background:#f7f7f7 }
  .subheader{ display:flex; align-items:center; justify-content:space-between; margin-top:20px }
  .muted{ color:#666; font-size:.92em }
  .inline-form { display:flex; gap:8px; align-items:center; margin:8px 0 }
  .inline-form input[type="text"]{ padding:.4rem .55rem; border:1px solid #aaa; border-radius:8px }
</style>

<!-- ============== INDOOR ============== -->
<div class="subheader">
  <h3 style="margin:0">Indoor</h3>
  <div style="display:flex; gap:8px; align-items:center">
    <button type="button" class="btn" id="btn-toggle-indoor" aria-expanded="false">Ver</button>
    <button type="button" class="btn" id="btn-add-indoor">Añadir</button>
  </div>
</div>

<div id="indoor-add-form" class="inline-form" style="display:none">
  <form method="post" action="">
    <input type="hidden" name="__action__" value="routines_add_category">
    <input type="hidden" name="parent_id" value="<?= (int)$INDOOR_PARENT_ID ?>">
    <input type="text" name="name_es" placeholder="Nombre (es)" required>
    <input type="text" name="name_eu" placeholder="Izena (eu)" required>
    <button type="submit" class="btn">Guardar</button>
    <button type="button" class="btn btn-red" data-cancel="#indoor-add-form">Cancelar</button>
  </form>
</div>

<div id="indoor-table-wrap" style="display:none">
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre (es)</th>
        <th>Nombre (eu)</th>
        <th>Status</th>
        <th style="width:1%">Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$indoorRows): ?>
      <tr><td colspan="6" class="muted">Sin elementos.</td></tr>
    <?php else: foreach ($indoorRows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['name_es'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['name_eu'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
        <td>
          <form method="post" action="" style="margin:0"
                onsubmit="return confirm('¿Seguro que quieres eliminar este elemento?');">
            <input type="hidden" name="__action__" value="routines_soft_delete">
            <input type="hidden" name="category_id" value="<?= (int)$r['id'] ?>">
            <button type="submit" class="btn btn-red">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- ============== OUTDOOR ============== -->
<div class="subheader">
  <h3 style="margin:0">Outdoor</h3>
  <div style="display:flex; gap:8px; align-items:center">
    <button type="button" class="btn" id="btn-toggle-outdoor" aria-expanded="false">Ver</button>
    <button type="button" class="btn" id="btn-add-outdoor">Añadir</button>
  </div>
</div>

<div id="outdoor-add-form" class="inline-form" style="display:none">
  <form method="post" action="">
    <input type="hidden" name="__action__" value="routines_add_category">
    <input type="hidden" name="parent_id" value="<?= (int)$OUTDOOR_PARENT_ID ?>">
    <input type="text" name="name_es" placeholder="Nombre (es)" required>
    <input type="text" name="name_eu" placeholder="Izena (eu)" required>
    <button type="submit" class="btn">Guardar</button>
    <button type="button" class="btn btn-red" data-cancel="#outdoor-add-form">Cancelar</button>
  </form>
</div>

<div id="outdoor-table-wrap" style="display:none">
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre (es)</th>
        <th>Nombre (eu)</th>
        <th>Status</th>
        <th style="width:1%">Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$outdoorRows): ?>
      <tr><td colspan="6" class="muted">Sin elementos.</td></tr>
    <?php else: foreach ($outdoorRows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['name_es'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['name_eu'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
        <td>
          <form method="post" action="" style="margin:0"
                onsubmit="return confirm('¿Seguro que quieres eliminar este elemento?');">
            <input type="hidden" name="__action__" value="routines_soft_delete">
            <input type="hidden" name="category_id" value="<?= (int)$r['id'] ?>">
            <button type="submit" class="btn btn-red">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const $id = (x) => document.getElementById(x);

  const indoorBtn = $id('btn-add-indoor');
  const indoorBox = $id('indoor-add-form');
  const outdoorBtn = $id('btn-add-outdoor');
  const outdoorBox = $id('outdoor-add-form');

  // Mostrar formularios
  if (indoorBtn && indoorBox) {
    indoorBtn.addEventListener('click', () => {
      indoorBox.style.display = indoorBox.style.display === 'none' || !indoorBox.style.display ? 'flex' : 'none';
    });
  } else {
    console.warn('No se encontró btn-add-indoor o indoor-add-form');
  }

  if (outdoorBtn && outdoorBox) {
    outdoorBtn.addEventListener('click', () => {
      outdoorBox.style.display = outdoorBox.style.display === 'none' || !outdoorBox.style.display ? 'flex' : 'none';
    });
  } else {
    console.warn('No se encontró btn-add-outdoor o outdoor-add-form');
  }

  // Botones Cancelar (cierran y limpian)
  document.querySelectorAll('[data-cancel]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const sel = btn.getAttribute('data-cancel');
      const box = document.querySelector(sel);
      if (box) {
        box.style.display = 'none';
        box.querySelectorAll('input[type="text"]').forEach((i) => (i.value = ''));
      }
    });
  });

  // Validación: ambos idiomas obligatorios
  document.querySelectorAll('#indoor-add-form form, #outdoor-add-form form').forEach((form) => {
    form.addEventListener('submit', (e) => {
      const es = form.querySelector('input[name="name_es"]')?.value.trim();
      const eu = form.querySelector('input[name="name_eu"]')?.value.trim();
      if (!es || !eu) {
        e.preventDefault();
        alert('Debes rellenar el nombre en Español y en Euskera.');
      }
    });
  });

    // --- Toggle Ver/Ocultar INDOOR ---
  const btnToggleIndoor = $id('btn-toggle-indoor');
  const indoorWrap = $id('indoor-table-wrap');
  if (btnToggleIndoor && indoorWrap) {
    btnToggleIndoor.addEventListener('click', () => {
      const hidden = indoorWrap.style.display === 'none' || !indoorWrap.style.display;
      indoorWrap.style.display = hidden ? 'block' : 'none';
      btnToggleIndoor.textContent = hidden ? 'Ocultar' : 'Ver';
      btnToggleIndoor.setAttribute('aria-expanded', String(hidden));
    });
    // Estado inicial
    indoorWrap.style.display = 'none';
    btnToggleIndoor.textContent = 'Ver';
    btnToggleIndoor.setAttribute('aria-expanded', 'false');
  }

  // --- Toggle Ver/Ocultar OUTDOOR ---
  const btnToggleOutdoor = $id('btn-toggle-outdoor');
  const outdoorWrap = $id('outdoor-table-wrap');
  if (btnToggleOutdoor && outdoorWrap) {
    btnToggleOutdoor.addEventListener('click', () => {
      const hidden = outdoorWrap.style.display === 'none' || !outdoorWrap.style.display;
      outdoorWrap.style.display = hidden ? 'block' : 'none';
      btnToggleOutdoor.textContent = hidden ? 'Ocultar' : 'Ver';
      btnToggleOutdoor.setAttribute('aria-expanded', String(hidden));
    });
    // Estado inicial
    outdoorWrap.style.display = 'none';
    btnToggleOutdoor.textContent = 'Ver';
    btnToggleOutdoor.setAttribute('aria-expanded', 'false');
  }

});
</script>


