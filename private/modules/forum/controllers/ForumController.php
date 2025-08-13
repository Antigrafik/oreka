<?php
require_once __DIR__ . '/../models/Forum.php';

class ForumController
{
    public function render(): string
    {
        $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es';

        $events = (new Forum())->getAll($lang, $fallback);

        // que el view tenga $language disponible
        $language = $GLOBALS['language'] ?? [];

        ob_start();
        include __DIR__ . '/../views/forum.php';
        return ob_get_clean();
    }
}
