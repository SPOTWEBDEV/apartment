<?php
declare(strict_types=1);
require_once __DIR__ . '/../../api/_bootstrap.php';   // JSON headers, respond(), clean()
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/bookings.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_session();
if (!admin_user()) respond(['ok'=>false,'error'=>'Your session expired. Sign in again.','login'=>true], 401);
$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0)
  respond(['ok'=>false,'error'=>'That upload is larger than the server allows (' . ini_get('post_max_size') . '). Try a smaller photo.'], 413);
if ($action !== 'export') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['ok'=>false,'error'=>'POST required.'], 405);
  if (!csrf_ok()) respond(['ok'=>false,'error'=>'Security token expired. Refresh the page.'], 419);
}
$pdo = db();
function p(string $k, string $d = ''): string { return trim((string)($_POST[$k] ?? $_GET[$k] ?? $d)); }
function pint(string $k, int $d = 0): int { return (int)($_POST[$k] ?? $_GET[$k] ?? $d); }
function rows(string $sql, array $a = []): array { global $pdo; $s = $pdo->prepare($sql); $s->execute($a); return $s->fetchAll(); }
function row(string $sql, array $a = []): ?array { return rows($sql, $a)[0] ?? null; }
function val(string $sql, array $a = []) { global $pdo; $s = $pdo->prepare($sql); $s->execute($a); return $s->fetchColumn(); }
function pay_status(int $total, int $paid): string { return $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'); }
const PAID_SUB = "(SELECT booking_id, SUM(amount) paid FROM payments WHERE status='paid' GROUP BY booking_id)";
