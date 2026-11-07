<?php
// A guest sends payment details (and optionally a screenshot). It waits as "pending" until staff approve or decline it.
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/bookings.php';
require_once __DIR__ . '/../includes/receipt.php';
require_once __DIR__ . '/../includes/crypto.php';
require_method('POST');
if (!$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) respond(['ok'=>false,'error'=>'That file is larger than the server allows (' . ini_get('post_max_size') . '). Try a smaller screenshot.'], 413);
if (!empty($_POST['website'])) respond(['ok'=>true]); // honeypot

$in = fn(string $k): string => trim((string)($_POST[$k] ?? ''));
$b = receipt_booking($in('ref'), $in('t'));
if (!$b) respond(['ok'=>false,'error'=>'This payment link is not valid. Use the link from your booking email.'], 404);
if ($b['status'] === 'cancelled') respond(['ok'=>false,'error'=>'This booking was cancelled, so it cannot take a payment.'], 409);
$pdo = db();
$balance = (int)$b['total'] - booking_paid((int)$b['id']);
if ($balance <= 0) respond(['ok'=>false,'error'=>'This booking is already paid in full. Thank you!'], 409);
$waiting = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE booking_id=? AND status='pending'"); $waiting->execute([$b['id']]);
if ((int)$waiting->fetchColumn() >= 5) respond(['ok'=>false,'error'=>'You already have several payments waiting for review. We will confirm them shortly.'], 429);

$amount = (int)$in('amount');
if ($amount < 1) respond(['ok'=>false,'error'=>'Enter the amount you paid.'], 422);
if ($amount > $balance) respond(['ok'=>false,'error'=>'That is more than the balance of ' . peso($balance) . '.'], 422);
// Only the choices the form offers: the full balance, or half when nothing has been paid yet.
$half = (int)ceil($balance / 2);
$allowed = [$balance];
if ($balance === (int)$b['total'] && $half < $balance) $allowed[] = $half;
if (!in_array($amount, $allowed, true)) {
  respond(['ok'=>false,'error'=>count($allowed) > 1
    ? 'Choose half payment (' . peso($half) . ') or the full balance (' . peso($balance) . ').'
    : 'Please pay the remaining balance of ' . peso($balance) . '.'], 422);
}
$method = in_array($in('method'), ['bank', 'gcash', 'maya', 'crypto', 'other'], true) ? $in('method') : 'other';
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $in('paid_at')) && $in('paid_at') <= date('Y-m-d') ? $in('paid_at') : date('Y-m-d');
$reference = clean($in('reference'), 80);

$coin = $network = $hash = ''; $camt = 0.0;
if ($method === 'crypto') {
  $coin = strtoupper($in('coin')); $network = $in('network'); $hash = $in('tx_hash'); $camt = (float)$in('crypto_amount');
  if (!isset(CRYPTO_NETWORKS[$coin]) || !in_array($network, CRYPTO_NETWORKS[$coin], true)) respond(['ok'=>false,'error'=>'Choose the coin and the network you sent it on.'], 422);
  if ($camt <= 0) respond(['ok'=>false,'error'=>'Enter the amount of crypto you sent.'], 422);
  if (!preg_match('/^[A-Za-z0-9]{10,128}$/', $hash)) respond(['ok'=>false,'error'=>'Paste the transaction hash (letters and numbers only, no spaces).'], 422);
  $dup = $pdo->prepare("SELECT 1 FROM payments WHERE lower(tx_hash)=lower(?) AND status!='declined'"); $dup->execute([$hash]);
  if ($dup->fetch()) respond(['ok'=>false,'error'=>'That transaction hash has already been submitted.'], 409);
}

// Optional proof: a screenshot (JPG, PNG, WebP) or a PDF, kept in the private storage folder.
$proof = '';
$f = $_FILES['proof'] ?? null;
if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
  if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) respond(['ok'=>false,'error'=>'That file is larger than the server allows (' . ini_get('upload_max_filesize') . ').'], 413);
  if ($f['error'] !== UPLOAD_ERR_OK) respond(['ok'=>false,'error'=>'The file could not be uploaded. Try again.'], 422);
  if ($f['size'] > 5 * 1024 * 1024) respond(['ok'=>false,'error'=>'The file is larger than 5 MB.'], 413);
  $info = @getimagesize($f['tmp_name']);
  $ext = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'][$info['mime'] ?? ''] ?? (file_get_contents($f['tmp_name'], false, null, 0, 5) === '%PDF-' ? 'pdf' : null);
  if (!$ext) respond(['ok'=>false,'error'=>'Upload a JPG, PNG, WebP or PDF file.'], 422);
  $dir = __DIR__ . '/../storage/proofs'; if (!is_dir($dir)) @mkdir($dir, 0775, true);
  $proof = preg_replace('/[^A-Za-z0-9-]/', '', $b['ref']) . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
  if (!@move_uploaded_file($f['tmp_name'], "$dir/$proof")) respond(['ok'=>false,'error'=>'Could not save the file. Please send it to us by email instead.'], 500);
}
if ($reference === '' && $hash === '' && $proof === '') respond(['ok'=>false,'error'=>'Add a reference number, or upload a screenshot of your payment.'], 422);

$same = $pdo->prepare("SELECT 1 FROM payments WHERE booking_id=? AND status='pending' AND amount=? AND method=? AND reference=? AND tx_hash=? AND created_at >= ?");
$same->execute([$b['id'], $amount, $method, $reference, $hash, date('Y-m-d H:i:s', time() - 600)]);
if ($same->fetch()) respond(['ok'=>true]); // already received a moment ago

$pdo->prepare("INSERT INTO payments(booking_id,amount,method,reference,note,status,paid_at,created_at,coin,network,tx_hash,crypto_amount,proof,submitted_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute([$b['id'], $amount, $method, $reference, '', 'pending', $date, date('Y-m-d H:i:s'), $coin, $network, $hash, $camt, $proof, 'guest']);

send_mail(cfg('email'), "Payment to review for booking {$b['ref']}",
  "{$b['name']} sent a payment of " . peso($amount) . " for booking {$b['ref']} ({$b['room_name']}).\nMethod: $method" . ($coin ? " ($coin on $network, $camt)" : '') . ($reference ? "\nReference: $reference" : '') . ($hash ? "\nTransaction hash: $hash" : '') . ($proof ? "\nA screenshot was uploaded." : '') .
  "\n\nApprove or decline it here: " . base_url(2) . '/admin/payments.php?status=pending', $b['email'] ?: null);
respond(['ok'=>true]);
