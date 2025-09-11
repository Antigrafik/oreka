<?php
require_once __DIR__ . '/../models/Community.php';

class CommunityController
{
  public function render(): string {
    $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
    $fallback = 'es';

    $m = new Community();
    $recommendations = $m->getRecommendations($lang, $fallback);

    $themes   = $m->getChildren(10, $lang, $fallback);
    $supports = $m->getChildren(11, $lang, $fallback);

    $language = $GLOBALS['language'] ?? [];

    ob_start();

    include __DIR__ . '/../views/community.php';
    return ob_get_clean();
  }
}

