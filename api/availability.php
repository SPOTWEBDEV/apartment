<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/../includes/bookings.php';
require_method('POST');

[$err, $q] = validate_stay(body());
if ($err) respond(['ok'=>false,'error'=>$err], 422);

$data = require __DIR__ . '/../includes/data.php';
respond(['ok'=>true, 'nights'=>$q['nights'], 'rooms'=>rooms_available($data['rooms'], $q)]);
