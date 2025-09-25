<?php
require_once __DIR__ . '/../models/Admin.php';

class AdminController {
    public function render(): string {

        $lang = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es';

        $model  = new Admin();
        $admins = $model->getAll($lang, $fallback);

        $language = $GLOBALS['language'] ?? [];

        ob_start();
        include __DIR__ . '/../views/admin/admin.php';
        return ob_get_clean();
    }
}