<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
admin_session();
if (admin_user()) { header('Location: index.php'); exit; }
$error = '';
$noAdmin = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_SESSION['lock_until'] ?? 0) > time()) {
    $error = 'Too many attempts. Try again in a few minutes.';
  } elseif (!csrf_ok()) {
    $error = 'Your session expired. Reload the page and try again.';
  } else {
    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([strtolower(trim((string)($_POST['email'] ?? '')))]);
    $u = $st->fetch();
    if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
      session_regenerate_id(true);
      $_SESSION['admin'] = ['id' => (int)$u['id'], 'email' => $u['email'], 'name' => $u['name']];
      $_SESSION['fails'] = 0;
      header('Location: index.php'); exit;
    }
    $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
    if ($_SESSION['fails'] >= 8) { $_SESSION['lock_until'] = time() + 300; $_SESSION['fails'] = 0; }
    sleep(1);
    $error = 'Email or password is incorrect.';
  }
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | <?= e(cfg('site_name')) ?> Admin</title>
<meta name="robots" content="noindex">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['Figtree','system-ui','sans-serif']},colors:{lagoon:'#0B2A43',tide:'#14405F',bronze:'#A8743B',sunset:'#F2A65A'}}}}</script>
</head>
<body class="grid min-h-screen bg-slate-50 font-sans text-lagoon antialiased lg:grid-cols-2">
  <div class="relative hidden overflow-hidden bg-lagoon lg:block">
    <div class="absolute inset-0 opacity-60" style="background:radial-gradient(60% 50% at 30% 20%,#F2A65A55,transparent),radial-gradient(50% 50% at 80% 90%,#2E6C8A88,transparent)"></div>
    <div class="relative flex h-full flex-col justify-between p-12 text-white">
      <img src="../assets/img/logo.png" alt="<?= e(cfg('site_name')) ?>" class="h-12 w-auto self-start" style="filter:brightness(0) invert(1)">
      <div><p class="max-w-md text-3xl font-semibold leading-snug">Bookings, rooms and payments in one place.</p>
      <p class="mt-3 max-w-sm text-white/70">Confirm requests, record payments and keep rates and codes up to date.</p></div>
    </div>
  </div>
  <main class="flex items-center justify-center p-6">
    <form method="post" class="w-full max-w-sm space-y-5">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div><h1 class="text-2xl font-bold">Sign in</h1><p class="mt-1 text-sm text-slate-500">Use your admin account for <?= e(cfg('site_name')) ?>.</p></div>
      <?php if ($noAdmin): ?><p class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">There is no admin account yet. Set ADMIN_EMAIL and ADMIN_PASSWORD in your .env file, then run <code>php tools/create-admin.php</code>.</p><?php endif; ?>
      <?php if ($error): ?><p class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><?= e($error) ?></p><?php endif; ?>
      <div><label class="mb-1 block text-xs font-semibold text-slate-600" for="email">Email</label>
        <input id="email" name="email" type="email" required autocomplete="username" autofocus value="<?= e($_POST['email'] ?? '') ?>" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-bronze focus:outline-none focus:ring-2 focus:ring-bronze/25"></div>
      <div><label class="mb-1 block text-xs font-semibold text-slate-600" for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-bronze focus:outline-none focus:ring-2 focus:ring-bronze/25"></div>
      <button class="w-full rounded-lg bg-bronze py-2.5 text-sm font-semibold text-white hover:bg-[#8f6030]">Sign in</button>
      <p class="text-center text-xs text-slate-400"><a href="../index.php" class="hover:text-lagoon">Back to the website</a></p>
    </form>
  </main>
</body></html>
