<?php

class HomeController {
    public function index() {

        require_once PRIVATE_PATH . '/modules/learn/controllers/LearnController.php';
        $learnSection = (new LearnController())->render();

        require_once PRIVATE_PATH . '/modules/forum/controllers/ForumController.php';
        $forumSection = (new ForumController())->render();

        require_once PRIVATE_PATH . '/modules/community/controllers/CommunityController.php';
        $communitySection = (new CommunityController())->render();

        require_once PRIVATE_PATH . '/modules/legal/controllers/LegalController.php';
        $legalSection = (new LegalController())->render();

        require_once PRIVATE_PATH . '/modules/intra/myspace/controllers/MySpaceController.php';
        $mySpaceSection = (new MySpaceController())->render();

        $adminSection = '';
        if ($this->userIsAdmin()) {
            require_once PRIVATE_PATH . '/modules/intra/admin/controllers/AdminController.php';
            $adminSection = (new AdminController())->render();
        }

        include PRIVATE_PATH . '/views/home.php';
    }

    private function userIsAdmin(): bool {
        global $pdo;

        $userId   = $_SESSION['user_id'] ?? null;
        $userName = $GLOBALS['user']
                ?? ($_SESSION['email'] ?? $_SESSION['user'] ?? $_SERVER['AUTH_USER'] ?? $_SERVER['REMOTE_USER'] ?? null);

        if ($userName && strpos($userName, '\\') !== false) {
            $userName = substr($userName, strrpos($userName, '\\') + 1);
        }

        try {
            if ($userId) {
                $stmt = $pdo->prepare("SELECT roles FROM [user] WHERE id = :id");
                $stmt->execute([':id' => $userId]);
            } elseif (!empty($userName)) {
                $stmt = $pdo->prepare("SELECT roles FROM [user] WHERE usuario = :usuario");
                $stmt->execute([':usuario' => $userName]);
            }
            if (isset($stmt)) {
                $role = $stmt->fetchColumn();
                if ($role !== false) {
                    $v = strtolower(trim((string)$role));
                    return ($v === 'admin' || $v === '1' || $v === 'true');
                }
            }
        } catch (Throwable $e) {
            // log si quieres
        }
        return false;
    }
}
