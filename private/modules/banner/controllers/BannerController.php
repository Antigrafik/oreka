<?php
// modules/banner/controllers/BannerController.php
require_once __DIR__ . '/../models/Banner.php';

class BannerController {
    /**
     * Render para Home. Devuelve HTML (string) o '' si no hay banner activo.
     */
    public function renderForHome(): string {
        // Idioma del sitio
        $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es';

        $model  = new BannerModel();
        $banner = $model->getActive($lang, $fallback);
        if (!$banner) return '';

        ob_start();
        // Renderiza con su vista
        $b = $banner; // alias corto para la vista
        include __DIR__ . '/../views/banner.php';
        return ob_get_clean();
    }
}
