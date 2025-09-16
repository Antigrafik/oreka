<?php
// Paso 1: habilitar buffer de salida y fijar cabecera global
ob_start();
if (!headers_sent()) {
  header('Content-Type: text/html; charset=utf-8');
}

// Rutas absolutas
define('BASE_PATH', dirname(__DIR__));
define('PRIVATE_PATH', BASE_PATH . '/private');

// A partir de aquí ya puedes incluir usando PRIVATE_PATH
require_once PRIVATE_PATH . '/config/config.php';

require PRIVATE_PATH . '/includes/header.php';
require PRIVATE_PATH . '/includes/topbar.php';
require PRIVATE_PATH . '/includes/menu.php';
require PRIVATE_PATH . '/app/Router.php';
require PRIVATE_PATH . '/includes/footer.php';
require PRIVATE_PATH . '/includes/close.php';

