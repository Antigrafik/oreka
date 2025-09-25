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

        //$kpis = (new MySpace())->getKpis($uid); // ['total'=>..., 'month'=>...]
        $activity = [];        // (new Activity())->list($uid, 10, 0);
        $learn    = [];        // (new LearnHistory())->list($uid);
        $forum    = [];        // (new ForumHistory())->list($uid);
        $recs     = [];        // (new RecommendationsHistory())->list($uid);
        $routines = [];        // (new RoutinesHistory())->list($uid);
        $trials   = [];        // (new TrialsHistory())->list($uid);
        $meetings = [];        // (new MeetingHistory())->list($uid);

        // variables disponibles en la vista principal
        ob_start();
        include PRIVATE_PATH . '/modules/intra/myspace/views/myspace.php';
        return ob_get_clean();
    }

    /** Obtiene el id del usuario autenticado, o null si no hay. */
    private function currentUserId(): ?int
    {
        // Adapta a tu sistema de login
        if (!empty($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }
        return null;
    }
}
