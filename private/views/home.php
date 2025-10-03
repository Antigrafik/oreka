<?php
global $pdo;

$flags = [];
try {
  $st = $pdo->query("SELECT module_key, CONVERT(INT, show_module) AS show_module FROM [module_toggle]");
  foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $flags[$r['module_key']] = ((int)$r['show_module'] === 1);
  }
} catch (Throwable $e) {}

$showLearn = array_key_exists('learn', $flags) ? $flags['learn'] : true;
$showForum = array_key_exists('forum', $flags) ? $flags['forum'] : true;
$showComm  = array_key_exists('community', $flags) ? $flags['community'] : true;
$showLegal = array_key_exists('legal', $flags) ? $flags['legal'] : true;
$showBanner = array_key_exists('banner', $flags) ? $flags['banner'] : true;

require_once PRIVATE_PATH . '/modules/banner/controllers/BannerController.php';
$bannerSection = (new BannerController())->renderForHome();
?>

<?php if ($showBanner && !empty($bannerSection)): ?>
  <div id="module-banner"><?= $bannerSection ?></div>
<?php endif; ?>

<?php if ($showLearn): ?>
  <div id="module-learn"><?= $learnSection ?></div>
<?php endif; ?>

<?php if ($showForum): ?>
  <div id="module-forum"><?= $forumSection ?></div>
<?php endif; ?>

<?php if ($showComm): ?>
  <div id="module-community"><?= $communitySection ?></div>
<?php endif; ?>

<?php if ($showLegal): ?>
  <div id="module-legal" class="hidden"><?= $legalSection ?></div>
<?php endif; ?>

<div id="module-myspace" class="hidden"><?= $mySpaceSection ?></div>

<?php if (!empty($adminSection)): ?>
  <div id="module-admin" class="hidden"><?= $adminSection ?></div>
<?php endif; ?>

<style>.hidden{display:none!important}</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const M = {
    banner:    document.getElementById('module-banner'),
    learn:     document.getElementById('module-learn'),
    forum:     document.getElementById('module-forum'),
    community: document.getElementById('module-community'),
    legal:     document.getElementById('module-legal'),
    myspace:   document.getElementById('module-myspace'),
    admin:     document.getElementById('module-admin')
  };

  const ALL = Object.values(M).filter(Boolean);
  const hideAll = () => ALL.forEach(el => el.classList.add('hidden'));
  const show    = el => { if (el) el.classList.remove('hidden'); };

  function showDefault() {
    [M.banner, M.learn, M.forum, M.community].forEach(el => { if (el) show(el); });
  }

  function route(hash) {
    const h = (hash || '').toLowerCase();
    hideAll();

    if ((h === '#legal' || h.startsWith('#legal/')) && M.legal) {
      show(M.legal); return;
    }
    if (h === '#my-space' || h.startsWith('#my-space/')) {
      show(M.myspace); return;
    }
    if (h === '#admin' || h.startsWith('#admin/')) {
      show(M.admin); return;
    }

    showDefault();
  }

  route(location.hash || '#learn');
  window.addEventListener('hashchange', () => route(location.hash));

  document.querySelectorAll('.menu a[href^="#"]').forEach(a => {
    a.addEventListener('click', () => setTimeout(() => route(location.hash), 0));
  });

  const legalBtn = document.querySelector('.legal-link');
  if (legalBtn) legalBtn.addEventListener('click', () => setTimeout(() => route('#legal'), 0));
});
</script>
