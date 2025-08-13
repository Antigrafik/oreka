<?php
require_once __DIR__ . '/../models/Community.php';

class CommunityController
{
    public function render(): string {
        $m = new Community();
        $recs     = $m->getRecommendations();
        $themes   = $m->getThemes();
        $supports = $m->getSupports();

        ob_start();
        include __DIR__ . '/../views/community.php';
        return ob_get_clean();
    }
}
