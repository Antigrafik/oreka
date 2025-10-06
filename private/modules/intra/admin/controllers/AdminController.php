<?php
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/UsersAdminController.php';
require_once __DIR__ . '/BannerAdminController.php';
require_once __DIR__ . '/LegalAdminController.php';

class AdminController
{
    public function render(): string
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        /* ==== Rutas AJAX/POST especializadas ==== */

        // Users (AJAX tabla)
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'users_data') {
            (new UsersAdminController())->data();
            return '';
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action__'] ?? '') === 'users_update_role') {
            (new UsersAdminController())->updateRole();
            return '';
        }

        // Banner (guardar/borrar)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action__'] ?? '') === 'banner_save') {
            (new BannerAdminController())->save();
            return '';
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action__'] ?? '') === 'banner_delete') {
            (new BannerAdminController())->delete();
            return '';
        }

        // Legal (guardar/publicar)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__action__'] ?? '') === 'save_legal') {
            (new LegalAdminController())->save();
            return '';
        }

        // Toggles de módulos (se queda aquí)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['__action__'] ?? '';
            if ($action === 'toggle_module' || $action === 'toggle_modules_batch') {
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
                } catch (Throwable $e) {
                    $base = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                    header('Location: ' . $base . '#admin');
                    exit;
                }
            }
        }

        /* ==== Carga de flags y render de vista contenedora ==== */
        $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es';

        $model  = new Admin();
        // (getAll queda por compatibilidad; no se usa aquí, pero lo mantenemos)
        $admins = $model->getAll($lang, $fallback);

        $moduleFlags = $model->getModuleFlags([
            'learn','forum','community','store','legal','banner',
            'recommendations','routines','trial','meeting'
        ]);

        $language = $GLOBALS['language'] ?? [];

        ob_start();
        include __DIR__ . '/../views/admin.php';
        return ob_get_clean();
    }
}
