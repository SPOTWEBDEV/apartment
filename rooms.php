<?php
require __DIR__ . '/includes/bootstrap.php';
$data = require __DIR__ . '/includes/data.php';
$page_title = 'Rooms and booking | ' . cfg('site_name');
$page_desc = 'Check dates and request a casita, villa or room at ' . cfg('site_name') . '.';
$active = 'rooms';
$extra_scripts = ['assets/js/booking.js'];
include __DIR__ . '/includes/header.php';
$fieldLabel = 'mb-1 block text-sm font-medium';
?>
<main>
<section class="bg-lagoon pb-16 pt-36 text-white">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <h1 data-reveal class="font-display text-5xl sm:text-6xl">Rooms and booking</h1>
    <p data-reveal class="mt-5 max-w-xl text-lg text-white/80">Pick your dates, choose a room and send a request. No payment is taken online.</p>
  </div>
</section>

<!-- Booking flow -->
<section id="booking" class="mx-auto max-w-5xl scroll-mt-20 px-5 py-16">
  <div id="flow" class="rounded-3xl bg-white p-6 shadow-sm sm:p-10">
    <ol class="mb-8 flex flex-wrap gap-x-8 gap-y-3 border-b border-lagoon/10 pb-6 text-sm" aria-label="Booking steps">
      <li class="step is-current" data-step="1" aria-current="step"><span class="dot">1</span>Dates</li>
      <li class="step" data-step="2"><span class="dot">2</span>Room</li>
      <li class="step" data-step="3"><span class="dot">3</span>Your details</li>
      <li class="step" data-step="4"><span class="dot">4</span>Done</li>
    </ol>

    <!-- 1: dates -->
    <div data-panel="1">
      <h2 class="font-display text-3xl">When are you staying?</h2>
      <form id="dateForm" class="mt-6 grid gap-4 sm:grid-cols-3" novalidate>
        <div><label class="<?= $fieldLabel ?>" for="checkin">Check-in</label><input class="field" id="checkin" name="checkin" type="date" required></div>
        <div><label class="<?= $fieldLabel ?>" for="checkout">Check-out</label><input class="field" id="checkout" name="checkout" type="date" required></div>
        <div><label class="<?= $fieldLabel ?>" for="guests">Guests</label><input class="field" id="guests" name="guests" type="number" min="1" max="30" value="2"></div>
        <button class="rounded-full bg-bronze px-7 py-3.5 font-semibold text-white hover:bg-lagoon disabled:opacity-60 sm:col-span-3 sm:w-fit">Show available rooms</button>
      </form>
      <p id="dateMsg" class="mt-4 min-h-6 text-sm text-red-700" role="status" aria-live="polite"></p>
    </div>

    <!-- 2: room -->
    <div data-panel="2" hidden>
      <h2 class="font-display text-3xl">Choose a room</h2>
      <p id="pickSummary" class="mt-2 text-lagoon/70"></p>
      <div id="roomResults" class="mt-6 space-y-4"></div>
      <button type="button" data-back="1" class="mt-6 font-medium text-bronze underline underline-offset-4">Change dates</button>
    </div>

    <!-- 3: details -->
    <div data-panel="3" hidden>
      <h2 class="font-display text-3xl">Your details</h2>
      <div class="mt-6 grid gap-8 lg:grid-cols-5">
        <form id="guestForm" class="space-y-4 lg:col-span-3" novalidate>
          <div><label class="<?= $fieldLabel ?>" for="g_name">Full name</label><input class="field" id="g_name" name="name" autocomplete="name"><p class="err mt-1 text-sm text-red-700" data-for="name"></p></div>
          <div><label class="<?= $fieldLabel ?>" for="g_email">Email</label><input class="field" id="g_email" name="email" type="email" autocomplete="email"><p class="err mt-1 text-sm text-red-700" data-for="email"></p></div>
          <div><label class="<?= $fieldLabel ?>" for="g_phone">Phone or WhatsApp</label><input class="field" id="g_phone" name="phone" type="tel" autocomplete="tel"><p class="err mt-1 text-sm text-red-700" data-for="phone"></p></div>
          <div><label class="<?= $fieldLabel ?>" for="g_notes">Requests (optional)</label><textarea class="field" id="g_notes" name="notes" rows="3" placeholder="Arrival time, pets, celebrations"></textarea></div>
          <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
          <div class="flex flex-wrap items-center gap-4">
            <button class="rounded-full bg-bronze px-7 py-3.5 font-semibold text-white hover:bg-lagoon disabled:opacity-60">Send booking request</button>
            <button type="button" data-back="2" class="font-medium text-bronze underline underline-offset-4">Choose another room</button>
          </div>
          <p id="guestMsg" class="min-h-6 text-sm text-red-700" role="status" aria-live="polite"></p>
        </form>
        <aside id="summary" class="h-fit rounded-2xl bg-foam p-6 lg:col-span-2" aria-label="Booking summary"></aside>
      </div>
    </div>

    <!-- 4: done -->
    <div data-panel="4" hidden>
      <h2 class="font-display text-3xl">Request sent</h2>
      <p class="mt-3 text-lg">Your reference is <strong id="refCode" class="font-display text-bronze"></strong>.</p>
      <p class="mt-3 max-w-xl text-lagoon/80">This is a request, not a confirmed booking. The reservations team confirms by email or WhatsApp, usually within a day. We emailed you a copy.</p>
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="index.php" class="rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">Back to home</a>
        <a href="<?= e(whatsapp_url('Hello, I just sent a booking request.')) ?>" rel="noopener" class="rounded-full border border-lagoon/25 px-7 py-3 font-semibold hover:bg-lagoon hover:text-white">Message us on WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<!-- Room details -->
<section class="mx-auto max-w-7xl space-y-16 px-5 pb-24 lg:px-8">
  <h2 data-reveal class="font-display text-4xl sm:text-5xl">About the rooms</h2>
  <?php foreach ($data['rooms'] as $i => $r): ?>
  <article id="<?= e($r['id']) ?>" class="grid scroll-mt-24 items-center gap-8 lg:grid-cols-2 lg:gap-14">
    <div data-clip class="ph aspect-[4/3] rounded-3xl bg-cover bg-center <?= $i % 2 ? 'lg:order-2' : '' ?>" style="background-image:url('assets/img/<?= e($r['img']) ?>'),linear-gradient(135deg,#14405F,#2E6C8A 55%,#F2A65A)" role="img" aria-label="<?= e($r['name']) ?>"></div>
    <div data-reveal>
      <h3 class="font-display text-3xl"><?= e($r['name']) ?></h3>
      <p class="mt-2 text-lagoon/70"><?= e($r['beds']) ?>. Sleeps up to <?= (int)$r['guests'] ?>.</p>
      <p class="mt-4 max-w-md text-lg text-lagoon/80"><?= e($r['blurb']) ?></p>
      <ul class="mt-5 grid max-w-md gap-x-6 gap-y-2 text-lagoon/80 sm:grid-cols-2">
        <?php foreach ($r['amenities'] as $a): ?><li class="border-l-2 border-bronze pl-3"><?= e($a) ?></li><?php endforeach; ?>
      </ul>
      <p class="mt-6">From <strong class="font-display text-2xl"><?= peso($r['price']) ?></strong> a night</p>
      <button type="button" data-pick="<?= e($r['id']) ?>" class="mt-4 rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">Check dates for this room</button>
    </div>
  </article>
  <?php endforeach; ?>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
