<?php
// Optional: fills the database with sample bookings, payments and a discount code so the admin looks alive.
// Run once from the project folder:  php tools/seed-demo.php
// Do NOT run this on a production database.
require __DIR__ . '/../includes/bookings.php';
if (PHP_SAPI !== 'cli') exit('Run this from the command line.');
$pdo = db(); $rooms = rooms_all(true);
$pdo->exec("INSERT OR IGNORE INTO discounts(code,label,type,value,min_nights,max_uses,active,created_at) VALUES('WELCOME10','Welcome offer','percent',10,0,0,1,'" . date('Y-m-d H:i:s') . "'),('LONGSTAY500','Long stay','fixed',500,3,50,1,'" . date('Y-m-d H:i:s') . "')");
$names = ['Maria Santos','Joel Reyes','Denise Tan','Marco Lim','Kristine Perez','Paolo Mendoza','Grace Villanueva','Ian Cruz','Bea Navarro','Rafael Dizon','Anna Ramos','Carlo Bautista'];
$methods = ['cash','bank','gcash','maya','card'];
mt_srand(7);
foreach ($names as $i => $n) {
  $room = $rooms[$i % count($rooms)]; $offset = mt_rand(-25, 40); $nights = mt_rand(1, 4);
  $in = date('Y-m-d', strtotime("$offset days")); $out = date('Y-m-d', strtotime("$in +$nights days"));
  $q = ['checkin'=>$in, 'checkout'=>$out, 'guests'=>min($room['guests'], mt_rand(1, 4)), 'nights'=>$nights];
  $status = $offset < -3 ? 'completed' : ($offset < 1 ? 'checked_in' : ['pending','confirmed','confirmed'][mt_rand(0, 2)]);
  $r = booking_create($room, $q, ['name'=>$n, 'email'=>strtolower(str_replace(' ', '.', $n)) . '@example.com', 'phone'=>'0917' . mt_rand(1000000, 9999999)], $i % 4 === 0 ? 'WELCOME10' : '', $status, $i % 5 === 0 ? 'admin' : 'web');
  if (!$r['ok']) continue;
  $b = $r['booking']; $paid = in_array($status, ['completed','checked_in','confirmed'], true) ? (mt_rand(0, 2) ? $b['total'] : intdiv($b['total'], 2)) : 0;
  if ($paid) $pdo->prepare('INSERT INTO payments(booking_id,amount,method,reference,status,paid_at,created_at) VALUES(?,?,?,?,?,?,?)')
      ->execute([$b['id'], $paid, $methods[mt_rand(0, 4)], 'REF' . mt_rand(10000, 99999), 'paid', date('Y-m-d', strtotime('-' . mt_rand(0, 25) . ' days')), date('Y-m-d H:i:s')]);
}
echo "Demo data added.\n";
