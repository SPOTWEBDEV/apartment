<?php
require_once __DIR__ . '/_layout.php';
admin_open('Dashboard', 'dashboard', 'How the resort is doing this month.', '<a href="bookings.php?new=1" class="btn-primary">New booking</a><a href="payments.php" class="btn-ghost">Record payment</a>');
?>
<div id="pwWarn" class="mb-6 hidden items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
  <p>You are still using the default password. Change it before sharing this admin with anyone.</p>
  <a href="settings.php#account" class="btn-ghost btn-sm shrink-0">Change password</a>
</div>
<div id="attention" class="mb-6 hidden flex-wrap items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900" role="status"></div>
<section id="kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Key numbers">
  <?php for ($i = 0; $i < 4; $i++): ?><div class="card skel h-32"></div><?php endfor; ?>
</section>

<section class="mt-6 grid gap-6 lg:grid-cols-3">
  <div class="card p-6 lg:col-span-2">
    <div class="flex items-center justify-between"><div><h2 class="font-semibold">Revenue</h2><p class="text-sm text-slate-500">Payments received, last 30 days</p></div><p id="revTotal" class="text-xl font-bold"></p></div>
    <div id="chart" class="relative mt-4"><div class="skel h-56 rounded-xl"></div></div>
  </div>
  <div class="card p-6">
    <h2 class="font-semibold">Bookings by status</h2><p class="text-sm text-slate-500">All time</p>
    <div id="statusBar" class="mt-5 flex h-3 overflow-hidden rounded-full bg-slate-100"></div>
    <ul id="statusList" class="mt-5 space-y-3 text-sm"></ul>
  </div>
</section>

<section class="mt-6 grid gap-6 lg:grid-cols-3">
  <div class="card"><div class="border-b border-slate-100 px-6 py-4"><h2 class="font-semibold">Needs your attention</h2><p class="text-sm text-slate-500">Pending requests</p></div><div id="pendingList" class="divide-y divide-slate-100"></div></div>
  <div class="card"><div class="border-b border-slate-100 px-6 py-4"><h2 class="font-semibold">Arriving this week</h2><p class="text-sm text-slate-500">Next 7 days</p></div><div id="arrivalList" class="divide-y divide-slate-100"></div></div>
  <div class="card"><div class="border-b border-slate-100 px-6 py-4"><h2 class="font-semibold">Recent payments</h2><p class="text-sm text-slate-500">Latest five</p></div><div id="payList" class="divide-y divide-slate-100"></div></div>
</section>
<?php admin_close(['assets/dashboard.js']);
