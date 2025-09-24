<?php
$success = $_SESSION['flash_success_trial'] ?? null;
$error   = $_SESSION['flash_error_trial']   ?? null;
unset($_SESSION['flash_success_trial'], $_SESSION['flash_error_trial']);
?>

<section id="trial" class="max-w-3xl mx-auto my-10 px-4">
  <h2 class="text-center text-3xl font-semibold mb-2">Tus pruebas deportivas</h2>
  <p class="text-center text-gray-600 mb-6">
    ¿Te animas a subir tus pruebas deportivas? Cuéntanos si has participado en caminatas solidarias,
    maratones, marchas cicloturistas, campeonatos de pádel, jornadas de silencio, gymkhanas, etc.
  </p>

  <?php if ($success): ?>
    <div class="alert success text-center mb-4"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert error text-center mb-4"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form id="trial-form" method="post" enctype="multipart/form-data" class="grid gap-4">
    <!-- Identificador para que SOLO TrialController procese este POST -->
    <input type="hidden" name="form" value="trial_submit">
    <!-- Límite de archivo (5 MB, redundante pero útil en algunos navegadores) -->
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= 5 * 1024 * 1024 ?>">

    <textarea
      name="content"
      rows="6"
      class="w-full border rounded p-3"
      placeholder="Describe tu prueba..."
      required></textarea>

    <div class="text-sm text-gray-700">
      Adjunta una <strong>imagen (JPG/PNG)</strong> o un <strong>PDF</strong>. Tamaño máximo <strong>5&nbsp;MB</strong>.
    </div>

    <input type="file" name="file" accept=".jpg,.jpeg,.png,.pdf" required>

    <div class="flex justify-center mt-2">
      <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded shadow">Enviar</button>
    </div>
  </form>
</section>

<script>
(function () {
  // Guardar/restaurar scroll para que, tras el PRG, no suba arriba
  const KEY = 'scrollY_trial';
  const y = sessionStorage.getItem(KEY);
  if (y !== null) {
    sessionStorage.removeItem(KEY);
    window.scrollTo(0, parseInt(y, 10) || 0);
  }
  document.getElementById('trial-form')?.addEventListener('submit', () => {
    sessionStorage.setItem(KEY, String(window.scrollY || window.pageYOffset || 0));
  });
})();
</script>

<style>
  .alert { padding:10px 12px; border-radius:8px; margin:10px 0; }
  .alert.success { background:#e7f7ee; border:1px solid #8ad1a3; color:#216b3a; }
  .alert.error   { background:#fdeaea; border:1px solid #f5b5b5; color:#7d1f1f; }
</style>
