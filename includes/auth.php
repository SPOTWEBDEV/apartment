<?php
declare(strict_types=1);
function admin_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_name('cl_admin');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']), 'path' => '/']);
    session_start();
  }
}
function admin_user(): ?array { admin_session(); return $_SESSION['admin'] ?? null; }
function require_admin(): array {
  $u = admin_user();
  if (!$u) { header('Location: login.php'); exit; }
  return $u;
}
function csrf_token(): string { admin_session(); return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_ok(): bool {
  admin_session();
  $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
  return is_string($t) && hash_equals((string)($_SESSION['csrf'] ?? ''), $t);
}
