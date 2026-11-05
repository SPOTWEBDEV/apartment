<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(array $payload, int $code = 200): never {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}
function body(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '', true);
  return is_array($data) ? $data : [];
}
function require_method(string $m): void {
  if ($_SERVER['REQUEST_METHOD'] !== $m) respond(['ok'=>false,'error'=>'Method not allowed.'], 405);
}
function clean(?string $v, int $max = 200): string {
  return mb_substr(trim(strip_tags((string)$v)), 0, $max);
}
