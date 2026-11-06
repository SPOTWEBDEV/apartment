<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

function icon(string $n, string $c = 'h-5 w-5'): string {
  $p = [
    'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
    'bookings'  => '<path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1zM6 6h12v14H6zM9.5 11h5M9.5 15h3"/>',
    'calendar'  => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4M8 13h2M14 13h2M8 17h2"/>',
    'rooms'     => '<path d="M3 20V6M3 14h18v6M21 14v-2a3 3 0 0 0-3-3h-8v5"/><circle cx="7" cy="11" r="1.5"/>',
    'payments'  => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
    'discounts' => '<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2"/><circle cx="17" cy="17" r="2"/>',
    'settings'  => '<path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>',
    'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    'external'  => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
    'logout'    => '<path d="M9 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h4M16 8l4 4-4 4M20 12H9"/>',
  ][$n] ?? '';
  return '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/** Prints the page shell up to the start of the content area. */
function admin_open(string $title, string $active, string $sub = '', string $actions = ''): void {
  $u = require_admin();
  $badge = ['bookings' => 0, 'payments' => 0, 'inquiries' => 0];
  try {
    $badge['bookings'] = (int)db()->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
    $badge['payments'] = (int)db()->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn();
    $badge['inquiries'] = (int)db()->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();
  } catch (Throwable $e) {}
  $nav = [['index.php','Dashboard','dashboard'],['bookings.php','Bookings','bookings'],['calendar.php','Calendar','calendar'],['rooms.php','Rooms','rooms'],
          [$badge['payments'] ? 'payments.php?status=pending' : 'payments.php','Payments','payments'],['inquiries.php','Messages','inquiries'],['discounts.php','Discounts','discounts'],['settings.php','Settings','settings']];
  $initial = strtoupper(mb_substr($u['name'] ?: $u['email'], 0, 1));
  ?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e(cfg('site_name')) ?> Admin</title>
<meta name="robots" content="noindex">
<meta name="csrf" content="<?= e(csrf_token()) ?>"><meta name="cur" content="<?= e(cfg('currency')) ?>"><meta name="max-images" content="<?= (int)cfg('max_room_images', 10) ?>">
<link rel="icon" href="../assets/img/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['Figtree','system-ui','sans-serif']},colors:{lagoon:'#0B2A43',tide:'#14405F',bronze:'#A8743B',sunset:'#F2A65A'}}}}</script>
<style type="text/tailwindcss">
@layer components{
  .card{@apply rounded-2xl border border-slate-200 bg-white shadow-[0_1px_2px_rgba(16,24,40,.05)]}
  .btn{@apply inline-flex items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60}
  .btn-primary{@apply btn bg-bronze text-white hover:bg-[#8f6030]}
  .btn-ghost{@apply btn border border-slate-200 bg-white text-lagoon hover:bg-slate-50}
  .btn-danger{@apply btn text-red-700 hover:bg-red-50}
  .btn-sm{@apply px-2.5 py-1.5 text-xs}
  .input{@apply w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-lagoon placeholder:text-slate-400 focus:border-bronze focus:outline-none focus:ring-2 focus:ring-bronze/25}
  .lbl{@apply mb-1 block text-xs font-semibold text-slate-600}
  .badge{@apply inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold}
  .th{@apply px-4 py-3 text-left text-xs font-semibold text-slate-500}
  .td{@apply px-4 py-3.5 text-sm}
  .navlink{@apply flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/70 transition hover:bg-white/10 hover:text-white}
  .navlink[aria-current=page]{@apply bg-white/10 text-white shadow-[inset_3px_0_0_#F2A65A]}
}
</style>
<style>:focus-visible{outline:2px solid #F2A65A;outline-offset:2px}.skel{background:linear-gradient(90deg,#f1f5f9,#e2e8f0,#f1f5f9);background-size:200% 100%;animation:sk 1.2s infinite}@keyframes sk{to{background-position:-200% 0}}@media(prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}</style>
</head>
<body class="bg-slate-50 font-sans text-lagoon antialiased">
<div id="side" class="fixed inset-y-0 left-0 z-30 flex w-64 -translate-x-full flex-col bg-lagoon px-4 py-5 transition-transform lg:translate-x-0">
  <a href="index.php" class="px-2"><img src="../assets/img/logo.png" alt="<?= e(cfg('site_name')) ?>" class="h-10 w-auto" style="filter:brightness(0) invert(1)"></a>
  <p class="mt-1 px-2 text-xs text-white/50">Admin</p>
  <nav class="mt-8 space-y-1" aria-label="Admin">
    <?php foreach ($nav as [$href,$label,$key]): ?>
      <a href="<?= $href ?>" class="navlink"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= icon($key === 'inquiries' ? 'mail' : $key) ?><span class="flex-1"><?= $label ?></span>
        <?php if (!empty($badge[$key])): ?><span class="rounded-full bg-sunset px-2 py-0.5 text-xs font-bold text-lagoon"><?= $badge[$key] ?></span><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="mt-auto space-y-1">
    <a href="../index.php" target="_blank" rel="noopener" class="navlink"><?= icon('external') ?>View website</a>
    <form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="navlink w-full"><?= icon('logout') ?>Sign out</button></form>
  </div>
</div>
<div id="sideScrim" class="fixed inset-0 z-20 hidden bg-lagoon/50 lg:hidden"></div>

<div class="lg:pl-64">
  <header class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white/85 px-4 py-3 backdrop-blur lg:px-8">
    <button id="sideBtn" class="rounded-lg p-2 hover:bg-slate-100 lg:hidden" aria-label="Open menu"><?= icon('menu') ?></button>
    <p class="hidden text-sm text-slate-500 lg:block"><?= date('l, j F Y') ?></p>
    <div class="flex items-center gap-3">
      <div class="text-right leading-tight"><p class="text-sm font-semibold"><?= e($u['name'] ?: 'Admin') ?></p><p class="text-xs text-slate-500"><?= e($u['email']) ?></p></div>
      <span class="grid h-9 w-9 place-items-center rounded-full bg-bronze text-sm font-bold text-white" aria-hidden="true"><?= e($initial) ?></span>
    </div>
  </header>
  <main class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
      <div><h1 class="text-2xl font-bold tracking-tight"><?= e($title) ?></h1><?php if ($sub): ?><p class="mt-1 text-sm text-slate-500"><?= e($sub) ?></p><?php endif; ?></div>
      <div class="flex flex-wrap gap-2"><?= $actions ?></div>
    </div>
<?php }

function admin_close(array $scripts = []): void { ?>
  </main>
</div>

<div id="scrim" class="fixed inset-0 z-40 hidden bg-lagoon/40 backdrop-blur-[2px]"></div>
<aside id="drawer" class="fixed inset-y-0 right-0 z-50 flex w-full max-w-lg translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300" role="dialog" aria-modal="true" aria-labelledby="drawerTitle" aria-hidden="true">
  <header class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
    <h2 id="drawerTitle" class="text-lg font-semibold"></h2>
    <button id="drawerClose" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close panel"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
  </header>
  <div id="drawerBody" class="flex-1 overflow-y-auto p-6"></div>
</aside>
<dialog id="dlg" class="w-[min(24rem,92vw)] rounded-2xl p-6 shadow-2xl backdrop:bg-lagoon/40">
  <h2 id="dlgTitle" class="text-lg font-semibold"></h2><p id="dlgText" class="mt-2 text-sm text-slate-600"></p>
  <div class="mt-6 flex justify-end gap-2"><button id="dlgNo" class="btn-ghost">Cancel</button><button id="dlgYes" class="btn-primary"></button></div>
</dialog>
<div id="toasts" class="fixed bottom-4 right-4 z-[60] space-y-2" role="status" aria-live="polite"></div>
<script src="assets/admin.js"></script>
<?php foreach ($scripts as $s): ?><script src="<?= e($s) ?>"></script>
<?php endforeach; ?>
</body></html>
<?php }
