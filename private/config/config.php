<?php
if (!defined('PRIVATE_PATH')) {
  define('PRIVATE_PATH', dirname(__DIR__));
}
if (!defined('BASE_PATH')) {
  define('BASE_PATH', dirname(PRIVATE_PATH));
}

$baseUrl = 'http://localhost';

if (!defined('SUPPORTED_LANGS')) {
  define('SUPPORTED_LANGS', ['es', 'eu']);
}
$localeMap = ['es' => 'es_ES', 'eu' => 'eu_ES'];

$rawLang = $_GET['lang'] ?? null;

if (!$rawLang && !empty($_COOKIE['lang'])) {
  $rawLang = $_COOKIE['lang'];
}

$currentLang = 'es';
if (is_string($rawLang)) {
  if (preg_match('/^(es|eu)(?:[_-][A-Za-z]{2})?$/i', $rawLang, $m)) {
    $candidate = strtolower($m[1]);
    if (in_array($candidate, SUPPORTED_LANGS, true)) {
      $currentLang = $candidate;
    }
  }
}

if (empty($_COOKIE['lang']) || $_COOKIE['lang'] !== $currentLang) {
  $cookieLocale = $localeMap[$currentLang] ?? 'es_ES';
  setcookie('lang', $cookieLocale, [
    'expires'  => time() + 31536000,
    'path'     => '/',
    'secure'   => false,
    'httponly' => false,
    'samesite' => 'Lax',
  ]);
}

if (!defined('DEFAULT_LANG')) {
  define('DEFAULT_LANG', $currentLang);
}

$langFile  = PRIVATE_PATH . '/lang/' . DEFAULT_LANG . '.php';
$language  = is_file($langFile) ? include $langFile : [];

require_once __DIR__ . '/db_connect.php';

$requestUri     = $_SERVER['REQUEST_URI'] ?? '/';
$_GET['url']    = $requestUri;
$globalConfig   = [
  'site_title' => 'Oreka',
  'favicon'    => '/assets/images/favicon/favicon.svg',
  'stylesheet' => ['/assets/css/main.css', '/assets/css/all.min.css'],
  'script'     => ['/assets/js/menu.js', '/assets/js/main.js', '/assets/js/learn-slider.js', '/assets/js/calendar.js', '/assets/js/recommendation.js', '/assets/js/likes.js'],
  'base_url'   => $baseUrl,
  'lang'       => DEFAULT_LANG,
  'locale'     => $localeMap[DEFAULT_LANG] ?? 'es_ES',
];
