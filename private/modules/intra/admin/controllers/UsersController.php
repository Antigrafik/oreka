<?php
require_once PRIVATE_PATH . '/modules/intra/admin/models/UserAdmin.php';

class UsersController {
    private $pdo;

    public function __construct() {
        global $pdo; // ya lo tenéis en config/db_connect.php
        $this->pdo = $pdo;
    }

    /** Vista principal (HTML) */
    public function index() {
        // Carga la vista sin datos (se piden por fetch desde el front)
        include PRIVATE_PATH . '/modules/intra/admin/views/users.php';
    }

    /** API: datos JSON para la tabla (paginado/filtrado) */
    public function data() {
        header('Content-Type: application/json; charset=utf-8');

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
        ]);
    }

    /** API: actualizar rol */
    public function updateRole() {
        header('Content-Type: application/json; charset=utf-8');
        $id   = (int)($_POST['id']   ?? 0);
        $role = (string)($_POST['role'] ?? '');

        if ($id <= 0) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); return; }

        $model = new UserAdmin($this->pdo);
        $ok = $model->updateRole($id, $role);

        echo json_encode(['ok'=>$ok, 'msg'=>$ok?'Actualizado':'No se pudo actualizar']);
    }
}
