<?php
// Create an admin or reset a password:  php tools/create-admin.php you@example.com "a long password"
require __DIR__ . '/../includes/bootstrap.php';
if (PHP_SAPI !== 'cli') exit('Run this from the command line.');
[$self, $email, $pass] = $argv + [null, null, null];
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$pass || strlen($pass) < 10) {
  exit("Usage: php tools/create-admin.php email@example.com \"password of 10+ characters\"\n");
}
$pdo = db(); $email = strtolower($email);
$pdo->prepare('INSERT INTO users(email,name,password_hash,initial_pw) VALUES(?,?,?,0) ON CONFLICT(email) DO UPDATE SET password_hash=excluded.password_hash, initial_pw=0')
    ->execute([$email, 'Administrator', password_hash($pass, PASSWORD_DEFAULT)]);
echo "Admin ready: $email\n";
