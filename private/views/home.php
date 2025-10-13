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

<div class="main-content">
  <div class="module">
    <?php if ($showBanner && !empty($bannerSection)): ?>
      <div id="module-banner" class="module-banner"><?= $bannerSection ?></div>
    <?php endif; ?>
 
    <?php if ($showLearn): ?>
      <div id="module-learn" class="module-learn"><?= $learnSection ?></div>
    <?php endif; ?>
 
    <?php if ($showForum): ?>
      <div id="module-forum" class="module-forum"><?= $forumSection ?></div>
    <?php endif; ?>
 
    <?php if ($showComm): ?>
      <div id="module-community" class="module-community"><?= $communitySection ?></div>
    <?php endif; ?>
 
    <?php if ($showLegal): ?>
      <div id="module-legal" class="hidden module-legal"><?= $legalSection ?></div>
    <?php endif; ?>
 
    <div id="module-myspace" class="hidden module-myspace"><?= $mySpaceSection ?></div>
 
    <?php if (!empty($adminSection)): ?>
      <div id="module-admin" class="hidden module-admin"><?= $adminSection ?></div>
    <?php endif; ?>
  </div>
</div>


