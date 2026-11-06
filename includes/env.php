<?php
declare(strict_types=1);
/**
 * Tiny .env loader. Looks for ".env" one folder ABOVE the project first (safest, outside the web root),
 * then in the project folder. Real server environment variables always win over the file.
 */
function env_load(): void {
  static $done = false;
  if ($done) return;
  $done = true;
  $GLOBALS['__ENV'] = [];
  foreach ([dirname(__DIR__, 2) . '/.env', dirname(__DIR__) . '/.env'] as $file) {
    if (!is_file($file) || !is_readable($file)) continue;
    foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
      $line = trim($line);
      if ($line === '' || $line[0] === '#') continue;
      if (str_starts_with($line, 'export ')) $line = substr($line, 7);
      if (!str_contains($line, '=')) continue;
      [$k, $v] = explode('=', $line, 2);
      $k = trim($k); $v = trim($v);
      if (preg_match('/^"(.*)"$/s', $v, $m)) $v = stripcslashes($m[1]);
      elseif (preg_match("/^'(.*)'$/s", $v, $m)) $v = $m[1];
      else $v = trim((string)preg_replace('/\s+#.*$/', '', $v));
      $GLOBALS['__ENV'][$k] = $v;
    }
    break;
  }
}
function env(string $key, $default = null) {
  env_load();
  $v = getenv($key);
  if ($v !== false && $v !== '') return $v;
  $v = $GLOBALS['__ENV'][$key] ?? null;
  return ($v === null || $v === '') ? $default : $v;
}
function env_bool(string $key, bool $default = false): bool {
  $v = env($key);
  return $v === null ? $default : in_array(strtolower((string)$v), ['1', 'true', 'yes', 'on'], true);
}
