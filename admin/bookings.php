<?php
require_once __DIR__ . '/_layout.php';
admin_open('Bookings', 'bookings', 'Requests, confirmed stays and walk-ins.', '<button id="newBtn" class="btn-primary">New booking</button>');
?>
<div class="card overflow-hidden">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
    <div id="tabs" class="flex flex-wrap gap-1" role="tablist" aria-label="Filter by status"></div>
    <input id="q" type="search" placeholder="Search guest, reference or phone" class="input max-w-xs" aria-label="Search bookings">
  </div>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[56rem]">
      <thead class="bg-slate-50"><tr><th class="th">Guest</th><th class="th">Stay</th><th class="th">Room</th><th class="th text-right">Total</th><th class="th">Payment</th><th class="th">Status</th></tr></thead>
      <tbody id="rows" class="divide-y divide-slate-100"></tbody>
    </table>
  </div>
  <div id="pager" class="flex items-center justify-between border-t border-slate-100 px-4 py-3"></div>
</div>
<?php admin_close(['assets/bookings.js']);
