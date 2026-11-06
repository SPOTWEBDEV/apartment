<?php
declare(strict_types=1);
// SQLite via PDO. The file is created on first use (storage/app.sqlite) and seeded from data.php and config.php.

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $custom = (string)env('DB_PATH', '');
  $path = $custom === '' ? __DIR__ . '/../storage/app.sqlite'
        : ((str_starts_with($custom, '/') || preg_match('/^[A-Za-z]:[\\\/]/', $custom)) ? $custom : dirname(__DIR__) . '/' . $custom);
  if (!is_dir(dirname($path))) @mkdir(dirname($path), 0775, true);
  $fresh = !is_file($path);
  $pdo = new PDO('sqlite:' . $path, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA busy_timeout=5000; PRAGMA foreign_keys=ON');
  db_migrate($pdo);
  if ($fresh) db_seed($pdo);
  db_backfill_tokens($pdo);
  db_backfill_room_images($pdo);
  return $pdo;
}

function db_migrate(PDO $pdo): void {
  $pdo->exec("
  CREATE TABLE IF NOT EXISTS rooms(
    id TEXT PRIMARY KEY, name TEXT NOT NULL, blurb TEXT DEFAULT '', beds TEXT DEFAULT '',
    guests INTEGER NOT NULL DEFAULT 2, units INTEGER NOT NULL DEFAULT 1, price INTEGER NOT NULL DEFAULT 0,
    img TEXT DEFAULT '', amenities TEXT DEFAULT '[]', featured INTEGER DEFAULT 0, active INTEGER DEFAULT 1, created_at TEXT);
  CREATE TABLE IF NOT EXISTS bookings(
    id INTEGER PRIMARY KEY AUTOINCREMENT, ref TEXT UNIQUE, room_id TEXT NOT NULL, room_name TEXT,
    checkin TEXT, checkout TEXT, nights INTEGER, guests INTEGER,
    name TEXT, email TEXT DEFAULT '', phone TEXT DEFAULT '', notes TEXT DEFAULT '', admin_notes TEXT DEFAULT '',
    subtotal INTEGER DEFAULT 0, discount_code TEXT DEFAULT '', discount_amount INTEGER DEFAULT 0, total INTEGER DEFAULT 0,
    status TEXT DEFAULT 'pending', source TEXT DEFAULT 'web', created_at TEXT);
  CREATE INDEX IF NOT EXISTS idx_b_room ON bookings(room_id, checkin, checkout);
  CREATE INDEX IF NOT EXISTS idx_b_status ON bookings(status);
  CREATE TABLE IF NOT EXISTS payments(
    id INTEGER PRIMARY KEY AUTOINCREMENT, booking_id INTEGER NOT NULL, amount INTEGER NOT NULL,
    method TEXT, reference TEXT DEFAULT '', note TEXT DEFAULT '', status TEXT DEFAULT 'paid', paid_at TEXT, created_at TEXT);
  CREATE INDEX IF NOT EXISTS idx_p_booking ON payments(booking_id);
  CREATE TABLE IF NOT EXISTS discounts(
    id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT UNIQUE NOT NULL, label TEXT DEFAULT '', type TEXT DEFAULT 'percent',
    value INTEGER NOT NULL, min_nights INTEGER DEFAULT 0, starts TEXT DEFAULT '', ends TEXT DEFAULT '',
    max_uses INTEGER DEFAULT 0, used INTEGER DEFAULT 0, active INTEGER DEFAULT 1, created_at TEXT);
  CREATE TABLE IF NOT EXISTS room_images(
    id INTEGER PRIMARY KEY AUTOINCREMENT, room_id TEXT NOT NULL, file TEXT NOT NULL, caption TEXT DEFAULT '', sort INTEGER DEFAULT 0, created_at TEXT);
  CREATE INDEX IF NOT EXISTS idx_ri_room ON room_images(room_id, sort);
  CREATE TABLE IF NOT EXISTS settings(k TEXT PRIMARY KEY, v TEXT);
  CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE, name TEXT, password_hash TEXT, initial_pw INTEGER DEFAULT 0);
  ");
  $cols = array_column($pdo->query('PRAGMA table_info(payments)')->fetchAll(), 'name');
  foreach (['coin' => "TEXT DEFAULT ''", 'network' => "TEXT DEFAULT ''", 'tx_hash' => "TEXT DEFAULT ''", 'crypto_amount' => 'REAL DEFAULT 0'] as $c => $def)
    if (!in_array($c, $cols, true)) $pdo->exec("ALTER TABLE payments ADD COLUMN $c $def");
  $ucols = array_column($pdo->query('PRAGMA table_info(users)')->fetchAll(), 'name');
  if (!in_array('initial_pw', $ucols, true)) $pdo->exec('ALTER TABLE users ADD COLUMN initial_pw INTEGER DEFAULT 0');
  $bcols = array_column($pdo->query('PRAGMA table_info(bookings)')->fetchAll(), 'name');
  if (!in_array('token', $bcols, true)) $pdo->exec("ALTER TABLE bookings ADD COLUMN token TEXT DEFAULT ''");
}

