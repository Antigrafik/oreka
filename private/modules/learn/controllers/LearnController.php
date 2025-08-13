<?php
require_once __DIR__ . '/../models/Learn.php';

class LearnController {
    public function render(): string {

        $lang = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es';

        $model  = new Learn();
        $learns = $model->getAll($lang, $fallback);

        $language = $GLOBALS['language'] ?? [];

        ob_start();
        include __DIR__ . '/../views/learn.php';
        return ob_get_clean();
    }
}
