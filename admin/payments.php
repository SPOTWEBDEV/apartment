<?php
require_once __DIR__ . '/_layout.php';
admin_open('Payments', 'payments', 'Review payments guests send, and record money you received yourself.', '<a id="export" href="api/payments.php?action=export" class="btn-ghost">Export CSV</a>');
?>
<section id="sum" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><?php for ($i = 0; $i < 4; $i++): ?><div class="card skel h-28"></div><?php endfor; ?></section>
<div class="card mt-6 overflow-hidden">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
    <div id="tabs" class="flex flex-wrap gap-1" role="tablist" aria-label="Filter by status"></div>
    <div class="flex flex-wrap items-center gap-2">
      <input id="q" type="search" placeholder="Search guest, booking or reference" class="input w-64" aria-label="Search payments">
      <select id="method" class="input w-auto" aria-label="Method"><option value="">All methods</option></select>
      <input id="month" type="month" class="input w-auto" aria-label="Month">
    </div>
  </div>
  <div class="overflow-x-auto"><table class="w-full min-w-[54rem]">
    <thead class="bg-slate-50"><tr><th class="th">Date</th><th class="th">Guest</th><th class="th">Method</th><th class="th">Reference</th><th class="th text-right">Amount</th><th class="th">Status</th><th class="th"></th></tr></thead>
    <tbody id="rows" class="divide-y divide-slate-100"></tbody></table></div>
  <div id="pager" class="flex items-center justify-between border-t border-slate-100 px-4 py-3"></div>
</div>
<?php admin_close(['assets/payments.js']);