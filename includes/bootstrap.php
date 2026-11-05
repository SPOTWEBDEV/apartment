<?php
declare(strict_types=1);
$GLOBALS['CFG'] = require __DIR__ . '/config.php';

/** cfg('email') or cfg('social.facebook') */
function cfg(string $key, $default = null) {
  $v = $GLOBALS['CFG'];
  foreach (explode('.', $key) as $k) {
    if (!is_array($v) || !array_key_exists($k, $v)) return $default;
    $v = $v[$k];
  }
  return $v;
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function peso($n): string { return cfg('currency', '₱') . number_format((float)$n); }
function whatsapp_url(string $text = ''): string {
  return 'https://wa.me/' . preg_replace('/\D/', '', (string)cfg('whatsapp')) . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}
function maps_url(): string {
  return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(cfg('site_name') . ', ' . implode(', ', cfg('address', [])));
}
