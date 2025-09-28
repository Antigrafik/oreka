<?php
require_once __DIR__ . '/../models/Admin.php';

class AdminController {
    public function render(): string {

        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        // Guardado desde pestaña Legal
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['__action__'] ?? '') === 'save_legal')) {
            $status     = isset($_POST['status']) ? 1 : 0;
            $titleEs    = $_POST['title_es']   ?? '';
            $contentEs  = $_POST['content_es'] ?? '';
            $titleEu    = $_POST['title_eu']   ?? '';
            $contentEu  = $_POST['content_eu'] ?? '';

            try {
                $adm = new Admin();
                // <-- usamos actualización in-place
                $adm->saveLegalUpdate($status, $titleEs, $contentEs, $titleEu, $contentEu);
                $_SESSION['flash_success_admin'] = 'Legal guardado correctamente.';
            } catch (Throwable $e) {
                $_SESSION['flash_error_admin'] = 'Error al guardar Legal: ' . $e->getMessage();
            }

            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#admin/legal');
            exit;
        }

        $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es';

        $model  = new Admin();
        $admins = $model->getAll($lang, $fallback);

        $language = $GLOBALS['language'] ?? [];

        ob_start();
        include __DIR__ . '/../views/admin.php';
        return ob_get_clean();
    }
}
