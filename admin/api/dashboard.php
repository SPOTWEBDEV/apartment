<?php
require_once __DIR__ . '/_boot.php';
$today = date('Y-m-d'); $ms = date('Y-m-01'); $pms = date('Y-m-01', strtotime('-1 month')); $nms = date('Y-m-01', strtotime('+1 month'));
$rev = fn($a, $b) => (int)val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND paid_at>=? AND paid_at<?", [$a, $b]);
$cnt = fn($a, $b) => (int)val("SELECT COUNT(*) FROM bookings WHERE status!='cancelled' AND created_at>=? AND created_at<?", [$a, $b]);

// Occupancy over the next 30 nights
$end = date('Y-m-d', strtotime('+30 days')); $used = 0;
foreach (rows("SELECT checkin, checkout FROM bookings WHERE status!='cancelled' AND checkin<? AND checkout>?", [$end, $today]) as $b) {
  $from = max(strtotime($b['checkin']), strtotime($today)); $to = min(strtotime($b['checkout']), strtotime($end));
  $used += max(0, (int)round(($to - $from) / 86400));
}
$cap = (int)val('SELECT COALESCE(SUM(units),0) FROM rooms WHERE active=1') * 30;

$outstanding = (int)val("SELECT COALESCE(SUM(b.total - COALESCE(p.paid,0)),0) FROM bookings b LEFT JOIN " . PAID_SUB . " p ON p.booking_id=b.id WHERE b.status IN ('confirmed','checked_in','completed') AND b.total > COALESCE(p.paid,0)");

$start = date('Y-m-d', strtotime('-29 days'));
$byDay = array_column(rows("SELECT paid_at d, SUM(amount) s FROM payments WHERE status='paid' AND paid_at>=? GROUP BY paid_at", [$start]), 's', 'd');
$bkDay = array_column(rows("SELECT substr(created_at,1,10) d, COUNT(*) c FROM bookings WHERE status!='cancelled' AND substr(created_at,1,10)>=? GROUP BY d", [$start]), 'c', 'd');
$series = [];
for ($i = 0; $i < 30; $i++) { $d = date('Y-m-d', strtotime("$start +$i days")); $series[] = ['d'=>$d, 'revenue'=>(int)($byDay[$d] ?? 0), 'bookings'=>(int)($bkDay[$d] ?? 0)]; }

$status = array_column(rows('SELECT status, COUNT(*) c FROM bookings GROUP BY status'), 'c', 'status');
$def = (bool)val('SELECT initial_pw FROM users WHERE id=?', [admin_user()['id']]);

respond(['ok'=>true,
  'kpi'=>['revenue'=>$rev($ms, $nms), 'revenue_prev'=>$rev($pms, $ms), 'bookings'=>$cnt($ms, $nms), 'bookings_prev'=>$cnt($pms, $ms),
          'occupancy'=>$cap ? (int)round(100 * $used / $cap) : 0, 'outstanding'=>$outstanding, 'pending'=>(int)($status['pending'] ?? 0)],
  'series'=>$series, 'status'=>$status, 'default_password'=>$def,
  'pending'=>rows("SELECT id, ref, name, room_name, checkin, checkout, total FROM bookings WHERE status='pending' ORDER BY id DESC LIMIT 5"),
  'arrivals'=>rows("SELECT id, ref, name, room_name, checkin, nights, guests, status FROM bookings WHERE status IN ('pending','confirmed') AND checkin>=? AND checkin<=? ORDER BY checkin LIMIT 6", [$today, date('Y-m-d', strtotime('+7 days'))]),
  'payments'=>rows("SELECT p.id, p.amount, p.method, p.paid_at, b.name, b.ref FROM payments p JOIN bookings b ON b.id=p.booking_id WHERE p.status='paid' ORDER BY p.id DESC LIMIT 5"),
]);
