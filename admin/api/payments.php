<?php
require_once __DIR__ . '/_boot.php';
require_once __DIR__ . '/../../includes/crypto.php';
require_once __DIR__ . '/../../includes/receipt.php';

const METHODS = ['cash','bank','gcash','maya','card','crypto','other'];
const STATUSES = ['pending','paid','declined','refunded'];
const FROM = 'FROM payments p JOIN bookings b ON b.id=p.booking_id';
const DETAIL = 'SELECT p.*, b.name, b.ref, b.email, b.room_name, b.total booking_total, b.status booking_status, (b.total - COALESCE(x.paid,0)) balance ' . FROM . ' LEFT JOIN ' . PAID_SUB . ' x ON x.booking_id=b.id';

function filters(): array {
  $w = []; $a = [];
  if (in_array(p('method'), METHODS, true)) { $w[] = 'p.method=?'; $a[] = p('method'); }
  if (in_array(p('status'), STATUSES, true)) { $w[] = 'p.status=?'; $a[] = p('status'); }
  if (preg_match('/^\d{4}-\d{2}$/', p('month'))) { $w[] = 'substr(p.paid_at,1,7)=?'; $a[] = p('month'); }
  if (p('q') !== '') { $w[] = '(b.name LIKE ? OR b.ref LIKE ? OR p.reference LIKE ? OR p.tx_hash LIKE ?)'; $l = '%' . p('q') . '%'; array_push($a, $l, $l, $l, $l); }
  return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $a];
}
function guest_mail(array $pay, string $subject, string $body): void {
  if (empty($pay['email'])) return;
  $b = ['ref' => $pay['ref'], 'token' => (string)val('SELECT token FROM bookings WHERE id=?', [$pay['booking_id']])];
  send_mail($pay['email'], $subject, "Hello {$pay['name']},\n\n$body\n\nBooking: {$pay['ref']} ({$pay['room_name']})\nYour payment page: " . base_url(3) . '/' . pay_path($b) . "\n\n" . cfg('site_name'), cfg('email'));
}

