<?php
/** @var array $b  Banner activo (id, is_raffle, prize, title, content, date_start, date_finish, status) */
$badge = $b['is_raffle'] ? 'SORTEO' : 'ANUNCIO';
$prize = trim((string)($b['prize'] ?? ''));
?>
<div class="banner-home card" style="margin: 16px 100px 16px 100px">
  <div class="card-body" style="display:grid;gap:8px;margin:auto;max-width:700px">
    <div style="display:flex;gap:8px;align-items:center">
      <span class="chip" style="border:1px solid #c00;border-radius:999px;padding:.2rem .6rem">
        <?= htmlspecialchars($badge) ?>
      </span>
      <?php if ($b['is_raffle'] && $prize !== ''): ?>
        <span style="opacity:.8">Premio: <strong><?= htmlspecialchars($prize) ?></strong></span>
      <?php endif; ?>
    </div>

    <?php if ($b['title'] !== ''): ?>
      <h3 style="margin:.25rem 0"><?= htmlspecialchars($b['title']) ?></h3>
    <?php endif; ?>

    <div class="content">
      <?= $b['content']?>
    </div>
  </div>
</div>
