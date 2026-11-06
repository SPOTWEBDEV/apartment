<?php
// Wipes ALL rooms, room photos, bookings, payments (and uploaded proofs), messages and discount codes so the admin starts empty.
// Keeps admin accounts and Settings. Use it once if a database ever held sample data.
//   php tools/reset-data.php --yes
require __DIR__ . '/../includes/bootstrap.php';
if (PHP_SAPI !== 'cli') exit('Run this from the command line.');
if (!in_array('--yes', $argv, true)) exit("This permanently deletes every room, booking, payment, message and discount code.\nRun again with --yes to continue.\n");
$pdo = db();
foreach ($pdo->query("SELECT file FROM room_images WHERE file LIKE 'rooms/%'")->fetchAll(PDO::FETCH_COLUMN) as $f) @unlink(__DIR__ . '/../assets/img/' . $f);
foreach (glob(__DIR__ . '/../storage/proofs/*') ?: [] as $f) @unlink($f);
foreach (['payments', 'bookings', 'room_images', 'rooms', 'discounts', 'inquiries'] as $t) $pdo->exec("DELETE FROM $t");
$pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('payments','bookings','room_images','discounts','inquiries')");
echo "Done. Rooms, bookings, payments, messages and discount codes are cleared.\n";
