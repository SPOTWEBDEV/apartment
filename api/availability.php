<?php
require __DIR__ . '/_bootstrap.php';
require_method('POST');
$in = body();
$in_d = DateTime::createFromFormat('Y-m-d', $in['checkin'] ?? '');
$out_d = DateTime::createFromFormat('Y-m-d', $in['checkout'] ?? '');
$guests = max(1, min(30, (int)($in['guests'] ?? 2)));
$today = new DateTime('today');

if (!$in_d || !$out_d) respond(['ok'=>false,'error'=>'Choose a check-in and check-out date.'], 422);
if ($in_d < $today) respond(['ok'=>false,'error'=>'Check-in cannot be in the past.'], 422);
if ($out_d <= $in_d) respond(['ok'=>false,'error'=>'Check-out must be after check-in.'], 422);

$nights = $in_d->diff($out_d)->days;
$data = require __DIR__ . '/../includes/data.php';
$results = [];
foreach ($data['rooms'] as $r) {
  if ($r['guests'] < $guests) continue;
  // TODO: replace with a real availability query against the bookings table.
  $results[] = ['id'=>$r['id'],'name'=>$r['name'],'guests'=>$r['guests'],
    'nightly'=>$r['price'],'total'=>$r['price'] * $nights];
}
respond(['ok'=>true,'nights'=>$nights,'rooms'=>$results]);
