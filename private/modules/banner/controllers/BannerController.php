<?php
require_once __DIR__ . '/../models/Banner.php';

class BannerController {

    public function renderForHome(): string {
        $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es';

        $model  = new BannerModel();
        $banner = $model->getActive($lang, $fallback);
        if (!$banner) return '';

        ob_start();
        $b = $banner;
        include __DIR__ . '/../views/banner.php';
        return ob_get_clean();
    }
}
