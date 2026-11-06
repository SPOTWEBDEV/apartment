<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/bookings.php';
require_method('POST');
$in = body();
[$err, $q] = validate_stay($in);
if ($err) respond(['ok'=>false,'error'=>$err], 422);
$room = null; foreach (rooms_all(true) as $r) if ($r['id'] === ($in['room'] ?? '')) $room = $r;
if (!$room) respond(['ok'=>false,'error'=>'Choose a room first.'], 422);
$code = strtoupper(clean($in['code'] ?? '', 30));
if ($code === '') respond(['ok'=>false,'error'=>'Enter a discount code.'], 422);
$d = discount_find($code); $subtotal = $room['price'] * $q['nights'];
[$e, $amt] = discount_eval($d, $q['nights'], $subtotal);
if ($e) respond(['ok'=>false,'error'=>$e], 422);
respond(['ok'=>true,'code'=>$d['code'],'label'=>$d['label'],'amount'=>$amt,'total'=>$subtotal - $amt]);
