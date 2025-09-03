<?php
// 🔒 Autenticación básica (ajusta a tu sistema)
if (empty($_SESSION['userName'])) {
  header('Location: ' . $baseUrl . '/login');
  exit;
}

$userName = $_SESSION['userName'] ?? 'Usuario';
$userRole = $_SESSION['userRole'] ?? 'usuario';

// 🔢 Utilidades de métricas (opcionales si tienes $pdo)
$counts = [
  'users'   => 0,
  'eventsUpcoming' => 0,
  'eventsPast'     => 0,
  'files'   => 0,
];

$recentEvents = []; // últimos eventos
$system = [
  'php' => PHP_VERSION,
  'db'  => 'desconocido',
];

// Si tienes $pdo, intenta obtener datos reales
if (isset($pdo) && $pdo instanceof PDO) {
  try {
    $counts['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  } catch (Throwable $e) {}

  try {
    $counts['eventsUpcoming'] = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn();
    $counts['eventsPast']     = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_date <  CURDATE()")->fetchColumn();
  } catch (Throwable $e) {}

  try {
    $counts['files'] = (int)$pdo->query("SELECT COUNT(*) FROM files")->fetchColumn();
  } catch (Throwable $e) {}

  try {
    $stmt = $pdo->query("
      SELECT id, title, slug, event_date, status
      FROM events
      ORDER BY updated_at DESC, id DESC
      LIMIT 6
    ");
    $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $system['db'] = 'OK';
  } catch (Throwable $e) {
    $system['db'] = 'error';
  }
}

// Helper para badge de estado
function badge($status) {
  $s = strtolower((string)$status);
  if (in_array($s, ['published','publicado','1','activo'])) return '<span class="badge badge--ok">Publicado</span>';
  if (in_array($s, ['draft','borrador','0'])) return '<span class="badge badge--warn">Borrador</span>';
  return '<span class="badge">'.$status.'</span>';
}
?>

    <header class="admin__topbar">
      <button class="sidebar__toggle" id="btnOpenSidebar" aria-label="Abrir menú">
        <i class="fa-solid fa-bars"></i>
      </button>

      <div class="topbar__title">
        <h1>¡Hola, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>!</h1>
        <span class="role">Rol: <?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>

      <div class="topbar__actions">
        <a class="btn btn--ghost" href="<?= $baseUrl; ?>/" target="_blank" rel="noopener">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Ver sitio
        </a>
        <a class="btn" href="<?= $baseUrl; ?>/admin/logout">
          <i class="fa-solid fa-right-from-bracket"></i> Salir
        </a>
      </div>
    </header>

    <main class="admin__content__wel">


      <!-- Métricas -->
      <section class="stats">
        <article class="stat">
          <div class="stat__icon"><i class="fa-solid fa-users"></i></div>
          <div class="stat__meta">
            <span class="stat__label">Usuarios</span>
            <span class="stat__value"><?= number_format($counts['users']); ?></span>
          </div>
        </article>

        <article class="stat">
          <div class="stat__icon"><i class="fa-solid fa-calendar-check"></i></div>
          <div class="stat__meta">
            <span class="stat__label">Eventos próximos</span>
            <span class="stat__value"><?= number_format($counts['eventsUpcoming']); ?></span>
          </div>
        </article>

        <article class="stat">
          <div class="stat__icon"><i class="fa-solid fa-calendar-xmark"></i></div>
          <div class="stat__meta">
            <span class="stat__label">Eventos pasados</span>
            <span class="stat__value"><?= number_format($counts['eventsPast']); ?></span>
          </div>
        </article>

        <article class="stat">
          <div class="stat__icon"><i class="fa-solid fa-folder"></i></div>
          <div class="stat__meta">
            <span class="stat__label">Archivos</span>
            <span class="stat__value"><?= number_format($counts['files']); ?></span>
          </div>
        </article>
      </section>

      <!-- Atajos -->
      <section class="cards-grid">
        <article class="card">
          <h3><i class="fa-solid fa-circle-plus"></i> Nuevo evento</h3>
          <p>Crea un evento con imagen destacada, categorías, etiquetas y metadatos.</p>
          <a class="btn" href="<?= $baseUrl; ?>/admin/eventos/add">Crear evento</a>
        </article>

        <article class="card">
          <h3><i class="fa-solid fa-upload"></i> Subir archivo</h3>
          <p>Biblioteca/Revista. Soporta categorías y estado (borrador/publicado).</p>
          <a class="btn" href="<?= $baseUrl; ?>/admin/archivos/add">Subir archivo</a>
        </article>

        <article class="card">
          <h3><i class="fa-solid fa-user-plus"></i> Nuevo usuario</h3>
          <p>Invita o crea una cuenta con roles y verificación por email.</p>
          <a class="btn" href="<?= $baseUrl; ?>/admin/usuarios/add">Crear usuario</a>
        </article>

        <article class="card card--status">
          <h3><i class="fa-solid fa-heart-pulse"></i> Estado del sistema</h3>
          <ul class="status">
            <li><span>PHP:</span> <strong><?= htmlspecialchars($system['php']); ?></strong></li>
            <li><span>Base de datos:</span>
              <?= $system['db'] === 'OK' ? '<strong class="ok">Conectada</strong>' : '<strong class="warn">Sin conexión</strong>'; ?>
            </li>
          </ul>
        </article>
      </section>

      <!-- Recientes -->
      <section class="panel">
        <div class="panel__head">
          <h3><i class="fa-solid fa-clock-rotate-left"></i> Actividad reciente (Eventos)</h3>
          <a class="btn btn--ghost" href="<?= $baseUrl; ?>/admin/events">Ver todos</a>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Título</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th class="right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($recentEvents)): ?>
                <?php foreach ($recentEvents as $ev): ?>
                  <tr>
                    <td class="ellipsis">
                      <a href="<?= $baseUrl; ?>/admin/eventos/edit?id=<?= (int)$ev['id']; ?>">
                        <?= htmlspecialchars($ev['title'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                      </a>
                    </td>
                    <td><?= htmlspecialchars($ev['event_date'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= badge($ev['status'] ?? '—'); ?></td>
                    <td class="right">
                      <a class="btn btn--tiny" href="<?= $baseUrl; ?>/eventos/<?= htmlspecialchars($ev['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" target="_blank">Ver</a>
                      <a class="btn btn--tiny btn--ghost" href="<?= $baseUrl; ?>/admin/eventos/edit?id=<?= (int)$ev['id']; ?>">Editar</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="4" class="muted">No hay actividad reciente.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>


<script>
// Toggle sidebar en móviles
document.getElementById('btnOpenSidebar')?.addEventListener('click', () => {
  document.getElementById('sidebar')?.classList.add('open');
});
document.getElementById('btnCloseSidebar')?.addEventListener('click', () => {
  document.getElementById('sidebar')?.classList.remove('open');
});
</script>

