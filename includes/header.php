<?php
require_once __DIR__ . '/bootstrap.php';
$page_title = $page_title ?? cfg('site_name') . ' | ' . cfg('tagline');
$page_desc  = $page_desc ?? cfg('description');
$active     = $active ?? '';
$solid      = $solid ?? true;   // false on the landing page (transparent over hero)
$links = [
  ['about.php','About','about'], ['rooms.php','Rooms','rooms'], ['experience.php','Experience','experience'], ['dining.php','Dining','dining'],
  ['events.php','Weddings and corporate','events'], ['gallery.php','Gallery','gallery'], ['contact.php','Contact','contact'],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?></title>
<meta name="robots" content="<?= e($robots ?? 'index,follow') ?>">
<meta name="description" content="<?= e($page_desc) ?>">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($page_desc) ?>">
<meta property="og:image" content="assets/img/hero-raft.jpg">
<link rel="icon" href="assets/img/logo.png">
<script>document.documentElement.classList.add('js');if(!matchMedia('(prefers-reduced-motion: reduce)').matches)document.documentElement.classList.add('motion');</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<!-- Dev only. For production compile Tailwind: npx tailwindcss -o assets/css/app.css --minify -->
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
tailwind.config = { theme: { extend: {
  fontFamily: { display: ['Fraunces','Georgia','serif'], sans: ['Figtree','system-ui','sans-serif'] },
  colors: { lagoon:'#0B2A43', tide:'#14405F', bronze:'#A8743B', sunset:'#F2A65A', foam:'#EEF4F3', sand:'#F7F5F1' }
}}}
</script>
<link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="bg-sand font-sans text-lagoon antialiased">

<header id="nav" data-solid="<?= $solid ? '1' : '0' ?>" class="fixed inset-x-0 top-0 z-50 <?= $solid ? 'is-solid' : '' ?>">
  <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3 lg:px-8">
    <a href="index.php" aria-label="<?= e(cfg('site_name')) ?> home"><img src="assets/img/logo.png" alt="<?= e(cfg('site_name')) ?>" class="logo"></a>
    <nav class="hidden items-center gap-6 text-sm font-medium xl:flex" aria-label="Main">
      <?php foreach ($links as [$href,$label,$key]): ?>
        <a href="<?= $href ?>" class="nl"<?= $key && $key === $active ? ' aria-current="page"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="flex items-center gap-3">
      <a href="rooms.php#booking" class="rounded-full bg-sunset px-5 py-2.5 text-sm font-semibold text-lagoon hover:bg-bronze hover:text-white">Book a stay</a>
      <button id="menuBtn" class="rounded-full p-2 xl:hidden" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
    </div>
  </div>
  <div id="mobileMenu" class="hidden bg-lagoon px-5 pb-6 pt-2 text-white xl:hidden">
    <?php foreach ($links as [$href,$label,$key]): ?>
      <a href="<?= $href ?>" class="block border-b border-white/10 py-3"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
</header>
