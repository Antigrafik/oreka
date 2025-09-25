<section class="container" id="my-space-root">
  <!-- Título -->
  <div class="hero" style="margin-top:16px">
    <h1>MI ESPACIO OREKA</h1>
    <p class="lead">Tu centro de control personal. Aquí puedes ver tu progreso, tu actividad y tus puntos.</p>
  </div>

  <!-- KPIs -->
  <div class="myspace-grid gap-20 mb-24">
    <div class="card kpi-card">
      <div class="kpi-number" id="kpi-total-points">—</div>
      <div class="kpi-label">Capital (Total)</div>
    </div>
    <div class="card kpi-card">
      <div class="kpi-number" id="kpi-month-points">—</div>
      <div class="kpi-label">Puntos de este mes</div>
    </div>
  </div>

    <!-- Historiales -->
    <div class="myspace-grid-hist gap-20">
        <section id="my-space-root">
            <?php include __DIR__ . '/activity.php'; ?>
            <?php include __DIR__ . '/learn_history.php'; ?>
            <?php include __DIR__ . '/forum_history.php'; ?>
            <?php include __DIR__ . '/recommendations_history.php'; ?>
            <?php include __DIR__ . '/routines_history.php'; ?>
            <?php include __DIR__ . '/trials_history.php'; ?>
            <?php include __DIR__ . '/meeting_history.php'; ?>
        </section>
    </div>
</section>

<script>
  // Placeholders hasta conectar con la BD
  document.addEventListener('DOMContentLoaded', () => {
    const $total = document.getElementById('kpi-total-points');
    const $month = document.getElementById('kpi-month-points');
    if ($total && !$total.dataset.init) { $total.textContent = '—'; $total.dataset.init = '1'; }
    if ($month && !$month.dataset.init) { $month.textContent = '—'; $month.dataset.init = '1'; }
  });
</script>
