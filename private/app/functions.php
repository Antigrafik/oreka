<?php
function current_lang(): string {
  return defined('DEFAULT_LANG') ? DEFAULT_LANG : 'es';
}

function locale_for(string $lang): string {
  return $lang === 'eu' ? 'eu_ES' : 'es_ES';
}

// Construye una URL añadiendo ?lang=xx_XX (o &lang=... si ya hay query)
function url_with_lang(string $url, ?string $lang = null): string {
  $lang   = $lang ?: current_lang();
  $locale = locale_for($lang);
  $sep    = (strpos($url, '?') !== false) ? '&' : '?';
  return $url . $sep . 'lang=' . $locale;
}
