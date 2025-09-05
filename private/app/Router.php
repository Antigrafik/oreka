<?php

$uri = trim(parse_url($_GET['url'], PHP_URL_PATH), '/');


switch ($uri) {

    case 'home':
        require_once PRIVATE_PATH . '/controllers/HomeController.php';
        (new HomeController())->index();
        break;

    case 'mi-espacio':
        require_once PRIVATE_PATH . '/modules/intra/controllers/UserController.php';
        (new UserController())->index();
        break;

    /*case 'admin':
        require_once PRIVATE_PATH . '/modules/intra/controllers/AdminController.php';
        (new AdminController())->index();
        break;*/
    
    case 'store':
        require_once PRIVATE_PATH . '/modules/store/controllers/ProductController.php';
        (new ProductController())->index();
        break;


    default:
        require_once PRIVATE_PATH . '/controllers/HomeController.php';
        (new HomeController())->index();
        break;
}



