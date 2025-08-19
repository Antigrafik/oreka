<?php
require_once __DIR__ . '/../models/Community.php';

class CommunityController
{
    public function render(): string {
        $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es';

        $m = new Community();
        $recs     = $m->getRecommendations($lang, $fallback);
        $themes   = $m->getThemes();
        $supports = $m->getSupports();

        // Asegurar $language en la vista
        $language = $GLOBALS['language'] ?? [];

        ob_start();
        include __DIR__ . '/../views/community.php';
        return ob_get_clean();
    }
}