switch ($action) {
  case 'list':
    [$where, $a] = filters(); $page = max(1, pint('page', 1)); $per = 15;
    $total = (int)val("SELECT COUNT(*) " . FROM . " $where", $a);
    $list = rows(DETAIL . " $where ORDER BY (p.status='pending') DESC, p.paid_at DESC, p.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $a);
    $ms = date('Y-m-01'); $nms = date('Y-m-01', strtotime('+1 month')); $pms = date('Y-m-01', strtotime('-1 month'));
    $sum = fn($st, $f, $t) => (int)val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status=? AND paid_at>=? AND paid_at<?", [$st, $f, $t]);
    $out = (int)val("SELECT COALESCE(SUM(b.total - COALESCE(x.paid,0)),0) FROM bookings b LEFT JOIN " . PAID_SUB . " x ON x.booking_id=b.id WHERE b.status IN ('confirmed','checked_in','completed') AND b.total > COALESCE(x.paid,0)");
    $by = rows('SELECT status, COUNT(*) c, COALESCE(SUM(amount),0) s FROM payments GROUP BY status');
    respond(['ok'=>true, 'payments'=>$list, 'total'=>$total, 'page'=>$page, 'per'=>$per, 'pages'=>max(1, (int)ceil($total / $per)),
      'counts'=>array_column($by, 'c', 'status'), 'amounts'=>array_column($by, 's', 'status'),
      'summary'=>['collected'=>$sum('paid', $ms, $nms), 'collected_prev'=>$sum('paid', $pms, $ms), 'refunded'=>$sum('refunded', $ms, $nms), 'outstanding'=>$out]]);

  case 'get':
    $pay = row(DETAIL . ' WHERE p.id=?', [pint('id')]);
    if (!$pay) respond(['ok'=>false,'error'=>'Payment not found.'], 404);
    respond(['ok'=>true, 'payment'=>$pay]);

  case 'approve':
    $pay = row(DETAIL . ' WHERE p.id=?', [pint('id')]);
    if (!$pay || $pay['status'] !== 'pending') respond(['ok'=>false,'error'=>'This payment is not waiting for review.'], 409);
    if ($pay['booking_status'] === 'cancelled') respond(['ok'=>false,'error'=>'This booking is cancelled. Decline the payment instead.'], 409);
    $amount = pint('amount') ?: (int)$pay['amount']; $balance = (int)$pay['booking_total'] - booking_paid((int)$pay['booking_id']);
    if ($amount < 1) respond(['ok'=>false,'error'=>'Enter an amount greater than zero.'], 422);
    if ($amount > $balance) respond(['ok'=>false,'error'=>'That is more than the balance of ' . peso($balance) . '. Lower the amount to approve part of it, or decline it.'], 422);
    $pdo->prepare("UPDATE payments SET status='paid', amount=?, reviewed_at=? WHERE id=? AND status='pending'")->execute([$amount, date('Y-m-d H:i:s'), $pay['id']]);
    guest_mail($pay, 'Payment received for booking ' . $pay['ref'], 'We approved your payment of ' . peso($amount) . '. Thank you!' . ($balance - $amount > 0 ? ' The remaining balance is ' . peso($balance - $amount) . '.' : ' Your booking is now paid in full.'));
    respond(['ok'=>true, 'balance'=>$balance - $amount]);

  case 'decline':
    $pay = row(DETAIL . ' WHERE p.id=?', [pint('id')]);
    if (!$pay || $pay['status'] !== 'pending') respond(['ok'=>false,'error'=>'This payment is not waiting for review.'], 409);
    $reason = clean(p('reason'), 300);
    if (mb_strlen($reason) < 3) respond(['ok'=>false,'error'=>'Tell the guest why it was declined, so they know what to fix.'], 422);
    $pdo->prepare("UPDATE payments SET status='declined', decline_reason=?, reviewed_at=? WHERE id=? AND status='pending'")->execute([$reason, date('Y-m-d H:i:s'), $pay['id']]);
    guest_mail($pay, 'We could not confirm your payment for booking ' . $pay['ref'], 'We could not approve the payment of ' . peso($pay['amount']) . " you sent.\nReason: $reason\n\nYou can send the details again from your payment page.");
    respond(['ok'=>true]);

  case 'booking_search':
    $l = '%' . p('q') . '%';
    $list = rows("SELECT b.id, b.ref, b.name, b.room_name, b.total, COALESCE(x.paid,0) paid FROM bookings b LEFT JOIN " . PAID_SUB . " x ON x.booking_id=b.id
                  WHERE b.status!='cancelled' AND b.total > COALESCE(x.paid,0) AND (b.name LIKE ? OR b.ref LIKE ?) ORDER BY b.id DESC LIMIT 8", [$l, $l]);
    foreach ($list as &$b) $b['balance'] = (int)$b['total'] - (int)$b['paid'];
    respond(['ok'=>true, 'bookings'=>$list]);

  case 'create': // staff record money they already received: saved as approved straight away
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
      if (val("SELECT 1 FROM payments WHERE lower(tx_hash)=lower(?) AND status!='declined'", [$hash])) respond(['ok'=>false,'error'=>'That transaction hash is already recorded on another payment.'], 409);
    }
    $pdo->prepare('INSERT INTO payments(booking_id,amount,method,reference,note,status,paid_at,created_at,coin,network,tx_hash,crypto_amount,submitted_by,reviewed_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$b['id'], $amount, $method, clean(p('reference'), 80), clean(p('note'), 200), 'paid', $date, date('Y-m-d H:i:s'), $coin, $network, $hash, $camt, 'staff', date('Y-m-d H:i:s')]);
    respond(['ok'=>true, 'balance'=>$balance - $amount]);

  case 'refund':
    $pay = row('SELECT * FROM payments WHERE id=?', [pint('id')]);
    if (!$pay || $pay['status'] !== 'paid') respond(['ok'=>false,'error'=>'Only an approved payment can be marked as refunded.'], 422);
    $pdo->prepare("UPDATE payments SET status='refunded', note=? WHERE id=?")->execute([trim($pay['note'] . ' Refunded ' . date('Y-m-d')), $pay['id']]);
    respond(['ok'=>true]);

  case 'export':
    [$where, $a] = filters();
    $list = rows("SELECT p.paid_at, b.ref, b.name, b.room_name, p.method, p.reference, p.amount, p.status, p.coin, p.network, p.crypto_amount, p.tx_hash, p.submitted_by, p.decline_reason " . FROM . " $where ORDER BY p.paid_at DESC, p.id DESC", $a);
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="payments-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w'); fputcsv($o, ['Date','Booking','Guest','Room','Method','Reference','Amount (PHP)','Status','Coin','Network','Crypto amount','Transaction hash','Submitted by','Decline reason']);
    foreach ($list as $r) fputcsv($o, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, array_values($r)));
    exit;
}
respond(['ok'=>false,'error'=>'Unknown action.'], 400);
