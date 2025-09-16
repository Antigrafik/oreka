
<section id="routines" class="max-w-5xl mx-auto my-10 px-4">
  <h2 class="text-center text-3xl font-semibold mb-2">Tus rutinas</h2>
  <p class="text-center text-gray-600 mb-6">Calcula tus puntos según tu actividad semanal.</p>

  <?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="text-center mb-4"><?= htmlspecialchars($_SESSION['flash_msg']) ?></div>
    <?php unset($_SESSION['flash_msg']); ?>
  <?php endif; ?>

  <form method="post" id="routine-form" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
    <!-- Tipo -->
    <div>
      <label class="block mb-1 font-medium">Tipo:</label>
      <select id="type_id" name="type_id" class="w-full border rounded p-2">
        <option value="">Selecciona</option>
        <?php foreach ($types as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= ((int)$selectedType === (int)$t['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['name'] ?? ('#' . (int)$t['id'])) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Categoría dependiente -->
    <div>
      <label class="block mb-1 font-medium">Categoría:</label>
      <select id="category_id" name="category_id" class="w-full border rounded p-2" <?= $selectedType ? '' : 'disabled' ?>>
        <?php if (!$selectedType): ?>
          <option value="">Selecciona tipo primero</option>
        <?php else: ?>
          <option value="">Selecciona</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>">
              <?= htmlspecialchars($c['name'] ?? ('#' . (int)$c['id'])) ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>

    <!-- Frecuencia 1..7 -->
    <div>
      <label class="block mb-1 font-medium">Frecuencia (días/semana):</label>
      <select name="frequency" class="w-full border rounded p-2">
        <option value="">Selecciona</option>
        <?php for ($i = 1; $i <= 7; $i++): ?>
          <option value="<?= $i ?>"><?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <!-- Duración (min/sesión) -->
    <div>
      <label class="block mb-1 font-medium">Duración (min por sesión):</label>
      <input name="duration" type="number" min="1" step="1" class="w-full border rounded p-2" placeholder="Ej. 30">
    </div>

    <div class="md:col-span-4 flex justify-center mt-2">
      <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded shadow">
        Calcular
      </button>
    </div>
  </form>

  <script>
    (function () {
      const typeSel = document.getElementById('type_id');
      const catSel  = document.getElementById('category_id');

      // Mapa precargado desde PHP: { [idTipo]: [{id, name, slug}, ...] }
      const CATS_BY_TYPE = <?=
        json_encode($catsByType, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
      ?>;

      function resetCategories(placeholder) {
        catSel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder || 'Selecciona';
        catSel.appendChild(opt);
      }

      function fillCategories(list) {
        resetCategories('Selecciona');
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
          resetCategories('Selecciona tipo primero');
          catSel.disabled = true;
        }
      }

      typeSel.addEventListener('change', onTypeChange);

      // Estado inicial coherente
      if (!typeSel.value) {
        resetCategories('Selecciona tipo primero');
        catSel.disabled = true;
      } else {
        onTypeChange();
      }
    })();
  </script>
</section>
