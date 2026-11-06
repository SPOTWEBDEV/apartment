<?php
require_once __DIR__ . '/_boot.php';

require_once __DIR__ . '/../../includes/crypto.php';
const METHODS = ['cash','bank','gcash','maya','card','crypto','other'];

function filters(): array {
  $w = []; $a = [];
  if (in_array(p('method'), METHODS, true)) { $w[] = 'p.method=?'; $a[] = p('method'); }
  if (in_array(p('status'), ['paid','refunded'], true)) { $w[] = 'p.status=?'; $a[] = p('status'); }
  if (preg_match('/^\d{4}-\d{2}$/', p('month'))) { $w[] = 'substr(p.paid_at,1,7)=?'; $a[] = p('month'); }
  if (p('q') !== '') { $w[] = '(b.name LIKE ? OR b.ref LIKE ? OR p.reference LIKE ?)'; $l = '%' . p('q') . '%'; array_push($a, $l, $l, $l); }
  return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $a];
}
const FROM = 'FROM payments p JOIN bookings b ON b.id=p.booking_id';

switch ($action) {
  case 'list':
    [$where, $a] = filters(); $page = max(1, pint('page', 1)); $per = 15;
    $total = (int)val("SELECT COUNT(*) " . FROM . " $where", $a);
    $list = rows("SELECT p.*, b.name, b.ref, b.room_name " . FROM . " $where ORDER BY p.paid_at DESC, p.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $a);
    $ms = date('Y-m-01'); $nms = date('Y-m-01', strtotime('+1 month')); $pms = date('Y-m-01', strtotime('-1 month'));
    $sum = fn($st, $f, $t) => (int)val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status=? AND paid_at>=? AND paid_at<?", [$st, $f, $t]);
    $out = (int)val("SELECT COALESCE(SUM(b.total - COALESCE(x.paid,0)),0) FROM bookings b LEFT JOIN " . PAID_SUB . " x ON x.booking_id=b.id WHERE b.status IN ('confirmed','checked_in','completed') AND b.total > COALESCE(x.paid,0)");
    respond(['ok'=>true, 'payments'=>$list, 'total'=>$total, 'page'=>$page, 'per'=>$per, 'pages'=>max(1, (int)ceil($total / $per)),
      'summary'=>['collected'=>$sum('paid', $ms, $nms), 'collected_prev'=>$sum('paid', $pms, $ms), 'refunded'=>$sum('refunded', $ms, $nms), 'outstanding'=>$out]]);

  case 'booking_search':
    $l = '%' . p('q') . '%';
    $list = rows("SELECT b.id, b.ref, b.name, b.room_name, b.total, COALESCE(x.paid,0) paid FROM bookings b LEFT JOIN " . PAID_SUB . " x ON x.booking_id=b.id
                  WHERE b.status!='cancelled' AND b.total > COALESCE(x.paid,0) AND (b.name LIKE ? OR b.ref LIKE ?) ORDER BY b.id DESC LIMIT 8", [$l, $l]);
    foreach ($list as &$b) $b['balance'] = (int)$b['total'] - (int)$b['paid'];
    respond(['ok'=>true, 'bookings'=>$list]);

  case 'create':
    $b = row("SELECT * FROM bookings WHERE id=?", [pint('booking_id')]);
    if (!$b) respond(['ok'=>false,'error'=>'Choose a booking.'], 422);
    if ($b['status'] === 'cancelled') respond(['ok'=>false,'error'=>'This booking is cancelled.'], 422);
    $amount = pint('amount'); $balance = (int)$b['total'] - booking_paid((int)$b['id']);
    if ($amount < 1) respond(['ok'=>false,'error'=>'Enter an amount greater than zero.'], 422);
    if ($amount > $balance) respond(['ok'=>false,'error'=>'That is more than the balance of ' . peso($balance) . '.'], 422);
    $method = in_array(p('method'), METHODS, true) ? p('method') : 'other';
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', p('paid_at')) ? p('paid_at') : date('Y-m-d');
    $coin = $network = $hash = ''; $camt = 0.0;
    if ($method === 'crypto') {
      $coin = strtoupper(p('coin')); $network = p('network'); $hash = p('tx_hash'); $camt = (float)p('crypto_amount');
      if (!isset(CRYPTO_NETWORKS[$coin]) || !in_array($network, CRYPTO_NETWORKS[$coin], true)) respond(['ok'=>false,'error'=>'Choose the coin and the network it was sent on.'], 422);
      if ($camt <= 0) respond(['ok'=>false,'error'=>'Enter the amount of crypto that was received.'], 422);
      if (!preg_match('/^[A-Za-z0-9]{10,128}$/', $hash)) respond(['ok'=>false,'error'=>'Enter the transaction hash (letters and numbers only, no spaces).'], 422);
      if (val('SELECT 1 FROM payments WHERE lower(tx_hash)=lower(?)', [$hash])) respond(['ok'=>false,'error'=>'That transaction hash is already recorded on another payment.'], 409);
    }
    $pdo->prepare('INSERT INTO payments(booking_id,amount,method,reference,note,status,paid_at,created_at,coin,network,tx_hash,crypto_amount) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$b['id'], $amount, $method, clean(p('reference'), 80), clean(p('note'), 200), 'paid', $date, date('Y-m-d H:i:s'), $coin, $network, $hash, $camt]);
    respond(['ok'=>true, 'balance'=>$balance - $amount]);

  case 'refund':
    $pay = row('SELECT * FROM payments WHERE id=?', [pint('id')]);
    if (!$pay || $pay['status'] !== 'paid') respond(['ok'=>false,'error'=>'Only a paid payment can be marked as refunded.'], 422);
    $pdo->prepare("UPDATE payments SET status='refunded', note=? WHERE id=?")->execute([trim($pay['note'] . ' Refunded ' . date('Y-m-d')), $pay['id']]);
    respond(['ok'=>true]);

  case 'export':
    [$where, $a] = filters();
    $list = rows("SELECT p.paid_at, b.ref, b.name, b.room_name, p.method, p.reference, p.amount, p.status, p.coin, p.network, p.crypto_amount, p.tx_hash " . FROM . " $where ORDER BY p.paid_at DESC, p.id DESC", $a);
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="payments-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w'); fputcsv($o, ['Date','Booking','Guest','Room','Method','Reference','Amount (PHP)','Status','Coin','Network','Crypto amount','Transaction hash']);
    foreach ($list as $r) fputcsv($o, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, array_values($r)));
    exit;
}
respond(['ok'=>false,'error'=>'Unknown action.'], 400);
