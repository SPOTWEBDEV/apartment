<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/../includes/bootstrap.php';
require_method('POST');
$in = body();

// Honeypot: bots fill hidden fields.
if (!empty($in['website'])) respond(['ok'=>true]);

$name = clean($in['name'] ?? '', 120);
$email = filter_var(trim($in['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$type = in_array($in['type'] ?? '', ['stay','wedding','corporate','other'], true) ? $in['type'] : 'other';
$message = clean($in['message'] ?? '', 2000);

$errors = [];
if ($name === '') $errors['name'] = 'Enter your name.';
if (!$email) $errors['email'] = 'Enter a valid email address.';
if (mb_strlen($message) < 10) $errors['message'] = 'Tell us a little more (at least 10 characters).';
if ($errors) respond(['ok'=>false,'errors'=>$errors], 422);

$record = ['at'=>date('c'),'name'=>$name,'email'=>$email,'type'=>$type,'message'=>$message,'ip'=>$_SERVER['REMOTE_ADDR'] ?? ''];
file_put_contents(__DIR__ . '/../storage/inquiries.jsonl', json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);

// Notify the resort. Swap mail() for PHPMailer/SMTP in production.
@mail(cfg('email'), "New $type inquiry from $name", "From: $name <$email>\n\n$message", "Reply-To: $email");

respond(['ok'=>true]);
