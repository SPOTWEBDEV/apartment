<?php
require_once __DIR__ . '/_layout.php';
admin_open('Messages', 'inquiries', 'Contact messages, table reservations and event inquiries from the website.');
?>
<div class="card overflow-hidden">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
    <div id="tabs" class="flex flex-wrap gap-1" role="tablist" aria-label="Filter by status"></div>
    <div class="flex flex-wrap gap-2">
      <select id="type" class="input w-auto" aria-label="Type"><option value="">All types</option><option value="table">Table reservations</option><option value="stay">Stay questions</option><option value="wedding">Weddings</option><option value="corporate">Corporate events</option><option value="other">General</option></select>
      <input id="q" type="search" placeholder="Search name, email or message" class="input w-64" aria-label="Search messages">
    </div>
  </div>
  <div class="overflow-x-auto"><table class="w-full min-w-[52rem]">
    <thead class="bg-slate-50"><tr><th class="th">From</th><th class="th">Type</th><th class="th">Details</th><th class="th">Received</th><th class="th">Status</th></tr></thead>
    <tbody id="rows" class="divide-y divide-slate-100"></tbody></table></div>
  <div id="pager" class="flex items-center justify-between border-t border-slate-100 px-4 py-3"></div>
</div>
<?php admin_close(['assets/inquiries.js']);
