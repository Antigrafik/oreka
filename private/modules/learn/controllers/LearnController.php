<?php
require_once __DIR__ . '/../models/Learn.php';

class LearnController {
    public function render(): string {
        // Idioma activo desde tu config (eu/es)
        $lang = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es'; // respaldo si falta la traducción en eu

        $model  = new Learn();
        $learns = $model->getAll($lang, $fallback);

        // (opcional) comprobar qué idioma llega
        // error_log("LearnController => lang={$lang}, fallback={$fallback}");

        // Aseguramos que $language está disponible en la vista
        $language = $GLOBALS['language'] ?? [];

        ob_start();
        include __DIR__ . '/../views/learn.php';
        return ob_get_clean();
    }
}
