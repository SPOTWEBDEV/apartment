<?php
require_once __DIR__ . '/_layout.php';
admin_open('Discounts', 'discounts', 'Codes guests can use in the booking flow.', '<button id="addCode" class="btn-primary">New code</button>');
?>
<div class="card overflow-hidden"><div class="overflow-x-auto"><table class="w-full min-w-[52rem]">
  <thead class="bg-slate-50"><tr><th class="th">Code</th><th class="th">Discount</th><th class="th">Valid</th><th class="th">Used</th><th class="th">Status</th><th class="th"></th></tr></thead>
  <tbody id="rows" class="divide-y divide-slate-100"><tr><td colspan="6" class="p-4"><div class="skel h-32 rounded-xl"></div></td></tr></tbody></table></div></div>
<?php admin_close(['assets/discounts.js']);
