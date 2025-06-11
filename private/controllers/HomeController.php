<?php

class HomeController {
    public function index() {

        // 🔹 Incluir vistas con los datos cargados
        include PRIVATE_PATH . '/views/home.php';

        // 🔹 Cargar modelos
        require_once PRIVATE_PATH . '/modules/banner/models/Banner.php';
        require_once PRIVATE_PATH . '/modules/learn/models/Learn.php';
        require_once PRIVATE_PATH . '/modules/forum/models/Forum.php';
        require_once PRIVATE_PATH . '/modules/community/models/Community.php';

        // 🔹 Obtener datos
        $banners = (new Banner())->getAll();
        $learns = (new Learn())->getAll();
        $forums = (new Forum())->getAll();
        $communities = (new Community())->getAll();

    }
}

