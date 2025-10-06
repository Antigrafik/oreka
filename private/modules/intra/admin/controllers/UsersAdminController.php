<?php
require_once PRIVATE_PATH . '/modules/intra/admin/models/UserAdmin.php';

class UsersAdminController {
    /** @var PDO */
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function data() {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');

        try {
            $page  = max(1, (int)($_GET['page']  ?? 1));
            $per   = min(100, max(1, (int)($_GET['per'] ?? 20)));
            $sort  = $_GET['sort']  ?? 'usuario';
            $dir   = $_GET['dir']   ?? 'ASC';
            $role  = $_GET['role']  ?? '';
            $q     = trim((string)($_GET['q'] ?? ''));

            $model = new UserAdmin($this->pdo);
            $data  = $model->getUsers($page, $per, $sort, $dir, $role, $q);

            echo json_encode([
                'ok'    => true,
                'rows'  => $data['rows'],
                'total' => $data['total'],
                'page'  => $page,
                'per'   => $per
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'  => false,
                'msg' => 'Error en servidor: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    public function updateRole() {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id   = (int)($_POST['id']   ?? 0);
            $role = (string)($_POST['role'] ?? '');

            if ($id <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
                exit;
            }

            $model = new UserAdmin($this->pdo);
            $ok = $model->updateRole($id, $role);

            echo json_encode([
                'ok'  => $ok,
                'msg' => $ok ? 'Actualizado' : 'No se pudo actualizar'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'  => false,
                'msg' => 'Error en servidor: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}
