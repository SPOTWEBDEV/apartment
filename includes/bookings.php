<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

/** Returns [errorMessage|null, ['checkin','checkout','guests','nights']] */
function validate_stay(array $in): array {
  $a = DateTime::createFromFormat('!Y-m-d', (string)($in['checkin'] ?? ''));
  $b = DateTime::createFromFormat('!Y-m-d', (string)($in['checkout'] ?? ''));
  $guests = max(1, min(30, (int)($in['guests'] ?? 2)));
  if (!$a || !$b) return ['Choose a check-in and check-out date.', []];
  if ($a < new DateTime('today')) return ['Check-in cannot be in the past.', []];
  if ($b <= $a) return ['Check-out must be after check-in.', []];
  $n = (int)$a->diff($b)->days; $max = (int)cfg('max_nights', 30);
  if ($n > $max) return ["Online requests are limited to $max nights. Contact us for longer stays.", []];
  return [null, ['checkin'=>$a->format('Y-m-d'), 'checkout'=>$b->format('Y-m-d'), 'guests'=>$guests, 'nights'=>$n]];
}

function room_used(PDO $pdo, string $roomId, string $in, string $out, int $excludeId = 0): int {
  $s = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id=? AND status!='cancelled' AND checkin<? AND checkout>? AND id!=?");
  $s->execute([$roomId, $out, $in, $excludeId]);
  return (int)$s->fetchColumn();
}

function rooms_available(array $rooms, array $q): array {
  $pdo = db(); $out = [];
  foreach ($rooms as $r) {
    if ($r['guests'] < $q['guests']) continue;
    $left = (int)$r['units'] - room_used($pdo, $r['id'], $q['checkin'], $q['checkout']);
    if ($left < 1) continue;
    $out[] = ['id'=>$r['id'],'name'=>$r['name'],'blurb'=>$r['blurb'],'img'=>$r['img'],'guests'=>$r['guests'],
      'nightly'=>$r['price'],'total'=>$r['price'] * $q['nights'],'left'=>$left];
  }
  return $out;
}

function discount_find(string $code): ?array {
  $s = db()->prepare('SELECT * FROM discounts WHERE code=?'); $s->execute([strtoupper(trim($code))]);
  return $s->fetch() ?: null;
}
/** Returns [errorMessage|null, amountOff] */
function discount_eval(?array $d, int $nights, int $subtotal): array {
  if (!$d || !(int)$d['active']) return ['That code is not valid.', 0];
  $t = date('Y-m-d');
  if ($d['starts'] && $t < $d['starts']) return ['That code is not active yet.', 0];
  if ($d['ends'] && $t > $d['ends']) return ['That code has expired.', 0];
  if ((int)$d['min_nights'] > $nights) return ["This code needs a stay of at least {$d['min_nights']} nights.", 0];
  if ((int)$d['max_uses'] > 0 && (int)$d['used'] >= (int)$d['max_uses']) return ['That code has been fully used.', 0];
  $amt = $d['type'] === 'percent' ? (int)round($subtotal * min(100, (int)$d['value']) / 100) : min((int)$d['value'], $subtotal);
  return [null, $amt];
}

/** Atomic availability check + insert (BEGIN IMMEDIATE takes the write lock first). */
function booking_create(array $room, array $q, array $guest, string $code = '', string $status = 'pending', string $source = 'web'): array {
  $pdo = db(); $open = false;
  $subtotal = (int)$room['price'] * $q['nights']; $disc = 0; $dcode = '';
  try {
    $pdo->exec('BEGIN IMMEDIATE'); $open = true;
    if (room_used($pdo, $room['id'], $q['checkin'], $q['checkout']) >= (int)$room['units']) { $pdo->exec('ROLLBACK'); return ['ok'=>false,'reason'=>'unavailable']; }
    if ($code !== '') {
      $d = discount_find($code); [$err, $disc] = discount_eval($d, $q['nights'], $subtotal);
      if ($err) { $pdo->exec('ROLLBACK'); return ['ok'=>false,'reason'=>'discount','error'=>$err]; }
      $dcode = $d['code'];
    }
    do { $ref = 'CL-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
         $c = $pdo->prepare('SELECT 1 FROM bookings WHERE ref=?'); $c->execute([$ref]); } while ($c->fetch());
    $pdo->prepare('INSERT INTO bookings(ref,room_id,room_name,checkin,checkout,nights,guests,name,email,phone,notes,subtotal,discount_code,discount_amount,total,status,source,created_at,token) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$ref,$room['id'],$room['name'],$q['checkin'],$q['checkout'],$q['nights'],$q['guests'],$guest['name'],$guest['email'] ?? '',$guest['phone'] ?? '',$guest['notes'] ?? '',$subtotal,$dcode,$disc,$subtotal - $disc,$status,$source,date('Y-m-d H:i:s'),bin2hex(random_bytes(16))]);
    $id = (int)$pdo->lastInsertId();
    if ($dcode) $pdo->prepare('UPDATE discounts SET used=used+1 WHERE code=?')->execute([$dcode]);
    $pdo->exec('COMMIT'); $open = false;
    return ['ok'=>true, 'booking'=>$pdo->query("SELECT * FROM bookings WHERE id=$id")->fetch()];
  } catch (Throwable $e) {
    if ($open) { try { $pdo->exec('ROLLBACK'); } catch (Throwable $x) {} }
    return ['ok'=>false,'reason'=>'storage'];
  }
}

function booking_paid(int $id): int {
  $s = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE booking_id=? AND status='paid'"); $s->execute([$id]);
  return (int)$s->fetchColumn();
}

function notify_guest(array $b, string $status): void {
  if (empty($b['email'])) return;
  $site = cfg('site_name');
  $msg = ['confirmed' => "Good news: your stay is confirmed.", 'cancelled' => "Your booking request has been cancelled. Contact us if this is a surprise."][$status] ?? '';
  if ($msg === '') return;
  send_mail($b['email'], "$site booking {$b['ref']}: $status",
    "Hello {$b['name']},

$msg

Reference: {$b['ref']}
Room: {$b['room_name']}
Check-in: {$b['checkin']}
Check-out: {$b['checkout']}

$site", cfg('email'));
}
