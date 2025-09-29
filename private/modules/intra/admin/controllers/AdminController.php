<?php
require_once __DIR__ . '/../models/Admin.php';

class AdminController {
    public function render(): string {

        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['__action__'] ?? '';
            $adm = new Admin();

            try {
                if ($action === 'toggle_module') {
                    $key      = strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string)($_POST['module_key'] ?? '')));
                    $visible  = isset($_POST['visible']) && (int)$_POST['visible'] === 1;
                    $redirect = (string)($_POST['redirect'] ?? 'dashboard');

                    if ($key !== '') {
                        $adm->setModuleVisible($key, $visible);
                        $_SESSION['flash_success_admin'] = 'Guardado.';
                    }
                    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#admin/' . $redirect);
                    exit;
                }

                if ($action === 'toggle_modules_batch') {
                    $vis = $_POST['vis'] ?? []; // vis[key]=1
                    $map = [];
                    foreach ($vis as $k => $v) {
                        $map[strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string)$k))] = ((int)$v === 1);
                    }
                    if (!empty($map)) {
                        $adm->setModuleVisibleBatch($map);
                        $_SESSION['flash_success_admin'] = 'Guardado.';
                    }
                    $redirect = (string)($_POST['redirect'] ?? 'community');
                    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#admin/' . $redirect);
                    exit;
                }

                if ($action === 'save_legal') {
                    $status     = isset($_POST['status']) ? 1 : 0;
                    $titleEs    = $_POST['title_es']   ?? '';
                    $contentEs  = $_POST['content_es'] ?? '';
                    $titleEu    = $_POST['title_eu']   ?? '';
                    $contentEu  = $_POST['content_eu'] ?? '';

                    $adm->saveLegalUpdate($status, $titleEs, $contentEs, $titleEu, $contentEu);
                    $_SESSION['flash_success_admin'] = 'Legal guardado correctamente.';

                    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#admin/legal');
                    exit;
                }

            } catch (Throwable $e) {
                $_SESSION['flash_error_admin'] = 'Error: ' . $e->getMessage();
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#'));
                exit;
            }
        }

        $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es';

        $model  = new Admin();
        $admins = $model->getAll($lang, $fallback);

        $moduleFlags = $model->getModuleFlags([
            'learn','forum','community','store','legal',
            'recommendations','routines','trial','meeting'
        ]);

        $language = $GLOBALS['language'] ?? [];

        ob_start();
        include __DIR__ . '/../views/admin.php';
        return ob_get_clean();
    }
}
