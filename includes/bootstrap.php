<?php
declare(strict_types=1);
require_once __DIR__ . '/env.php';
error_reporting(E_ALL);
ini_set('display_errors', env_bool('APP_DEBUG') ? '1' : '0');
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/db.php';
$GLOBALS['CFG'] = require __DIR__ . '/config.php';

/** Values saved in Admin > Settings override the defaults in config.php. */
function settings_all(): array {
  static $s = null;
  if ($s === null) {
    $s = [];
    try { foreach (db()->query('SELECT k, v FROM settings') as $r) $s[$r['k']] = (string)$r['v']; } catch (Throwable $e) {}
  }
  return $s;
}
(function () {
  $o = settings_all(); $c = &$GLOBALS['CFG'];
  foreach (['site_name','tagline','email','phone','phone_tel','whatsapp','check_in','check_out','currency','max_nights'] as $k)
    if (isset($o[$k]) && $o[$k] !== '') $c[$k] = $o[$k];
  if (!empty($o['address'])) $c['address'] = array_values(array_filter(array_map('trim', explode("\n", $o['address']))));
  foreach (['facebook','instagram'] as $k) if (isset($o[$k]) && $o[$k] !== '') $c['social'][$k] = $o[$k];
})();

/** Rooms from the database (falls back to data.php if SQLite is unavailable). */
function rooms_all(bool $activeOnly = true): array {
  try {
    $rows = db()->query('SELECT * FROM rooms' . ($activeOnly ? ' WHERE active=1' : '') . ' ORDER BY featured DESC, created_at, id')->fetchAll();
  } catch (Throwable $e) { $d = require __DIR__ . '/data.php'; return $d['rooms']; }
  $gal = [];
  try { foreach (db()->query('SELECT room_id, file, caption FROM room_images ORDER BY sort, id') as $i) $gal[$i['room_id']][] = ['file' => $i['file'], 'caption' => $i['caption']]; } catch (Throwable $e) {}
  foreach ($rows as &$r) {
    $r['images'] = $gal[$r['id']] ?? [];
    if ($r['images']) $r['img'] = $r['images'][0]['file'];
    $r['amenities'] = json_decode($r['amenities'] ?: '[]', true) ?: [];
    foreach (['guests','units','price','featured','active'] as $k) $r[$k] = (int)$r[$k];
  }
  return $rows;
}

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

require_once __DIR__ . '/mail.php';
