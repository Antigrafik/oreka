<?php
// Constantes base
if (!defined('PRIVATE_PATH')) {
  define('PRIVATE_PATH', dirname(__DIR__)); // .../private
}
if (!defined('BASE_PATH')) {
  define('BASE_PATH', dirname(PRIVATE_PATH));
}

$baseUrl = 'http://localhost';

// --- Idiomas soportados ---
if (!defined('SUPPORTED_LANGS')) {
  define('SUPPORTED_LANGS', ['es', 'eu']);
}
$localeMap = ['es' => 'es_ES', 'eu' => 'eu_ES'];

// --- Detección de idioma ---
// 1) URL ?lang=es_ES / eu_ES
$rawLang = $_GET['lang'] ?? null;

// 2) Cookie (si no viene en URL)
if (!$rawLang && !empty($_COOKIE['lang'])) {
  $rawLang = $_COOKIE['lang'];
}

// Normaliza: acepta "es", "es_ES", "eu", "eu_ES"
$currentLang = 'es'; // por defecto español
if (is_string($rawLang)) {
  // extrae las dos primeras letras válidas
  if (preg_match('/^(es|eu)(?:[_-][A-Za-z]{2})?$/i', $rawLang, $m)) {
    $candidate = strtolower($m[1]);
    if (in_array($candidate, SUPPORTED_LANGS, true)) {
      $currentLang = $candidate;
    }
  }
}

// Persiste en cookie (1 año) para próximas visitas
if (empty($_COOKIE['lang']) || $_COOKIE['lang'] !== $currentLang) {
  // Guarda el locale completo (es_ES / eu_ES) por comodidad
  $cookieLocale = $localeMap[$currentLang] ?? 'es_ES';
  setcookie('lang', $cookieLocale, [
    'expires'  => time() + 31536000,
    'path'     => '/',
    'secure'   => false,
    'httponly' => false,
    'samesite' => 'Lax',
  ]);
}

// Define constante del idioma actual (código corto)
if (!defined('DEFAULT_LANG')) {
  define('DEFAULT_LANG', $currentLang); // 'es' o 'eu'
}

// Carga del archivo de idioma
$langFile  = PRIVATE_PATH . '/lang/' . DEFAULT_LANG . '.php';
$language  = is_file($langFile) ? include $langFile : [];

// Conexión BD
require_once __DIR__ . '/db_connect.php';

// Resto de config global
$requestUri     = $_SERVER['REQUEST_URI'] ?? '/';
$_GET['url']    = $requestUri;
$globalConfig   = [
  'site_title' => 'Oreka',
  'favicon'    => '/assets/images/favicon/favicon.svg',
  'stylesheet' => '/assets/css/main.css',
  'script'     => ['/assets/js/main.js','/assets/js/menu.js', '/assets/js/learn-slider.js', '/assets/js/community-recomendation-filter.js', '/assets/js/admin_richtext.js'],
  'base_url'   => $baseUrl,
  'lang'       => DEFAULT_LANG,              // 'es' o 'eu'
  'locale'     => $localeMap[DEFAULT_LANG] ?? 'es_ES', // 'es_ES' o 'eu_ES'
];