/** Rooms created before galleries existed keep their single photo as the first gallery image. */
function db_backfill_room_images(PDO $pdo): void {
  $rows = $pdo->query("SELECT r.id, r.img FROM rooms r WHERE r.img != '' AND NOT EXISTS (SELECT 1 FROM room_images i WHERE i.room_id = r.id)")->fetchAll();
  $ins = $pdo->prepare('INSERT INTO room_images(room_id,file,caption,sort,created_at) VALUES(?,?,?,0,?)');
  foreach ($rows as $r) if (is_file(__DIR__ . '/../assets/img/' . $r['img'])) $ins->execute([$r['id'], $r['img'], '', date('Y-m-d H:i:s')]);
}

/** Every booking gets a secret token so its receipt link cannot be guessed from the reference. */
function db_backfill_tokens(PDO $pdo): void {
  $ids = $pdo->query("SELECT id FROM bookings WHERE token IS NULL OR token=''")->fetchAll(PDO::FETCH_COLUMN);
  if (!$ids) return;
  $up = $pdo->prepare('UPDATE bookings SET token=? WHERE id=?');
  foreach ($ids as $id) $up->execute([bin2hex(random_bytes(16)), $id]);
}

function db_seed(PDO $pdo): void {
  $now = date('Y-m-d H:i:s');
  $cfg = require __DIR__ . '/config.php';
  $data = require __DIR__ . '/data.php';

  $st = $pdo->prepare('INSERT OR IGNORE INTO rooms(id,name,blurb,beds,guests,units,price,img,amenities,featured,active,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,1,?)');
  foreach ($data['rooms'] as $r) {
    $st->execute([$r['id'], $r['name'], $r['blurb'], $r['beds'] ?? '', $r['guests'], $r['units'] ?? 1, $r['price'], $r['img'], json_encode($r['amenities'] ?? []), !empty($r['featured']) ? 1 : 0, $now]);
  }

  // First admin comes from .env (ADMIN_EMAIL / ADMIN_PASSWORD). No password is ever hard-coded in production.
  $pass = (string)env('ADMIN_PASSWORD', '');
  if ($pass === '' && env('APP_ENV', 'production') !== 'production') $pass = 'ChangeMe123!'; // local development only
  if ($pass !== '') {
    $pdo->prepare('INSERT OR IGNORE INTO users(email,name,password_hash,initial_pw) VALUES(?,?,?,1)')
        ->execute([strtolower(trim((string)(env('ADMIN_EMAIL') ?: $cfg['email']))), 'Administrator', password_hash($pass, PASSWORD_DEFAULT)]);
  }

  // Import bookings saved by the first version of the booking flow, if any.
  $old = __DIR__ . '/../storage/bookings.jsonl';
  if (is_file($old)) {
    $ins = $pdo->prepare('INSERT OR IGNORE INTO bookings(ref,room_id,room_name,checkin,checkout,nights,guests,name,email,phone,notes,subtotal,total,status,source,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach (explode("\n", trim((string)file_get_contents($old))) as $line) {
      $b = json_decode($line, true); if (!is_array($b)) continue;
      $ins->execute([$b['ref'], $b['room'], $b['room_name'] ?? '', $b['checkin'], $b['checkout'], $b['nights'], $b['guests'], $b['name'] ?? '', $b['email'] ?? '', $b['phone'] ?? '', $b['notes'] ?? '', $b['total'] ?? 0, $b['total'] ?? 0, $b['status'] ?? 'pending', 'web', substr(str_replace('T', ' ', (string)($b['at'] ?? $now)), 0, 19)]);
    }
    @rename($old, $old . '.imported');
  }
}
