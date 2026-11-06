<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/bookings.php';
require_once __DIR__ . '/../includes/receipt.php';
require_method('POST');
$in = body();
if (!empty($in['website'])) respond(['ok'=>true,'ref'=>'CL-OK']); // honeypot

[$err, $q] = validate_stay($in);
if ($err) respond(['ok'=>false,'error'=>$err], 422);

$room = null; foreach (rooms_all(true) as $r) if ($r['id'] === ($in['room'] ?? '')) $room = $r;
if (!$room) respond(['ok'=>false,'error'=>'Choose a room.'], 422);
if ($q['guests'] > $room['guests']) respond(['ok'=>false,'error'=>"This room sleeps up to {$room['guests']} guests."], 422);

$name = clean($in['name'] ?? '', 120);
$email = filter_var(trim($in['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = clean($in['phone'] ?? '', 40);
$notes = clean($in['notes'] ?? '', 1000);
$code = strtoupper(clean($in['code'] ?? '', 30));
$errors = [];
if ($name === '') $errors['name'] = 'Enter your name.';
if (!$email) $errors['email'] = 'Enter a valid email address.';
if ($phone === '') $errors['phone'] = 'Enter a phone or WhatsApp number so we can reach you.';
if ($errors) respond(['ok'=>false,'errors'=>$errors], 422);

$res = booking_create($room, $q, ['name'=>$name,'email'=>$email,'phone'=>$phone,'notes'=>$notes], $code);
if (!$res['ok']) {
  if ($res['reason'] === 'unavailable') respond(['ok'=>false,'unavailable'=>true,'error'=>'That room was just booked for those dates. Choose another room or different dates.'], 409);
  if ($res['reason'] === 'discount') respond(['ok'=>false,'errors'=>['code'=>$res['error']],'error'=>$res['error']], 422);
  respond(['ok'=>false,'error'=>'We could not save your request. Please contact us directly.'], 500);
}
$b = $res['booking'];
$summary = "Reference: {$b['ref']}\nRoom: {$b['room_name']}\nCheck-in: {$b['checkin']}\nCheck-out: {$b['checkout']} ({$b['nights']} nights)\nGuests: {$b['guests']}\n"
  . ($b['discount_code'] ? "Discount {$b['discount_code']}: -" . peso($b['discount_amount']) . "\n" : '') . 'Estimated total: ' . peso($b['total']);

send_mail(cfg('email'), "New booking request {$b['ref']}", "$summary\n\nGuest: $name\nEmail: $email\nPhone: $phone\nNotes: $notes", $email);
send_mail($email, 'We received your booking request (' . $b['ref'] . ')', "Hello $name,\n\nThank you for choosing " . cfg('site_name') . ". This is a request, not a confirmed booking. Our reservations team will confirm by email or WhatsApp.\n\n$summary\n\nDownload your receipt (PDF): " . base_url(2) . '/' . receipt_path($b) . "\n\n" . cfg('site_name'), cfg('email'));

respond(['ok'=>true,'ref'=>$b['ref'],'total'=>$b['total'],'receipt'=>receipt_path($b),'summary'=>[
  'ref'=>$b['ref'],'name'=>$b['name'],'email'=>$b['email'],'phone'=>$b['phone'],'room'=>$b['room_name'],'checkin'=>$b['checkin'],'checkout'=>$b['checkout'],
  'nights'=>(int)$b['nights'],'guests'=>(int)$b['guests'],'subtotal'=>(int)$b['subtotal'],'discount_code'=>$b['discount_code'],'discount_amount'=>(int)$b['discount_amount'],
  'total'=>(int)$b['total'],'paid'=>0,'balance'=>(int)$b['total'],'status_label'=>'Pending confirmation']]);
