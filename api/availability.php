<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/bookings.php';
require_method('POST');

[$err, $q] = validate_stay(body());
if ($err) respond(['ok'=>false,'error'=>$err], 422);
respond(['ok'=>true, 'nights'=>$q['nights'], 'rooms'=>rooms_available(rooms_all(true), $q)]);
