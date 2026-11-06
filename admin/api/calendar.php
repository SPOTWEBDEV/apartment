<?php
require_once __DIR__ . '/_boot.php';

if ($action === 'range') {
  $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', p('from')) ? p('from') : date('Y-m-d');
  $n = max(7, min(62, pint('days', 30)));
  $to = date('Y-m-d', strtotime("$from +$n days"));
  $bookings = rows("SELECT b.id, b.ref, b.name, b.room_id, b.checkin, b.checkout, b.guests, b.status, b.total, COALESCE(p.paid,0) paid
                    FROM bookings b LEFT JOIN " . PAID_SUB . " p ON p.booking_id=b.id
                    WHERE b.status!='cancelled' AND b.checkin<? AND b.checkout>? ORDER BY b.checkin, b.id", [$to, $from]);
  foreach ($bookings as &$b) $b['pay_status'] = pay_status((int)$b['total'], (int)$b['paid']);
  $rooms = array_map(fn($r) => ['id'=>$r['id'],'name'=>$r['name'],'units'=>$r['units'],'price'=>$r['price'],'active'=>$r['active']], rooms_all(false));
  $today = date('Y-m-d');
  $cap = (int)val('SELECT COALESCE(SUM(units),0) FROM rooms WHERE active=1');
  respond(['ok'=>true, 'from'=>$from, 'days'=>$n, 'rooms'=>$rooms, 'bookings'=>$bookings, 'capacity'=>$cap, 'today'=>[
    'occupied'=>(int)val("SELECT COUNT(*) FROM bookings WHERE status!='cancelled' AND checkin<=? AND checkout>?", [$today, $today]),
    'arrivals'=>(int)val("SELECT COUNT(*) FROM bookings WHERE status IN ('pending','confirmed') AND checkin=?", [$today]),
    'departures'=>(int)val("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed','checked_in') AND checkout=?", [$today]),
  ]]);
}
respond(['ok'=>false,'error'=>'Unknown action.'], 400);
