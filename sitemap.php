<?php
require_once __DIR__ . '/includes/receipt.php'; // base_url()
header('Content-Type: application/xml; charset=utf-8');
$base = base_url(1);
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach (['index', 'about', 'rooms', 'experience', 'dining', 'events', 'gallery', 'contact', 'privacy', 'terms'] as $p)
  echo '  <url><loc>' . htmlspecialchars("$base/$p.php", ENT_XML1) . '</loc><lastmod>' . date('Y-m-d', filemtime(__DIR__ . "/$p.php")) . "</lastmod></url>\n";
echo '</urlset>';
