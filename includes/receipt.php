<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/pdf.php';

function base_url(int $up = 2): string {
  $fixed = trim((string)cfg('site_url', ''));
  if ($fixed !== '') return rtrim($fixed, '/');
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  $dir = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
  for ($i = 0; $i < $up; $i++) $dir = dirname($dir);
  return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim($dir === '.' ? '' : $dir, '/');
}
/** Relative link used on the website (pages live in the project root). */
function receipt_path(array $b): string { return 'receipt.php?ref=' . rawurlencode($b['ref']) . '&t=' . $b['token']; }

function receipt_booking(string $ref, string $token): ?array {
  $s = db()->prepare('SELECT * FROM bookings WHERE ref = ?'); $s->execute([$ref]);
  $b = $s->fetch();
  return ($b && $b['token'] !== '' && hash_equals($b['token'], $token)) ? $b : null;
}

function receipt_pdf(array $b): string {
  $s = db()->prepare("SELECT * FROM payments WHERE booking_id=? AND status='paid' ORDER BY paid_at, id"); $s->execute([$b['id']]);
  $pays = $s->fetchAll();
  $paid = array_sum(array_map(fn($p) => (int)$p['amount'], $pays)); $total = (int)$b['total'];
  $balance = max(0, $total - $paid);

  $cur = cfg('currency', '₱'); $cur = $cur === '₱' ? 'PHP ' : $cur;
  $m = fn($n) => ($n < 0 ? '-' : '') . $cur . number_format(abs((int)$n), 2);
  $d = fn($x) => date('j M Y', strtotime((string)$x));
  $navy = [11,42,67]; $gray = [100,116,139]; $bronze = [168,116,59]; $line = [226,232,240]; $tint = [238,244,243];
  $L = 50; $R = 545;
  $statusLabel = ['pending'=>'Pending confirmation','confirmed'=>'Confirmed','checked_in'=>'Checked in','completed'=>'Completed','cancelled'=>'Cancelled'][$b['status']] ?? $b['status'];
  $payLabel = $paid >= $total ? 'Paid in full' : ($paid > 0 ? 'Partly paid' : 'Unpaid');
  $methods = ['cash'=>'Cash','bank'=>'Bank transfer','gcash'=>'GCash','maya'=>'Maya','card'=>'Card','crypto'=>'Crypto','other'=>'Other'];

  $pdf = new SimplePdf(); $pdf->title('Booking receipt ' . $b['ref']);
  $pdf->image(__DIR__ . '/../assets/img/logo-receipt.jpg', $L, 38, 112);
  $pdf->text($R, 58, 'BOOKING RECEIPT', 17, true, $navy, 'r');
  $pdf->text($R, 76, 'Issued ' . date('j M Y'), 9, false, $gray, 'r');
  $pdf->line($L, 100, $R, 100);

  // From / billed to
  $y = 124;
  $pdf->text($L, $y, 'FROM', 8, true, $bronze); $pdf->text(320, $y, 'BILLED TO', 8, true, $bronze);
  $yl = $y + 16; $pdf->text($L, $yl, (string)cfg('site_name'), 11, true);
  foreach ((array)cfg('address', []) as $a) { $yl += 14; $pdf->text($L, $yl, (string)$a, 9.5, false, $gray); }
  $yl += 14; $pdf->text($L, $yl, (string)cfg('phone'), 9.5, false, $gray);
  $yl += 14; $pdf->text($L, $yl, (string)cfg('email'), 9.5, false, $gray);
  $yr = $y + 16; $pdf->text(320, $yr, $b['name'], 11, true);
  foreach ([$b['email'], $b['phone']] as $v) if ($v !== '') { $yr += 14; $pdf->text(320, $yr, $v, 9.5, false, $gray); }

  // Summary strip
  $y = max($yl, $yr) + 24;
  $pdf->rect($L, $y, $R - $L, 52, $tint);
  foreach ([['REFERENCE', $b['ref'], $L + 16], ['BOOKING STATUS', $statusLabel, 215], ['PAYMENT', $payLabel, 395]] as [$k, $v, $x]) {
    $pdf->text($x, $y + 19, $k, 8, true, $gray); $pdf->text($x, $y + 38, $v, 11.5, true);
  }

  // Stay
  $y += 82; $pdf->text($L, $y, 'STAY', 8, true, $bronze); $y += 8;
  $rows = [['Room', $b['room_name']], ['Check-in', $d($b['checkin']) . ' from ' . cfg('check_in')], ['Check-out', $d($b['checkout']) . ' by ' . cfg('check_out')],
           ['Nights', (string)$b['nights']], ['Guests', (string)$b['guests']], ['Booked on', $d(substr((string)$b['created_at'], 0, 10))]];
  foreach ($rows as [$k, $v]) { $y += 17; $pdf->text($L, $y, $k, 10, false, $gray); $pdf->text(150, $y, (string)$v, 10, true); }

  // Charges
  $y += 34; $pdf->rect($L, $y, $R - $L, 24, $navy);
  $pdf->text($L + 12, $y + 16, 'Description', 9.5, true, [255,255,255]); $pdf->text($R - 12, $y + 16, 'Amount', 9.5, true, [255,255,255], 'r');
  $y += 24; $rate = (int)round($b['subtotal'] / max(1, $b['nights']));
  $desc = $b['room_name'] . '  x ' . $b['nights'] . ($b['nights'] == 1 ? ' night' : ' nights') . ' at ' . $m($rate);
  $y += 20; $pdf->text($L + 12, $y, $desc, 10); $pdf->text($R - 12, $y, $m($b['subtotal']), 10, false, $navy, 'r');
  if ((int)$b['discount_amount'] > 0) { $y += 20; $pdf->text($L + 12, $y, 'Discount (' . $b['discount_code'] . ')', 10, false, [4,120,87]); $pdf->text($R - 12, $y, $m(-(int)$b['discount_amount']), 10, false, [4,120,87], 'r'); }
  $y += 10; $pdf->line($L, $y, $R, $y); $y += 20;
  $pdf->text($L + 12, $y, 'Total', 11.5, true); $pdf->text($R - 12, $y, $m($total), 11.5, true, $navy, 'r');

  // Payments
  $y += 36; $pdf->text($L, $y, 'PAYMENTS RECEIVED', 8, true, $bronze); $y += 6;
  if (!$pays) { $y += 18; $pdf->text($L, $y, 'No payments recorded yet.', 10, false, $gray); }
  foreach (array_slice($pays, 0, 8) as $p) {
    $y += 18; $label = $methods[$p['method']] ?? $p['method'];
    if ($p['method'] === 'crypto' && $p['coin']) $label .= ' (' . $p['coin'] . ' ' . $p['network'] . ')';
    $pdf->text($L, $y, $d($p['paid_at']), 10, false, $gray); $pdf->text(150, $y, $label, 10);
    $pdf->text($R, $y, $m($p['amount']), 10, false, $navy, 'r');
  }
  if (count($pays) > 8) { $y += 16; $pdf->text($L, $y, '+ ' . (count($pays) - 8) . ' more payments', 9, false, $gray); }
  $y += 12; $pdf->line($L, $y, $R, $y); $y += 20;
  $pdf->text($L, $y, 'Amount paid', 10.5, true); $pdf->text($R, $y, $m($paid), 10.5, true, [4,120,87], 'r');
  $y += 20; $pdf->text($L, $y, 'Balance due', 11.5, true); $pdf->text($R, $y, $m($balance), 11.5, true, $balance > 0 ? [185,28,28] : $navy, 'r');

  // Footer
  $note = $b['status'] === 'pending'
    ? 'Your booking request has been received. The reservations team confirms by email or WhatsApp, and your stay is not guaranteed until it is confirmed.'
    : ($b['status'] === 'cancelled' ? 'This booking has been cancelled.' : 'Thank you for choosing ' . cfg('site_name') . '. Please show this receipt or your reference at check-in.');
  $note .= ' This is a booking receipt and not an official tax invoice. Rates and balances are subject to the resort\'s confirmation.';
  $fy = 748; $pdf->line($L, $fy - 14, $R, $fy - 14);
  foreach ($pdf->wrap($note, $R - $L, 8.5) as $ln) { $pdf->text($L, $fy, $ln, 8.5, false, $gray); $fy += 12; }
  $pdf->text(($L + $R) / 2, 815, cfg('site_name') . '  |  ' . implode(', ', (array)cfg('address', [])) . '  |  ' . cfg('email'), 8, false, $gray, 'c');
  return $pdf->output();
}
