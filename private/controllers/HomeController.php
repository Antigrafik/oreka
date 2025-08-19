<?php

class HomeController {
    public function index() {

        require_once PRIVATE_PATH . '/modules/learn/controllers/LearnController.php';
        $learnSection = (new LearnController())->render();

        require_once PRIVATE_PATH . '/modules/forum/controllers/ForumController.php';
        $forumSection = (new ForumController())->render();

        //require_once PRIVATE_PATH . '/modules/community/controllers/CommunityController.php';
        //$communitySection = (new CommunityController())->render();

        include PRIVATE_PATH . '/views/home.php';
    }
}

