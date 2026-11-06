<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_method('POST');
$in = body();
if (!empty($in['website'])) respond(['ok'=>true]); // honeypot

$types = ['stay'=>'Stay question', 'table'=>'Table reservation', 'wedding'=>'Wedding inquiry', 'corporate'=>'Corporate event inquiry', 'other'=>'General message'];
$type = array_key_exists($in['type'] ?? '', $types) ? $in['type'] : 'other';
$name = clean($in['name'] ?? '', 120);
$email = filter_var(trim($in['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = clean($in['phone'] ?? '', 40);
$message = clean($in['message'] ?? '', 2000);
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($in['event_date'] ?? '')) ? $in['event_date'] : '';
$time = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string)($in['reserve_time'] ?? '')) ? $in['reserve_time'] : '';
$guests = max(0, min(5000, (int)($in['guests'] ?? 0)));
if ($type !== 'table') $time = '';
if (!in_array($type, ['table', 'wedding', 'corporate'], true)) { $date = ''; $guests = 0; }

$errors = [];
if ($name === '') $errors['name'] = 'Enter your name.';
if (!$email) $errors['email'] = 'Enter a valid email address.';
if ($type === 'table') {
  if ($date === '') $errors['event_date'] = 'Choose the date you would like.';
  elseif ($date < date('Y-m-d')) $errors['event_date'] = 'That date has passed.';
  if ($guests < 1) $errors['guests'] = 'How many guests?';
} elseif (mb_strlen($message) < 10) $errors['message'] = 'Tell us a little more (at least 10 characters).';
if ($errors) respond(['ok'=>false,'errors'=>$errors], 422);

db()->prepare('INSERT INTO inquiries(type,name,email,phone,event_date,reserve_time,guests,message,status,ip,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
    ->execute([$type, $name, $email, $phone, $date, $time, $guests, $message, 'new', $_SERVER['REMOTE_ADDR'] ?? '', date('Y-m-d H:i:s')]);

$extra = ($date ? "Date: $date\n" : '') . ($time ? "Time: $time\n" : '') . ($guests ? "Guests: $guests\n" : '') . ($phone ? "Phone: $phone\n" : '');
send_mail(cfg('email'), $types[$type] . " from $name", "From: $name <$email>\n$extra\n$message\n\nReply or mark it handled in the admin: Messages.", $email);
respond(['ok'=>true]);
