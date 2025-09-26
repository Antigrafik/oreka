<?php
class LegalController {
    public function render(): string {
        require_once PRIVATE_PATH . '/modules/legal/models/Legal.php';

        // usa el que fija config.php
        $lang = (defined('DEFAULT_LANG') && in_array(DEFAULT_LANG, ['es','eu'], true))
              ? DEFAULT_LANG
              : 'es';

        $html = (new Legal())->getHtml($lang);

        ob_start();
        include PRIVATE_PATH . '/modules/legal/views/legal.php';
        return ob_get_clean();
    }
}

