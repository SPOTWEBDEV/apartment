<?php
declare(strict_types=1);
require_once __DIR__ . '/env.php';

/**
 * Sends a plain-text email. Uses SMTP when MAIL_HOST is set in .env, otherwise PHP's mail().
 * Returns true when the message was accepted. Failures are logged without credentials.
 */
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool {
  $one = fn(string $s): string => trim(str_replace(["\r", "\n"], ' ', $s));   // blocks header injection
  $to = $one($to);
  if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
  $fromEmail = $one((string)(env('MAIL_FROM') ?: cfg('email')));
  $fromName = $one((string)(env('MAIL_FROM_NAME') ?: cfg('site_name')));
  $encSubject = '=?UTF-8?B?' . base64_encode($one($subject)) . '?=';
  $encName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
  $common = ['Date: ' . date('r'), "From: $encName <$fromEmail>", 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64'];
  if ($replyTo && filter_var($one($replyTo), FILTER_VALIDATE_EMAIL)) $common[] = 'Reply-To: <' . $one($replyTo) . '>';
  $payload = rtrim(chunk_split(base64_encode($body)));

  $host = (string)env('MAIL_HOST', '');
  if ($host === '') return @mail($to, $encSubject, $payload, implode("\r\n", $common));

  $headers = array_merge(["To: <$to>", "Subject: $encSubject", 'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . (parse_url(base_host(), PHP_URL_HOST) ?: 'localhost') . '>'], $common);
  $data = preg_replace('/^\./m', '..', implode("\r\n", $headers) . "\r\n\r\n" . $payload);

  $enc = strtolower((string)env('MAIL_ENCRYPTION', 'tls'));
  $port = (int)env('MAIL_PORT', $enc === 'ssl' ? 465 : 587);
  $fp = @stream_socket_client(($enc === 'ssl' ? 'ssl://' : 'tcp://') . "$host:$port", $errno, $errstr, 10);
  if (!$fp) { error_log("send_mail: cannot connect to $host:$port ($errstr)"); return false; }
  stream_set_timeout($fp, 15);
  $read = function () use ($fp): string {
    $out = '';
    while (($l = fgets($fp, 1024)) !== false) { $out .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; }
    return $out;
  };
  $cmd = function (string $c, array $ok) use ($fp, $read): bool {
    if ($c !== '') fwrite($fp, $c . "\r\n");
    $r = $read();
    if (!in_array(substr($r, 0, 3), $ok, true)) { error_log('send_mail: unexpected SMTP reply: ' . trim(substr($r, 0, 120))); return false; }
    return true;
  };
  $helo = 'localhost';
  $ok = $cmd('', ['220']) && $cmd("EHLO $helo", ['250']);
  if ($ok && $enc === 'tls') $ok = $cmd('STARTTLS', ['220']) && @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) && $cmd("EHLO $helo", ['250']);
  $user = (string)env('MAIL_USER', ''); $pass = (string)env('MAIL_PASS', '');
  if ($ok && $user !== '') $ok = $cmd('AUTH LOGIN', ['334']) && $cmd(base64_encode($user), ['334']) && $cmd(base64_encode($pass), ['235']);
  $ok = $ok && $cmd("MAIL FROM:<$fromEmail>", ['250']) && $cmd("RCPT TO:<$to>", ['250', '251']) && $cmd('DATA', ['354']);
  if ($ok) { fwrite($fp, $data . "\r\n.\r\n"); $ok = $cmd('', ['250']); }
  if (is_resource($fp)) { @fwrite($fp, "QUIT\r\n"); fclose($fp); }
  return $ok;
}
function base_host(): string { return (string)env('APP_URL', 'http://localhost'); }
