<?php
class HomeController {
    public function index() {

        // Cargar el controlador del módulo Learn
        require_once PRIVATE_PATH . '/modules/learn/controllers/LearnController.php';

        // Obtener el HTML del módulo Learn
        $learnSection = (new LearnController())->render();

        // Pasar a la vista principal
        include PRIVATE_PATH . '/views/home.php';
    }
}

