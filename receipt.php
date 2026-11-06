<?php
require_once __DIR__ . '/includes/receipt.php';
header('X-Robots-Tag: noindex, nofollow');
$b = receipt_booking((string)($_GET['ref'] ?? ''), (string)($_GET['t'] ?? ''));
if (!$b) {
  http_response_code(404);
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><meta charset="utf-8"><title>Receipt not found</title><body style="font-family:system-ui;max-width:32rem;margin:15vh auto;padding:0 1.5rem;color:#0B2A43"><h1>Receipt not found</h1><p>This receipt link is not valid. Use the link from your booking confirmation email, or contact us with your booking reference.</p><p><a href="index.php">Back to the website</a></p>';
  exit;
}
$pdf = receipt_pdf($b);
header('Content-Type: application/pdf');
header('Content-Disposition: ' . (isset($_GET['inline']) ? 'inline' : 'attachment') . '; filename="receipt-' . preg_replace('/[^A-Za-z0-9-]/', '', $b['ref']) . '.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');
echo $pdf;
