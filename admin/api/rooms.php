<?php
require_once __DIR__ . '/_boot.php';

const MAX_BYTES = 12 * 1024 * 1024;

function img_root(): string { return __DIR__ . '/../../assets/img/'; }
function room_images(string $id): array { return rows('SELECT * FROM room_images WHERE room_id=? ORDER BY sort, id', [$id]); }
/** The first gallery photo is the cover shown on cards. */
function sync_cover(string $id): void {
  global $pdo;
  $f = val('SELECT file FROM room_images WHERE room_id=? ORDER BY sort, id LIMIT 1', [$id]);
  $pdo->prepare('UPDATE rooms SET img=? WHERE id=?')->execute([$f ?: '', $id]);
}
/** Only files uploaded through the admin (rooms/...) are ever deleted from disk. */
function drop_file(string $file): void {
  if (str_starts_with($file, 'rooms/') && !str_contains($file, '..') && !val('SELECT 1 FROM room_images WHERE file=?', [$file])) @unlink(img_root() . $file);
}
function room_or_fail(string $id): array {
  $r = row('SELECT * FROM rooms WHERE id=?', [$id]);
  if (!$r) respond(['ok'=>false,'error'=>'Room not found. Save the room first.'], 404);
  return $r;
}

switch ($action) {
  case 'list':
    $today = date('Y-m-d');
    $rooms = rows('SELECT * FROM rooms ORDER BY featured DESC, created_at, id');
    foreach ($rooms as &$r) {
      $r['amenities'] = json_decode($r['amenities'] ?: '[]', true) ?: [];
      $r['images'] = room_images($r['id']);
      $r['occupied'] = (int)val("SELECT COUNT(*) FROM bookings WHERE room_id=? AND status IN ('confirmed','checked_in') AND checkin<=? AND checkout>?", [$r['id'], $today, $today]);
      $r['upcoming'] = (int)val("SELECT COUNT(*) FROM bookings WHERE room_id=? AND status IN ('pending','confirmed') AND checkin>?", [$r['id'], $today]);
    }
    respond(['ok'=>true, 'rooms'=>$rooms, 'max_images'=>(int)cfg('max_room_images', 10)]);

  case 'save': // details only; photos go through upload / images_save
    $id = p('id'); $name = clean(p('name'), 80);
    $guests = pint('guests', 2); $units = pint('units', 1); $price = pint('price');
    if ($name === '') respond(['ok'=>false,'error'=>'Enter a room name.'], 422);
    if ($guests < 1 || $units < 1 || $price < 0) respond(['ok'=>false,'error'=>'Guests and units must be at least 1, and the price cannot be negative.'], 422);
    $amen = array_values(array_filter(array_map(fn($x) => clean($x, 60), preg_split('/\R/', p('amenities')))));
    $featured = pint('featured') ? 1 : 0; $active = pint('active') ? 1 : 0;
    if ($id === '') {
      $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'room'; $id = $base; $n = 2;
      while (val('SELECT 1 FROM rooms WHERE id=?', [$id])) $id = $base . '-' . $n++;
      $pdo->prepare('INSERT INTO rooms(id,name,blurb,beds,guests,units,price,img,amenities,featured,active,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')
          ->execute([$id, $name, clean(p('blurb'), 300), clean(p('beds'), 60), $guests, $units, $price, '', json_encode($amen), $featured, $active, date('Y-m-d H:i:s')]);
    } else {
      room_or_fail($id);
      $pdo->prepare('UPDATE rooms SET name=?, blurb=?, beds=?, guests=?, units=?, price=?, amenities=?, featured=?, active=? WHERE id=?')
          ->execute([$name, clean(p('blurb'), 300), clean(p('beds'), 60), $guests, $units, $price, json_encode($amen), $featured, $active, $id]);
    }
    if ($featured) $pdo->prepare('UPDATE rooms SET featured=0 WHERE id!=?')->execute([$id]);
    respond(['ok'=>true, 'id'=>$id]);

  case 'upload': // one photo per request, so large galleries never hit the server's total upload limit
    $id = p('room_id'); room_or_fail($id);
    $max = (int)cfg('max_room_images', 10);
    if ((int)val('SELECT COUNT(*) FROM room_images WHERE room_id=?', [$id]) >= $max) respond(['ok'=>false,'error'=>"A room can have up to $max photos."], 409);
    $f = $_FILES['image'] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) respond(['ok'=>false,'error'=>'Choose a photo.'], 422);
    if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) respond(['ok'=>false,'error'=>'This photo is larger than the server allows (' . ini_get('upload_max_filesize') . ').'], 413);
    if ($f['error'] !== UPLOAD_ERR_OK) respond(['ok'=>false,'error'=>'The photo could not be uploaded. Try again.'], 422);
    if ($f['size'] > MAX_BYTES) respond(['ok'=>false,'error'=>'This photo is larger than 12 MB.'], 413);
    $info = @getimagesize($f['tmp_name']);
    $ext = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'][$info['mime'] ?? ''] ?? null;
    if (!$ext) respond(['ok'=>false,'error'=>'Use a JPG, PNG or WebP photo.'], 422);
    $dir = img_root() . 'rooms'; if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $file = 'rooms/' . $id . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
    if (!@move_uploaded_file($f['tmp_name'], img_root() . $file)) respond(['ok'=>false,'error'=>'Could not save the photo. Check that assets/img is writable.'], 500);
    $pdo->prepare('INSERT INTO room_images(room_id,file,caption,sort,created_at) VALUES(?,?,?,?,?)')
        ->execute([$id, $file, clean(p('caption'), 60), 1 + (int)val('SELECT COALESCE(MAX(sort),0) FROM room_images WHERE room_id=?', [$id]), date('Y-m-d H:i:s')]);
    $imgId = (int)$pdo->lastInsertId(); sync_cover($id);
    respond(['ok'=>true, 'id'=>$imgId, 'file'=>$file]);

  case 'images_save': // final order (first = cover) and captions: order = JSON [{id, caption}, ...]
    $id = p('room_id'); room_or_fail($id);
    $list = json_decode(p('order', '[]'), true); if (!is_array($list)) $list = [];
    $upd = $pdo->prepare('UPDATE room_images SET sort=?, caption=? WHERE id=? AND room_id=?');
    foreach (array_values($list) as $i => $it) $upd->execute([$i, clean((string)($it['caption'] ?? ''), 60), (int)($it['id'] ?? 0), $id]);
    sync_cover($id);
    respond(['ok'=>true]);

  case 'image_delete':
    $im = row('SELECT * FROM room_images WHERE id=?', [pint('id')]);
    if (!$im) respond(['ok'=>true]);
    $pdo->prepare('DELETE FROM room_images WHERE id=?')->execute([$im['id']]);
    drop_file($im['file']); sync_cover($im['room_id']);
    respond(['ok'=>true]);

  case 'toggle':
    $pdo->prepare('UPDATE rooms SET active=? WHERE id=?')->execute([pint('active') ? 1 : 0, p('id')]);
    respond(['ok'=>true]);

  case 'delete':
    if ((int)val('SELECT COUNT(*) FROM bookings WHERE room_id=?', [p('id')]) > 0)
      respond(['ok'=>false,'error'=>'This room has bookings, so it cannot be deleted. Switch it off instead to hide it from the website.'], 409);
    $files = array_column(room_images(p('id')), 'file');
    $pdo->prepare('DELETE FROM room_images WHERE room_id=?')->execute([p('id')]);
    $pdo->prepare('DELETE FROM rooms WHERE id=?')->execute([p('id')]);
    foreach ($files as $f) drop_file($f);
    respond(['ok'=>true]);
}
respond(['ok'=>false,'error'=>'Unknown action.'], 400);
