<?php
// Constantes base (si no vienen ya definidas)
if (!defined('PRIVATE_PATH')) {
  define('PRIVATE_PATH', dirname(__DIR__)); // .../private
}
if (!defined('BASE_PATH')) {
  define('BASE_PATH', dirname(PRIVATE_PATH));
}

$baseUrl = 'http://localhost';

// --- Idioma / i18n ---
if (!defined('DEFAULT_LANG')) {

    //IMPORTANTE!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! CAMBIAR
  define('DEFAULT_LANG', 'es'); //AQUI SE CAMBIA EL IDIOMA, CUANDO AVERIGÜEMOS CÓMO COGERLO DE SU INTRANET, ENLAZAR AQUÍ
  
}
if (!defined('SUPPORTED_LANGS')) {
  define('SUPPORTED_LANGS', ['es', 'eu']);
}

$langFile = PRIVATE_PATH . '/lang/' . DEFAULT_LANG . '.php';
$language = is_file($langFile) ? include $langFile : [];

// Includes con rutas absolutas y seguras
require_once __DIR__ . '/db_connect.php';

// Resto de configuración global
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$_GET['url'] = $requestUri;

$globalConfig = [
    'site_title' => 'Oreka',
    'favicon'    => '',
    'stylesheet' => '/assets/css/main.css',
    'script'     => [],
    'base_url'   => $baseUrl,
];
