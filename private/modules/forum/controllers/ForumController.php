<?php
require_once __DIR__ . '/../models/Forum.php';

class ForumController
{
    private function currentUserId(): ?int {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        // 1) Buscar en $_SESSION por rutas habituales
        $candidatos = [
            ['user','id'], ['usuario','id'], ['auth','id'], ['profile','id'],
            ['id_user'], ['user_id'], ['id'],
        ];
        foreach ($candidatos as $path) {
            $v = $_SESSION; $ok = true;
            foreach ($path as $k) { if (!is_array($v) || !array_key_exists($k,$v)) { $ok=false; break; } $v=$v[$k]; }
            if ($ok && (int)$v > 0) return (int)$v;
        }
        // 2) Si tienes $user en el topbar (valor de [user].usuario), resolvemos a id
        global $pdo;
        if (isset($GLOBALS['user']) && is_string($GLOBALS['user']) && trim($GLOBALS['user']) !== '' && $pdo) {
            $st = $pdo->prepare("SELECT TOP (1) id FROM [user] WHERE usuario = ?");
            $st->execute([trim($GLOBALS['user'])]);
            $id = (int)$st->fetchColumn();
            if ($id > 0) return $id;
        }
        return null;
    }

    public function render(): string
    {
        $lang = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = ($lang === 'es') ? 'eu' : 'es';
        $userId = $this->currentUserId();

        $mdl = new Forum();
        // 👇 PASAMOS EL userId para que calcule joined_by_user=1 cuando toque
        $events = $mdl->getAll($lang, $fallback, $userId);

        // Render de la vista
        ob_start();
        // si tu ruta es distinta, ajusta el include
        include __DIR__ . '/../views/forum.php';
        return ob_get_clean();
    }
}
