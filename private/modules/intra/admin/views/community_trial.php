<?php
global $language;
$checked = !empty($moduleFlags['trial']);
?>
<h2><?= htmlspecialchars($language['modules']['trial'] ?? 'Pruebas') ?></h2>

<form method="post" action="" class="mod-toggle">
  <input type="hidden" name="__action__" value="toggle_module">
  <input type="hidden" name="module_key" value="trial">
  <input type="hidden" name="redirect"   value="community/trial">
  <label class="toggle">
    <input type="checkbox" name="visible" value="1" <?= $checked ? 'checked' : '' ?>>
    <?= htmlspecialchars($language['admin_toggle']['label'] ?? 'Mostrar este módulo') ?>
  </label>
  <button class="btn btn-red" type="submit"><?= htmlspecialchars($language['admin_toggle']['save'] ?? 'Guardar') ?></button>
</form>

<?php
$pdo = $GLOBALS['pdo'] ?? null;

$trialPoints = 0;
try {
  if ($pdo) {
    $st = $pdo->prepare("
      SELECT TOP (1) points
      FROM dbo.point_modules
      WHERE module_code = 'trial' AND effective_to IS NULL
      ORDER BY id DESC
    ");
    $st->execute();
    $trialPoints = (int)($st->fetchColumn() ?? 0);
  }
} catch (Throwable $e) { /* opcional: log */ }
?>

<!-- === PUNTOS DEL MÓDULO TRIAL === -->
<div id="trial-points-box" style="display:flex;gap:10px;align-items:center;margin:10px 0 18px">
  <div>
    <span><?= htmlspecialchars($language['admin_trial']['points_label'] ?? 'Puntos por actividad del módulo:') ?></span>
    <strong id="trial-points-text"><?= (int)$trialPoints ?></strong>
  </div>

  <form method="post" action="" id="trial-points-form" style="display:inline-flex;gap:8px;align-items:center">
    <input type="hidden" name="__action__" value="trial_update_points">
    <input type="hidden" name="module_key" value="trial">
    <input type="number" name="points" id="trial-points-input"
           value="<?= (int)$trialPoints ?>" min="0" step="1"
           style="width:90px;display:none;padding:.35rem;border:1px solid #0aa;border-radius:8px">
    <button type="button" class="btn" id="btn-edit-trial-points">
      <?= htmlspecialchars($language['admin_trial']['edit_points'] ?? 'Modificar puntos') ?>
    </button>
    <button type="submit" class="btn" id="btn-save-trial-points" style="display:none">
      <?= htmlspecialchars($language['admin_trial']['save_points'] ?? 'Guardar puntos') ?>
    </button>
  </form>
</div>

<script>
(() => {
  const txt   = document.getElementById('trial-points-text');
  const input = document.getElementById('trial-points-input');
  const bEdit = document.getElementById('btn-edit-trial-points');
  const bSave = document.getElementById('btn-save-trial-points');
  const form  = document.getElementById('trial-points-form');

  if (bEdit && bSave && txt && input && form) {
    bEdit.addEventListener('click', () => {
      txt.style.display   = 'none';
      input.style.display = '';
      bEdit.style.display = 'none';
      bSave.style.display = '';
      input.focus(); input.select?.();
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
