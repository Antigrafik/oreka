<?php
global $language;

$success = $_SESSION['flash_success_trial'] ?? null;
$error   = $_SESSION['flash_error_trial']   ?? null;
unset($_SESSION['flash_success_trial'], $_SESSION['flash_error_trial']);

$L = $language['trial'] ?? [];
?>
<section id="trial">
  <h2><?= htmlspecialchars($L['title'] ?? '') ?></h2>
  <p class="lead">
    <?= htmlspecialchars($L['subtitle'] ?? '') ?>
  </p>

  <?php if ($success): ?>
    <div class="alert success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form id="trial-form" class="trial-form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="form" value="trial_submit">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= 5 * 1024 * 1024 ?>">

    <div class="form-grid">
      <label class="field">
        <textarea
          name="content"
          rows="6"
          placeholder="<?= htmlspecialchars($L['placeholder'] ?? '') ?>"
          required></textarea>
      </label>

      <div class="hint">
        <?= htmlspecialchars($L['helper'] ?? '') ?>
      </div>

      <label class="field">
        <label class="file-label" for="file">
          <?= htmlspecialchars($language['trial']['choose_file'] ?? '') ?>
        </label>
        <input 
          type="file" 
          id="file" 
          name="file" 
          class="file-input" 
          accept=".jpg,.jpeg,.png,.pdf" 
          required
        >
        <span id="file-chosen" class="file-chosen">
          <?= htmlspecialchars($language['trial']['no_file'] ?? '') ?>
        </span>
      </label>

      <div class="actions">
        <button type="submit" class="btn">
          <?= htmlspecialchars($L['button'] ?? 'Enviar') ?>
        </button>
      </div>
    </div>
  </form>
</section>

<script>
  (function () {
    // --- Restaurar scroll tras PRG ---
    const KEY = 'scrollY_trial';
    const y = sessionStorage.getItem(KEY);
    if (y !== null) {
      sessionStorage.removeItem(KEY);
      window.scrollTo(0, parseInt(y, 10) || 0);
    }
    document.getElementById('trial-form')?.addEventListener('submit', () => {
      sessionStorage.setItem(KEY, String(window.scrollY || window.pageYOffset || 0));
    });

    // --- Gestión del input file con idioma ---
    const fileInput = document.getElementById('file');
    const fileChosen = document.getElementById('file-chosen');

    if (fileInput && fileChosen) {
      fileInput.addEventListener('change', function () {
        fileChosen.textContent = this.files.length > 0
          ? this.files[0].name
          : "<?= htmlspecialchars($language['trial']['no_file'] ?? '') ?>";
      });
    }
  })();
</script>

