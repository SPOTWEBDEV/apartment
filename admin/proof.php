<?php
// Streams a guest's payment screenshot to signed-in admins only (the file itself lives in the private storage folder).
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
if (!admin_user()) { http_response_code(403); exit('Sign in to view this file.'); }
$s = db()->prepare('SELECT proof FROM payments WHERE id=?'); $s->execute([(int)($_GET['id'] ?? 0)]);
$file = basename((string)$s->fetchColumn());
$path = __DIR__ . '/../storage/proofs/' . $file;
$type = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'][strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? null;
if ($file === '' || !$type || !is_file($path)) { http_response_code(404); exit('File not found.'); }
header('Content-Type: ' . $type); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
header('Content-Disposition: inline; filename="' . $file . '"'); header('Content-Length: ' . filesize($path));
readfile($path);
