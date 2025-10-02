<?php
declare(strict_types=1);

class MySpaceController
{
    public function render(): string
    {
        require_once PRIVATE_PATH . '/modules/intra/myspace/models/MySpace.php';
        require_once PRIVATE_PATH . '/modules/intra/myspace/models/Activity.php';
        require_once PRIVATE_PATH . '/modules/intra/myspace/models/LearnHistory.php';
        require_once PRIVATE_PATH . '/modules/intra/myspace/models/ForumHistory.php';
        require_once PRIVATE_PATH . '/modules/intra/myspace/models/RecommendationsHistory.php';
        require_once PRIVATE_PATH . '/modules/intra/myspace/models/RoutinesHistory.php';
        require_once PRIVATE_PATH . '/modules/intra/myspace/models/TrialsHistory.php';
        require_once PRIVATE_PATH . '/modules/intra/myspace/models/MeetingHistory.php';

        $uid = $this->currentUserId();

        // === FLAGS de visibilidad para historiales (por defecto: true) ===
        global $pdo;
        $msFlags = [
            'learn'            => true,
            'forum'            => true,
            'recommendations'  => true,
            'routines'         => true,
            'trial'            => true,
            'meeting'          => true,
        ];
        try {
            $st = $pdo->query("
                SELECT module_key, CONVERT(INT, show_module) AS show_module
                FROM [module_toggle]
                WHERE module_key IN ('learn','forum','recommendations','routines','trial','meeting')
            ");
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $msFlags[$r['module_key']] = ((int)$r['show_module'] === 1);
            }
        } catch (\Throwable $e) {
            // si falla, se quedan en true
        }

        // Datos (a futuro)
        $activity = $learn = $forum = $recs = $routines = $trials = $meetings = [];

        ob_start();
        include PRIVATE_PATH . '/modules/intra/myspace/views/myspace.php';
        return ob_get_clean();
    }

    private function currentUserId(): ?int
    {
        return !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}
