<?php
/** @var array $htmlSections */
/** @var array $language */
?>
<section id="community" class="title-section">
  <h1><?= htmlspecialchars($language['community']['title'] ?? 'Community') ?></h1>
  <p><?= htmlspecialchars($language['community']['subtitle'] ?? '') ?></p>
</section>

<?= $htmlSections['recommendations'] ?? '' ?>
<?= $htmlSections['routines'] ?? '' ?>
<?= $htmlSections['trial'] ?? '' ?>
<?= $htmlSections['meeting'] ?? '' ?>
