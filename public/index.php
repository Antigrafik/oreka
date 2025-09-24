<?php
// Paso 0: rutas base del proyecto (las necesitamos para el tmp)
$PROJECT_ROOT = dirname(__DIR__);

// Paso 0.1: forzar directorio temporal de subidas SOLO para esta petición (sin tocar IIS/php.ini)
$localTmp = $PROJECT_ROOT . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'upload_tmp';
if (!is_dir($localTmp)) {
  @mkdir($localTmp, 0775, true);
}
if (is_dir($localTmp) && is_writable($localTmp)) {
  ini_set('upload_tmp_dir', $localTmp);
}

// Paso 1: habilitar buffer de salida y fijar cabecera global
ob_start();
if (!headers_sent()) {
  header('Content-Type: text/html; charset=utf-8');
}

// Rutas absolutas (constantes)
define('BASE_PATH', $PROJECT_ROOT);
define('PRIVATE_PATH', BASE_PATH . '/private');

// A partir de aquí ya puedes incluir usando PRIVATE_PATH
require_once PRIVATE_PATH . '/config/config.php';
require PRIVATE_PATH . '/includes/header.php';
require PRIVATE_PATH . '/includes/topbar.php';
require PRIVATE_PATH . '/includes/menu.php';
require PRIVATE_PATH . '/app/Router.php';
require PRIVATE_PATH . '/includes/footer.php';
require PRIVATE_PATH . '/includes/close.php';

