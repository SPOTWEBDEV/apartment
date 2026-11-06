<?php
require_once __DIR__ . '/_layout.php';
admin_open('Calendar', 'calendar', 'Which rooms are taken, day by day. Click an empty day to add a booking.', '<a href="bookings.php?new=1" class="btn-primary">New booking</a>');
?>
<section id="stats" class="grid gap-4 sm:grid-cols-3"><?php for ($i = 0; $i < 3; $i++): ?><div class="card skel h-24"></div><?php endfor; ?></section>
<div class="card mt-6 overflow-hidden">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
    <div class="flex items-center gap-2">
      <button id="prev" class="btn-ghost btn-sm" aria-label="Earlier"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 5l-7 7 7 7"/></svg></button>
      <button id="today" class="btn-ghost btn-sm">Today</button>
      <button id="next" class="btn-ghost btn-sm" aria-label="Later"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg></button>
      <h2 id="range" class="ml-2 text-sm font-semibold"></h2>
    </div>
    <div class="flex flex-wrap items-center gap-4">
      <ul id="legend" class="hidden flex-wrap gap-3 text-xs text-slate-600 md:flex"></ul>
      <select id="span" class="input w-auto" aria-label="Days shown"><option value="14">14 days</option><option value="30" selected>30 days</option><option value="60">60 days</option></select>
    </div>
  </div>
  <div id="cal" class="overflow-x-auto"><div class="skel m-4 h-72 rounded-xl"></div></div>
</div>
<?php admin_close(['assets/calendar.js']);
