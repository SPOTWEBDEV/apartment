<?php
require __DIR__ . '/includes/bootstrap.php';
$data = require __DIR__ . '/includes/data.php';
$page_title = 'Rooms and booking | ' . cfg('site_name');
$page_desc = 'Check dates and request a casita, villa or room at ' . cfg('site_name') . '.';
$active = 'rooms';
require_once __DIR__ . '/includes/gallery.php';
$extra_scripts = ['assets/js/receipt-image.js', 'assets/js/booking.js', 'assets/js/gallery.js'];
include __DIR__ . '/includes/header.php';
$fieldLabel = 'mb-1 block text-sm font-medium';
$hasRooms = (bool)rooms_all(true);
?>
<main>
<section class="bg-lagoon pb-16 pt-36 text-white">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <h1 data-reveal class="font-display text-5xl sm:text-6xl">Rooms and booking</h1>
    <p data-reveal class="mt-5 max-w-xl text-lg text-white/80">Pick your dates, choose a room and send a request. No payment is taken online.</p>
  </div>
</section>

<?php if (!$hasRooms): ?>
<section class="mx-auto max-w-3xl px-5 py-24 text-center"><h2 class="font-display text-3xl">Our rooms are being added</h2><p class="mt-3 text-lagoon/80">Online booking opens as soon as rooms are listed. Contact the reservations team and we will help you right away.</p><a href="contact.php" class="mt-8 inline-block rounded-full bg-bronze px-8 py-3.5 font-semibold text-white hover:bg-lagoon">Contact reservations</a></section>
<?php else: ?>
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
          <div><label class="<?= $fieldLabel ?>" for="g_phone">Phone number</label><input class="field" id="g_phone" name="phone" type="tel" autocomplete="tel"><p class="err mt-1 text-sm text-red-700" data-for="phone"></p></div>
          <div><label class="<?= $fieldLabel ?>" for="g_notes">Requests (optional)</label><textarea class="field" id="g_notes" name="notes" rows="3" placeholder="Arrival time, pets, celebrations"></textarea></div>
          <div><label class="<?= $fieldLabel ?>" for="g_code">Discount code (optional)</label>
            <div class="flex gap-2"><input class="field uppercase" id="g_code" name="code" autocomplete="off"><button type="button" id="applyCode" class="shrink-0 rounded-xl border border-lagoon/25 px-5 font-semibold hover:bg-lagoon hover:text-white">Apply</button></div>
            <p id="codeMsg" class="mt-1 min-h-5 text-sm" role="status" aria-live="polite"></p></div>
          <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
          <div class="flex flex-wrap items-center gap-4">
            <button class="rounded-full bg-bronze px-7 py-3.5 font-semibold text-white hover:bg-lagoon disabled:opacity-60">Send booking request</button>
            <button type="button" data-back="2" class="font-medium text-bronze underline underline-offset-4">Choose another room</button>
          </div>
          <p class="text-xs text-lagoon/60">By sending this request you agree to our <a class="underline" href="terms.php">Terms</a> and <a class="underline" href="privacy.php">Privacy Policy</a>.</p>
          <p id="guestMsg" class="min-h-6 text-sm text-red-700" role="status" aria-live="polite"></p>
        </form>
        <aside id="summary" class="h-fit rounded-2xl bg-foam p-6 lg:col-span-2" aria-label="Booking summary"></aside>
      </div>
    </div>

    <!-- 4: done -->
    <div data-panel="4" hidden>
      <h2 class="font-display text-3xl">Request sent</h2>
      <p class="mt-3 text-lg">Your reference is <strong id="refCode" class="font-display text-bronze"></strong>.</p>
      <p class="mt-3 max-w-xl text-lagoon/80">This is a request, not a confirmed booking. The reservations team confirms by email or phone, usually within a day. We emailed you a copy with a link to your receipt, so keep that email.</p>
      <div id="imagePreview" class="mt-8 hidden max-w-sm" aria-live="polite">
        <p class="mb-2 text-sm text-lagoon/70">Your receipt image downloads automatically. If it does not, press and hold the image to save it.</p>
        <img id="receiptPreview" alt="Your booking receipt" class="w-full rounded-xl border border-lagoon/15 shadow-sm">
      </div>
      <div id="cryptoBox" class="mt-8 hidden max-w-xl rounded-2xl border border-lagoon/15 bg-foam p-6">
        <h3 class="font-display text-xl">Prefer to pay with crypto?</h3>
        <p class="mt-1 text-sm text-lagoon/70">Send to one of these wallets, then message us the transaction hash with your reference <strong id="cryptoRef"></strong>. The reservations team confirms the amount received.</p>
        <ul id="cryptoList" class="mt-4 space-y-3"></ul>
      </div>
      <div class="mt-8 flex flex-wrap gap-3">
        <a id="receiptLink" href="#" class="rounded-full bg-bronze px-7 py-3 font-semibold text-white hover:bg-lagoon">Download receipt (PDF)</a>
        <a id="payLink" href="#" class="rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">Send payment details</a>
        <button type="button" id="receiptImage" class="rounded-full border border-lagoon/25 px-7 py-3 font-semibold hover:bg-lagoon hover:text-white">Save receipt as image</button>
        <a href="index.php" class="rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">Back to home</a>
        <?php if (messenger_url()): ?><a href="<?= e(messenger_url()) ?>" rel="noopener" class="rounded-full border border-lagoon/25 px-7 py-3 font-semibold hover:bg-lagoon hover:text-white">Message us on Messenger</a><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Room details -->
<section class="mx-auto max-w-7xl space-y-16 px-5 pb-24 lg:px-8">
  <h2 data-reveal class="font-display text-4xl sm:text-5xl">About the rooms</h2>
  <?php foreach (rooms_all(true) as $i => $r): ?>
  <article id="<?= e($r['id']) ?>" class="grid scroll-mt-24 items-center gap-8 lg:grid-cols-2 lg:gap-14">
    <?php render_gallery($r, 'min-w-0 ' . ($i % 2 ? 'lg:order-2' : '')); ?>
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
<?php endif; ?>
<script>window.SITE = <?= json_encode(['name'=>cfg('site_name'),'address'=>array_values((array)cfg('address',[])),'phone'=>cfg('phone'),'email'=>cfg('email'),'currency'=>cfg('currency'),'check_in'=>cfg('check_in'),'check_out'=>cfg('check_out')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;</script>
<?php render_lightbox(); ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
