
<?php

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$_GET['url'] = $requestUri;

define('PRIVATE_PATH', dirname(__DIR__) . '/private');

include PRIVATE_PATH . '/includes/header.php';
include PRIVATE_PATH . '/includes/topbar.php';
include PRIVATE_PATH . '/includes/menu.php';
//include PRIVATE_PATH . '/app/Router.php';
include PRIVATE_PATH . '/includes/footer.php';
include PRIVATE_PATH . '/includes/close.php';
