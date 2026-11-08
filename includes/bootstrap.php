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
  foreach (['site_name','tagline','email','phone','phone_tel','messenger','payment_instructions','check_in','check_out','currency','max_nights'] as $k)
    if (isset($o[$k]) && $o[$k] !== '') $c[$k] = $o[$k];
  if (!empty($o['address'])) $c['address'] = array_values(array_filter(array_map('trim', explode("\n", $o['address']))));
  foreach (['facebook','instagram'] as $k) if (isset($o[$k]) && $o[$k] !== '') $c['social'][$k] = $o[$k];
})();

/** Rooms from the database (falls back to data.php if SQLite is unavailable). */
function rooms_all(bool $activeOnly = true): array {
  try {
    $rows = db()->query('SELECT * FROM rooms' . ($activeOnly ? ' WHERE active=1' : '') . ' ORDER BY featured DESC, created_at, id')->fetchAll();
  } catch (Throwable $e) { return []; }
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
function room_img(string $f): string {
  return 'assets/img/' . (str_contains($f, '/') ? $f : 'rooms/' . $f);
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function peso($n): string { return cfg('currency', '₱') . number_format((float)$n); }
/** Accepts a page name, an m.me / facebook.com link or a numeric page id and returns just the handle ('' if invalid). */
function messenger_handle(string $v): string {
  $v = trim($v);
  if ($v === '') return '';
  if (preg_match('/profile\.php\?id=(\d+)/', $v, $m)) return $m[1];
  $v = (string)preg_replace('#^https?://#i', '', $v);
  $v = (string)preg_replace('#^(www\.|m\.)?(m\.me|facebook\.com|fb\.com|fb\.me|messenger\.com/t)/#i', '', $v);
  $v = trim(explode('?', explode('#', $v)[0])[0], '/ ');
  return preg_match('/^[A-Za-z0-9.\-_]{3,100}$/', $v) ? $v : '';
}
/** Link that opens a Messenger chat with the business page ('' until set in Admin > Settings). */
function messenger_url(): string {
  $raw = trim((string)cfg('messenger', ''));
  if ($raw === '') return '';
  $h = messenger_handle($raw);
  if ($h !== '') return 'https://m.me/' . rawurlencode($h);
  // Not a format messenger_handle() recognises: use what the admin typed.
  if (preg_match('#^https?://#i', $raw)) return $raw;                    // a full link
  if (preg_match('#^(m\.me|fb\.me|(www\.)?facebook\.com)/#i', $raw)) return 'https://' . $raw;
  return 'https://m.me/' . rawurlencode(ltrim($raw, '@/'));               // a plain page name
}
function maps_url(): string {
  return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(cfg('site_name') . ', ' . implode(', ', cfg('address', [])));
}

require_once __DIR__ . '/mail.php';
