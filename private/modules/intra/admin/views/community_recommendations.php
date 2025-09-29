<?php
global $language;
$checked = !empty($moduleFlags['recommendations']);
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
