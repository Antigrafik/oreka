<?php
 global $language;
?>

<section id="routines">
  <h2><?= htmlspecialchars($language['routines']['title'] ?? '') ?></h2>
  <p class="lead"><?= htmlspecialchars($language['routines']['subtitle'] ?? '') ?></p>

  <?php
    $success = $_SESSION['flash_success_routines'] ?? null;
    $error   = $_SESSION['flash_error_routines']   ?? null;
    unset($_SESSION['flash_success_routines'], $_SESSION['flash_error_routines']);
  ?>

  <?php if ($success): ?>
    <div class="alert success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" id="routine-form" class="routine-grid">
    <input type="hidden" name="form" value="routines_submit">

    <!-- Tipo -->
    <div class="field">
      <label><?= htmlspecialchars($language['routines']['type'] ?? '') ?>:</label>
      <select id="type_id" name="type_id">
        <option value=""><?= htmlspecialchars($language['routines']['select'] ?? '') ?></option>
        <?php foreach ($types as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= ((int)$selectedType === (int)$t['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['name'] ?? ('#' . (int)$t['id'])) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Categoría dependiente -->
    <div class="field">
      <label><?= htmlspecialchars($language['routines']['category'] ?? '') ?>:</label>
      <select id="category_id" name="category_id" <?= $selectedType ? '' : 'disabled' ?>>
        <?php if (!$selectedType): ?>
          <option value=""><?= htmlspecialchars($language['routines']['select_type_first'] ?? '') ?></option>
        <?php else: ?>
          <option value=""><?= htmlspecialchars($language['routines']['select'] ?? '') ?></option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>">
              <?= htmlspecialchars($c['name'] ?? ('#' . (int)$c['id'])) ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>

    <!-- Frecuencia -->
    <div class="field">
      <label><?= htmlspecialchars($language['routines']['frequency'] ?? '') ?>:</label>
      <select name="frequency">
        <option value=""><?= htmlspecialchars($language['routines']['select'] ?? '') ?></option>
        <?php for ($i = 1; $i <= 7; $i++): ?>
          <option value="<?= $i ?>"><?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <!-- Duración -->
    <div class="field">
      <label><?= htmlspecialchars($language['routines']['duration'] ?? '') ?>:</label>
      <input
        name="duration"
        type="number"
        min="1"
        step="1"
        placeholder="<?= htmlspecialchars($language['routines']['placeholder_duration'] ?? '') ?>">
    </div>

    <div class="actions">
      <button type="submit" class="btn">
        <?= htmlspecialchars($language['routines']['btn'] ?? '') ?>
      </button>
    </div>
  </form>

  <script>
    (function () {
      const typeSel = document.getElementById('type_id');
      const catSel  = document.getElementById('category_id');

      // Mapa precargado desde PHP: { [idTipo]: [{id, name, slug}, ...] }
      const CATS_BY_TYPE = <?= json_encode($catsByType, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

      // Textos localizados desde PHP
      const T_SELECT            = <?= json_encode($language['routines']['select'] ?? '') ?>;
      const T_SELECT_TYPE_FIRST = <?= json_encode($language['routines']['select_type_first'] ?? '') ?>;

      function resetCategories(placeholder) {
        catSel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder || T_SELECT;
        catSel.appendChild(opt);
      }

      function fillCategories(list) {
        resetCategories(T_SELECT);
        list.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.id;
          opt.textContent = c.name || ('#' + c.id);
          catSel.appendChild(opt);
        });
      }

      function onTypeChange() {
        const tid = parseInt(typeSel.value, 10);
        if (Number.isFinite(tid) && tid > 0 && CATS_BY_TYPE[tid]) {
          fillCategories(CATS_BY_TYPE[tid]);
          catSel.disabled = false;
        } else {
          resetCategories(T_SELECT_TYPE_FIRST);
          catSel.disabled = true;
        }
      }

      typeSel.addEventListener('change', onTypeChange);

      // Estado inicial coherente
      if (!typeSel.value) {
        resetCategories(T_SELECT_TYPE_FIRST);
        catSel.disabled = true;
      } else {
        onTypeChange();
      }
    })();
  </script>
</section>
