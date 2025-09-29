<?php
global $language;
$checked = !empty($moduleFlags['community']);
?>
<h2><?= htmlspecialchars($language['menu_admin']['community'] ?? 'Comunidad') ?></h2>

<form method="post" action="" class="mod-toggle">
  <input type="hidden" name="__action__" value="toggle_module">
  <input type="hidden" name="module_key" value="community">
  <input type="hidden" name="redirect"   value="community">

  <label class="toggle">
    <input type="checkbox" name="visible" value="1" <?= $checked ? 'checked' : '' ?>>
    <span></span>
    <?= htmlspecialchars($language['admin_toggle']['label'] ?? 'Mostrar este módulo (y su menú)') ?>
  </label>
  <button class="btn btn-red" type="submit"><?= htmlspecialchars($language['admin_toggle']['save'] ?? 'Guardar') ?></button>
</form>

<p>(Ajustes de Comunidad, si los hubiera.)</p>
