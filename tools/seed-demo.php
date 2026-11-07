<?php
/**
 * Adds rooms only (no bookings, payments or discount codes).
 * Room details come from the Rooms & Villas page you uploaded.
 *
 * Put this file in the same folder as your old demo script, upload the room photos
 * to assets/img/, then run:   php seed-rooms.php
 *
 * It is safe to run twice: a room whose name already exists is skipped.
 */
require __DIR__ . '/../includes/bookings.php';
if (PHP_SAPI !== 'cli') exit('Run this from the command line.');

$pdo = db();
$imgDir = __DIR__ . '/../assets/img/rooms/';

$BASE = 'Designed for refined comfort with thoughtful in-room amenities.';
$A_SUPERIOR = ['TV HD', 'Coffee and Tea', 'Welcome Fruit/Cookies', 'In-room Safety Box', 'WiFi Access', 'Bottled Water'];
$A_STANDARD = ['WiFi Access', 'TV HD', 'Coffee and Tea', 'Mini Refrigerator', 'In-room Safety Box', 'Bottled Water'];

// price = the price shown on the page (after the 50% off); beds = the bedding line from the page.
$rooms = [
  ['superior-room',          'Superior Room',           $BASE,                                                '1 Queen Size Bed',            2,   5500, $A_SUPERIOR, 'room-superior-room.jpg'],
  ['deluxe-swim-up',         'Deluxe Swim-Up',          $BASE,                                                '2 Queen Size Beds',           4,   6150, $A_STANDARD, 'room-deluxe-swim-up.jpg'],
  ['premier-queen',          'Premier Queen',           "Beautiful beach view. $BASE",                        '2 Queen Size Beds',           4,   6850, $A_STANDARD, 'room-premier-queen.jpg'],
  ['deluxe-with-sofa',       'Deluxe with Sofa',        $BASE,                                                '2 Queen Size Beds with Sofa', 4,   7000, $A_STANDARD, 'room-deluxe-with-sofa.jpg'],
  ['standard-ground-floor',  'Standard Ground Floor',   $BASE,                                                '2 Queen Size Beds',           5,   7600, $A_STANDARD, 'room-standard-ground-floor.jpg'],
  ['standard-poolside',      'Standard Poolside',       $BASE,                                                '2 Queen Size Beds',           5,   8150, $A_STANDARD, 'room-standard-poolside.jpg'],
  ['deluxe-family-pool-view','Deluxe Family Pool View', "2 connecting rooms. $BASE",                          '4 Beds',                      8,   8350, $A_STANDARD, 'room-deluxe-family-pool-view.jpg'],
  ['premier-family-room',    'Premier Family Room',     "2 rooms. $BASE",                                     '4 Beds',                      8,   9950, $A_STANDARD, 'room-premier-family-room.jpg'],
  ['1-bedroom-villa',        '1 Bedroom Villa',         "Private pool. $BASE",                                '2 Queen Size Beds',           8,  12150, $A_STANDARD, 'room-1-bedroom-villa.jpg'],
  ['2-bedroom-villa',        '2 Bedroom Villa',         "135 sqm with a private pool. $BASE",                 '2 Bedrooms',                 10,  14350, $A_STANDARD, null],
  ['3-bedroom-villa',        '3 Bedroom Villa',         "270 sqm with a private pool. $BASE",                 '3 Bedrooms',                 15,  17750, $A_STANDARD, null],
  ['presidential-suite',     'Presidential Suite',      "400 sqm suite with its own pool. $BASE",             '5 Bedrooms',                 20,  25000, $A_STANDARD, null],
];
$FEATURED = '1-bedroom-villa'; // shown large on the home page; it needs a photo

function table_cols(PDO $pdo, string $table): array {
  $out = [];
  foreach ($pdo->query('PRAGMA table_info(' . $table . ')') as $c) $out[$c['name']] = strtoupper((string)$c['type']);
  return $out;
}
function insert_row(PDO $pdo, string $table, array $row, array $cols): int {
  $row = array_intersect_key($row, $cols); // only columns that really exist
  $names = array_keys($row);
  $pdo->prepare("INSERT INTO $table (" . implode(',', $names) . ') VALUES (' . implode(',', array_fill(0, count($names), '?')) . ')')->execute(array_values($row));
  return (int)$pdo->lastInsertId();
}

