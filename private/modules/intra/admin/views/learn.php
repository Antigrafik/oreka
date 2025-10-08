<?php
global $language;
$checked = !empty($moduleFlags['learn']);

$pdo = $GLOBALS['pdo'] ?? null; // <-- Asegura acceso al PDO

// --- Leer puntos vigentes del módulo 'learn' ---
$learnPoints = 0;
try {
  if ($pdo) {
    $st = $pdo->prepare("
      SELECT TOP (1) points
      FROM dbo.point_modules
      WHERE module_code = 'learn' AND effective_to IS NULL
      ORDER BY id DESC
    ");
    $st->execute();
    $learnPoints = (int)($st->fetchColumn() ?? 0);
  }
} catch (Throwable $e) { /* opcional: log */ }
?>

<h2><?= htmlspecialchars($language['menu_admin']['learn'] ?? 'Aula') ?></h2>

<form method="post" action="" class="mod-toggle">
  <input type="hidden" name="__action__"  value="toggle_module">
  <input type="hidden" name="module_key"  value="learn">
  <input type="hidden" name="redirect"    value="learn">

  <label class="toggle">
    <input type="checkbox" name="visible" value="1" <?= $checked ? 'checked' : '' ?>>
    <?= htmlspecialchars($language['admin_toggle']['label'] ?? 'Mostrar este módulo') ?>
  </label>
  <button class="btn btn-red" type="submit">
    <?= htmlspecialchars($language['admin_toggle']['save'] ?? 'Guardar') ?>
  </button>
</form>

<!-- === PUNTOS DEL MÓDULO LEARN === -->
<div id="learn-points-box" style="display:flex;gap:10px;align-items:center;margin:10px 0 18px">
  <div>
    <span><?= htmlspecialchars($language['admin_learn']['points_label'] ?? 'Puntos por actividad del módulo:') ?></span>
    <strong id="learn-points-text"><?= (int)$learnPoints ?></strong>
  </div>

  <form method="post" action="" id="learn-points-form" style="display:inline-flex;gap:8px;align-items:center">
    <input type="hidden" name="__action__" value="learn_update_points">
    <input type="hidden" name="module_key" value="learn">
    <input type="number" name="points" id="learn-points-input"
           value="<?= (int)$learnPoints ?>" min="0" step="1"
           style="width:90px;display:none;padding:.35rem;border:1px solid #0aa;border-radius:8px">
    <button type="button" class="btn" id="btn-edit-learn-points">
      <?= htmlspecialchars($language['admin_learn']['edit_points'] ?? 'Modificar puntos') ?>
    </button>
    <button type="submit" class="btn" id="btn-save-learn-points" style="display:none">
      <?= htmlspecialchars($language['admin_learn']['save_points'] ?? 'Guardar puntos') ?>
    </button>
  </form>
</div>

<script>
(() => {
  const txt   = document.getElementById('learn-points-text');
  const input = document.getElementById('learn-points-input');
  const bEdit = document.getElementById('btn-edit-learn-points');
  const bSave = document.getElementById('btn-save-learn-points');
  const form  = document.getElementById('learn-points-form');

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

