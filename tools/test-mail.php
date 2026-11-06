<?php
// Check your email settings:  php tools/test-mail.php you@example.com
require __DIR__ . '/../includes/bootstrap.php';
if (PHP_SAPI !== 'cli') exit('Run this from the command line.');
$to = $argv[1] ?? '';
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) exit("Usage: php tools/test-mail.php you@example.com\n");
echo send_mail($to, 'Test email from ' . cfg('site_name'), "Your email settings work.\n") ? "Sent. Check the inbox of $to.\n" : "Failed. See the PHP error log for the reason.\n";
