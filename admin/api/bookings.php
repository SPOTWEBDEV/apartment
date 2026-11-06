<?php
require_once __DIR__ . '/_boot.php';

const FLOW = ['pending'=>['confirmed','cancelled'], 'confirmed'=>['checked_in','cancelled'], 'checked_in'=>['completed'], 'completed'=>[], 'cancelled'=>['pending']];

function decorate(array $b): array {
  $b['paid'] = (int)($b['paid'] ?? 0); $b['total'] = (int)$b['total'];
  $b['balance'] = max(0, $b['total'] - $b['paid']); $b['pay_status'] = pay_status($b['total'], $b['paid']);
  return $b;
}

switch ($action) {
  case 'list':
    $status = p('status', 'all'); $q = p('q'); $page = max(1, pint('page', 1)); $per = 12;
    $w = []; $a = [];
    if (isset(FLOW[$status])) { $w[] = 'b.status=?'; $a[] = $status; }
    if ($q !== '') { $w[] = '(b.name LIKE ? OR b.ref LIKE ? OR b.email LIKE ? OR b.phone LIKE ?)'; $l = "%$q%"; array_push($a, $l, $l, $l, $l); }
    $where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
    $total = (int)val("SELECT COUNT(*) FROM bookings b $where", $a);
    $list = rows("SELECT b.*, COALESCE(p.paid,0) paid FROM bookings b LEFT JOIN " . PAID_SUB . " p ON p.booking_id=b.id $where ORDER BY b.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $a);
    respond(['ok'=>true, 'bookings'=>array_map('decorate', $list), 'total'=>$total, 'page'=>$page, 'per'=>$per, 'pages'=>max(1, (int)ceil($total / $per)),
             'counts'=>array_column(rows('SELECT status, COUNT(*) c FROM bookings GROUP BY status'), 'c', 'status')]);

  case 'get':
    $b = row('SELECT b.*, COALESCE(p.paid,0) paid FROM bookings b LEFT JOIN ' . PAID_SUB . ' p ON p.booking_id=b.id WHERE b.id=?', [pint('id')]);
    if (!$b) respond(['ok'=>false,'error'=>'Booking not found.'], 404);
    respond(['ok'=>true, 'booking'=>decorate($b), 'payments'=>rows('SELECT * FROM payments WHERE booking_id=? ORDER BY id DESC', [$b['id']]), 'next'=>FLOW[$b['status']] ?? []]);

  case 'status':
    $b = row('SELECT * FROM bookings WHERE id=?', [pint('id')]); $to = p('status');
    if (!$b) respond(['ok'=>false,'error'=>'Booking not found.'], 404);
    if (!in_array($to, FLOW[$b['status']] ?? [], true)) respond(['ok'=>false,'error'=>"A {$b['status']} booking cannot move to $to."], 422);
    if ($b['status'] === 'cancelled') { // reopening must not double-book
      $room = row('SELECT units FROM rooms WHERE id=?', [$b['room_id']]);
      if (!$room || room_used($pdo, $b['room_id'], $b['checkin'], $b['checkout'], (int)$b['id']) >= (int)$room['units'])
        respond(['ok'=>false,'error'=>'That room is no longer free for those dates, so the booking cannot be reopened.'], 409);
    }
    $pdo->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$to, $b['id']]);
    notify_guest($b, $to);
    respond(['ok'=>true]);

  case 'notes':
    $pdo->prepare('UPDATE bookings SET admin_notes=? WHERE id=?')->execute([clean(p('admin_notes'), 2000), pint('id')]);
    respond(['ok'=>true]);

  case 'rooms':
    respond(['ok'=>true, 'rooms'=>array_map(fn($r) => ['id'=>$r['id'],'name'=>$r['name'],'price'=>$r['price'],'guests'=>$r['guests']], rooms_all(true))]);

  case 'create': // phone, walk-in and manual bookings
    [$err, $q] = validate_stay($_POST);
    if ($err) respond(['ok'=>false,'error'=>$err], 422);
    $room = null; foreach (rooms_all(true) as $r) if ($r['id'] === p('room')) $room = $r;
    if (!$room) respond(['ok'=>false,'error'=>'Choose a room.'], 422);
    if ($q['guests'] > $room['guests']) respond(['ok'=>false,'error'=>"This room sleeps up to {$room['guests']} guests."], 422);
    $name = clean(p('name'), 120); $email = trim(p('email'));
    if ($name === '') respond(['ok'=>false,'error'=>'Enter the guest name.'], 422);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) respond(['ok'=>false,'error'=>'Enter a valid email address or leave it empty.'], 422);
    $st = in_array(p('status'), ['pending','confirmed'], true) ? p('status') : 'confirmed';
    $res = booking_create($room, $q, ['name'=>$name,'email'=>$email,'phone'=>clean(p('phone'), 40),'notes'=>clean(p('notes'), 1000)], strtoupper(clean(p('code'), 30)), $st, 'admin');
    if (!$res['ok']) respond(['ok'=>false,'error'=>['unavailable'=>'That room is fully booked for those dates.','discount'=>$res['error'] ?? '','storage'=>'Could not save the booking.'][$res['reason']]], 409);
    if ($st === 'confirmed') notify_guest($res['booking'], 'confirmed');
    respond(['ok'=>true, 'id'=>(int)$res['booking']['id'], 'ref'=>$res['booking']['ref']]);
}
respond(['ok'=>false,'error'=>'Unknown action.'], 400);
