<?php
declare(strict_types=1);
// Flat-file booking store (storage/bookings.jsonl). Swap these functions for PDO/MySQL queries later;
// the API files only call the functions below, so nothing else has to change.
date_default_timezone_set('Asia/Manila');

function bookings_path(): string { return __DIR__ . '/../storage/bookings.jsonl'; }

function bookings_parse(string $raw): array {
  $out = [];
  foreach (explode("\n", trim($raw)) as $line) {
    $r = json_decode($line, true);
    if (is_array($r)) $out[] = $r;
  }
  return $out;
}

/** Returns [errorMessage|null, ['checkin','checkout','guests','nights']] */
function validate_stay(array $in): array {
  $a = DateTime::createFromFormat('!Y-m-d', (string)($in['checkin'] ?? ''));
  $b = DateTime::createFromFormat('!Y-m-d', (string)($in['checkout'] ?? ''));
  $guests = max(1, min(30, (int)($in['guests'] ?? 2)));
  if (!$a || !$b) return ['Choose a check-in and check-out date.', []];
  if ($a < new DateTime('today')) return ['Check-in cannot be in the past.', []];
  if ($b <= $a) return ['Check-out must be after check-in.', []];
  $n = (int)$a->diff($b)->days;
  if ($n > 30) return ['Online requests are limited to 30 nights. Contact us for longer stays.', []];
  return [null, ['checkin'=>$a->format('Y-m-d'), 'checkout'=>$b->format('Y-m-d'), 'guests'=>$guests, 'nights'=>$n]];
}

function room_left(array $room, array $bookings, string $in, string $out): int {
  $used = 0;
  foreach ($bookings as $b) {
    if (($b['room'] ?? '') === $room['id'] && ($b['status'] ?? 'pending') !== 'cancelled'
        && $b['checkin'] < $out && $b['checkout'] > $in) $used++;
  }
  return max(0, (int)($room['units'] ?? 1) - $used);
}

function rooms_available(array $rooms, array $q): array {
  $bk = is_file(bookings_path()) ? bookings_parse((string)file_get_contents(bookings_path())) : [];
  $out = [];
  foreach ($rooms as $r) {
    if ($r['guests'] < $q['guests']) continue;
    $left = room_left($r, $bk, $q['checkin'], $q['checkout']);
    if ($left < 1) continue;
    $out[] = ['id'=>$r['id'],'name'=>$r['name'],'blurb'=>$r['blurb'],'img'=>$r['img'],'guests'=>$r['guests'],
      'nightly'=>$r['price'],'total'=>$r['price'] * $q['nights'],'left'=>$left];
  }
  return $out;
}

/** Atomic check-and-insert so two visitors cannot take the last unit at once. */
function booking_create(array $room, array $q, array $guest): array {
  $fh = @fopen(bookings_path(), 'c+');
  if (!$fh) return ['ok'=>false, 'reason'=>'storage'];
  flock($fh, LOCK_EX);
  $bk = bookings_parse((string)stream_get_contents($fh));
  if (room_left($room, $bk, $q['checkin'], $q['checkout']) < 1) {
    flock($fh, LOCK_UN); fclose($fh);
    return ['ok'=>false, 'reason'=>'unavailable'];
  }
  $rec = [
    'ref' => 'CL-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2))),
    'at' => date('c'), 'status' => 'pending',
    'room' => $room['id'], 'room_name' => $room['name'],
    'checkin' => $q['checkin'], 'checkout' => $q['checkout'], 'nights' => $q['nights'], 'guests' => $q['guests'],
    'total' => $room['price'] * $q['nights'],
  ] + $guest;
  fseek($fh, 0, SEEK_END);
  fwrite($fh, json_encode($rec, JSON_UNESCAPED_UNICODE) . "\n");
  fflush($fh); flock($fh, LOCK_UN); fclose($fh);
  return ['ok'=>true, 'booking'=>$rec];
}