try {
  $roomCols = table_cols($pdo, 'rooms');
  if (!$roomCols) exit("No 'rooms' table found.\n");

  // Find the photos table (the one with a room_id column).
  $imgTable = null; $imgCols = [];
  foreach ($pdo->query("SELECT name FROM sqlite_master WHERE type='table'") as $t) {
    $n = $t['name'];
    if ($n === 'rooms' || !preg_match('/image|photo|gallery/i', $n)) continue;
    $c = table_cols($pdo, $n);
    if (isset($c['room_id'])) { $imgTable = $n; $imgCols = $c; break; }
  }
  if (!$imgTable) echo "Warning: no photos table found, rooms will be added without photos.\n";

  // Amenities are stored as JSON unless existing rows show a line-separated list.
  $asJson = true;
  if (isset($roomCols['amenities'])) {
    $sample = $pdo->query("SELECT amenities FROM rooms WHERE amenities IS NOT NULL AND amenities != '' LIMIT 1")->fetchColumn();
    if ($sample) $asJson = ltrim((string)$sample)[0] === '[';
  }
  $idIsInt = str_contains($roomCols['id'] ?? '', 'INT');
  $sortCol = null; foreach (['sort', 'sort_order', 'position'] as $c) if (isset($roomCols[$c])) { $sortCol = $c; break; }
  $imgSort = null; foreach (['sort', 'sort_order', 'position'] as $c) if (isset($imgCols[$c])) { $imgSort = $c; break; }
  $hasFeatured = isset($roomCols['featured']) ? (int)$pdo->query('SELECT COUNT(*) FROM rooms WHERE featured = 1')->fetchColumn() > 0 : false;
  $now = date('Y-m-d H:i:s'); $added = 0; $skipped = 0;

  $pdo->beginTransaction();
  foreach ($rooms as $i => [$slug, $name, $blurb, $beds, $guests, $price, $amen, $file]) {
    $exists = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE name = ?'); $exists->execute([$name]);
    if ($exists->fetchColumn()) { echo "Skipped (already exists): $name\n"; $skipped++; continue; }

    $row = [
      'name' => $name, 'blurb' => $blurb, 'beds' => $beds, 'guests' => $guests, 'units' => 1, 'price' => $price,
      'amenities' => $asJson ? json_encode($amen, JSON_UNESCAPED_UNICODE) : implode("\n", $amen),
      'featured' => ($slug === $FEATURED && !$hasFeatured) ? 1 : 0, 'active' => 1,
      'created_at' => $now, 'updated_at' => $now,
    ];
    if (!$idIsInt) $row['id'] = $slug;
    if ($sortCol) $row[$sortCol] = $i + 1;
    if ($file) foreach (['img', 'image', 'cover'] as $c) $row[$c] = $file;

    $newId = insert_row($pdo, 'rooms', $row, $roomCols);
    $roomId = $idIsInt ? $newId : $slug;

    if ($file && $imgTable) {
      if (!is_file($imgDir . $file)) echo "  Warning: $file is not in assets/img/rooms yet.\n";
      $img = ['room_id' => $roomId, 'file' => $file, 'caption' => $name, 'created_at' => $now];
      if ($imgSort) $img[$imgSort] = 1;
      insert_row($pdo, $imgTable, $img, $imgCols);
    }
    echo "Added: $name" . ($file ? '' : ' (no photo yet, add one in Admin > Rooms)') . "\n"; $added++;
  }
  $pdo->commit();
  echo "\nDone. $added rooms added, $skipped skipped. No bookings were created.\n";
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo 'Stopped: ' . $e->getMessage() . "\n";
  echo "Nothing was saved. Send me the rooms table columns:\n";
  foreach (($roomCols ?? []) as $c => $t) echo "  rooms.$c $t\n";
  foreach (($imgCols ?? []) as $c => $t) echo "  $imgTable.$c $t\n";
  exit(1);
}