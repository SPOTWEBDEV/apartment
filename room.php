<?php
require __DIR__ . '/includes/bootstrap.php';

$id = (string)($_GET['room'] ?? '');
$room = null;
foreach (rooms_all(true) as $r) { if ($r['id'] === $id) { $room = $r; break; } }
if (!$room) { header('Location: rooms.php'); exit; }

$page_title = $room['name'] . ' | ' . cfg('site_name');
$page_desc  = $room['blurb'];
$active = 'rooms';
require_once __DIR__ . '/includes/gallery.php';
require_once __DIR__ . '/includes/payinfo.php';
$extra_scripts = ['assets/js/receipt-image.js', 'assets/js/pay.js', 'assets/js/booking.js', 'assets/js/gallery.js'];
include __DIR__ . '/includes/header.php';

$fieldLabel = 'mb-1 block text-sm font-medium';
$payOn = has_pay_info();
$steps = array_merge(['Dates', 'Confirm room', 'Your details'], $payOn ? ['Payment'] : [], ['Done']);
$doneN = count($steps);
?>
<main>
<section class="bg-lagoon pb-14 pt-36 text-white">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <a href="rooms.php" class="text-sm text-white/70 underline underline-offset-4 hover:text-white">All rooms</a>
    <h1 data-reveal class="mt-3 font-display text-5xl sm:text-6xl"><?= e($room['name']) ?></h1>
    <p class="mt-4 text-white/80"><?= e($room['beds']) ?> · Sleeps up to <?= (int)$room['guests'] ?> · from <?= peso($room['price']) ?> a night</p>
  </div>
</section>

<!-- Room details -->
<section class="mx-auto grid max-w-7xl items-start gap-10 px-5 py-16 lg:grid-cols-2 lg:gap-14 lg:px-8">
  <?php render_gallery($room, 'min-w-0'); ?>
  <div data-reveal>
    <p class="text-lg text-lagoon/80"><?= e($room['blurb']) ?></p>
    <ul class="mt-6 grid max-w-md gap-x-6 gap-y-2 text-lagoon/80 sm:grid-cols-2">
      <?php foreach ($room['amenities'] as $a): ?><li class="border-l-2 border-bronze pl-3"><?= e($a) ?></li><?php endforeach; ?>
    </ul>
    <p class="mt-6">From <strong class="font-display text-2xl"><?= peso($room['price']) ?></strong> a night</p>
    <a href="#booking" class="mt-6 inline-block rounded-full bg-bronze px-8 py-3.5 font-semibold text-white hover:bg-lagoon">Book this room</a>
  </div>
</section>

<!-- Booking flow -->
<section id="booking" class="mx-auto max-w-5xl scroll-mt-20 px-5 pb-24">
  <div id="flow" data-pay="<?= $payOn ? 1 : 0 ?>" data-room="<?= e($room['id']) ?>" class="rounded-3xl bg-white p-6 shadow-sm sm:p-10">
    <ol class="mb-8 flex flex-wrap gap-x-8 gap-y-3 border-b border-lagoon/10 pb-6 text-sm" aria-label="Booking steps">
      <?php foreach ($steps as $i => $label): ?>
      <li class="step<?= $i === 0 ? ' is-current' : '' ?>" data-step="<?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="step"' : '' ?>><span class="dot"><?= $i + 1 ?></span><?= e($label) ?></li>
      <?php endforeach; ?>
    </ol>

    <!-- 1: dates -->
    <div data-panel="1">
      <h2 class="font-display text-3xl">When are you staying?</h2>
      <form id="dateForm" class="mt-6 grid gap-4 sm:grid-cols-3" novalidate>
        <div><label class="<?= $fieldLabel ?>" for="checkin">Check-in</label><input class="field" id="checkin" name="checkin" type="date" required></div>
        <div><label class="<?= $fieldLabel ?>" for="checkout">Check-out</label><input class="field" id="checkout" name="checkout" type="date" required></div>
        <div><label class="<?= $fieldLabel ?>" for="guests">Guests</label><input class="field" id="guests" name="guests" type="number" min="1" max="<?= (int)$room['guests'] ?>" value="2"></div>
        <button class="rounded-full bg-bronze px-7 py-3.5 font-semibold text-white hover:bg-lagoon disabled:opacity-60 sm:col-span-3 sm:w-fit">Check this room</button>
      </form>
      <p id="dateMsg" class="mt-4 min-h-6 text-sm text-red-700" role="status" aria-live="polite"></p>
    </div>

    <!-- 2: confirm room -->
    <div data-panel="2" hidden>
      <h2 class="font-display text-3xl">Confirm your room</h2>
      <p id="pickSummary" class="mt-2 text-lagoon/70"></p>
      <div id="roomResults" class="mt-6 space-y-4"></div>
      <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2">
        <button type="button" data-back="1" class="font-medium text-bronze underline underline-offset-4">Change dates</button>
        <a href="rooms.php" class="font-medium text-bronze underline underline-offset-4">See other rooms</a>
      </div>
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
            <button type="button" data-back="2" class="font-medium text-bronze underline underline-offset-4">Back</button>
          </div>
          <p class="text-xs text-lagoon/60">By sending this request you agree to our <a class="underline" href="terms.php">Terms</a> and <a class="underline" href="privacy.php">Privacy Policy</a>.</p>
          <p id="guestMsg" class="min-h-6 text-sm text-red-700" role="status" aria-live="polite"></p>
        </form>
        <aside id="summary" class="h-fit rounded-2xl bg-foam p-6 lg:col-span-2" aria-label="Booking summary"></aside>
      </div>
    </div>

    <?php if ($payOn): ?>
    <!-- 4: payment -->
    <div data-panel="4" hidden>
      <h2 class="font-display text-3xl">Pay to secure your booking</h2>
      <p class="mt-2 max-w-2xl text-lagoon/75">Your request <strong id="payRef" class="font-mono text-bronze"></strong> is saved. Choose how you will pay, send the money, then upload your proof. Amount due: <strong id="payDue"></strong>.</p>
      <div class="mt-8"><?php render_pay_methods(); ?></div>
      <div class="mt-8 grid gap-10 lg:grid-cols-2">
        <div><?php render_payment_accounts(); ?></div>
        <div class="h-fit rounded-3xl bg-foam p-6">
          <h3 class="font-display text-2xl">I have made the payment</h3>
          <div class="mt-4"><?php render_pay_form('flowPayForm', 0, 0, '', '', true, true); ?></div>
        </div>
      </div>
      <?php if (ONLINE_PAY): ?>
      <div class="mt-10 border-t border-lagoon/10 pt-8">
        <p class="font-medium">Prefer to pay online?</p>
        <button type="button" id="payOnline" class="mt-3 rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">Pay online now</button>
        <p id="payOnlineMsg" class="mt-2 min-h-6 text-sm text-lagoon/70" role="status" aria-live="polite"></p>
      </div>
      <?php endif; ?>
      <button type="button" id="skipPay" class="mt-8 font-medium text-bronze underline underline-offset-4">I will pay later</button>
    </div>
    <?php endif; ?>

    <!-- <?= $doneN ?>: done -->
    <div data-panel="<?= $doneN ?>" hidden>
      <h2 id="doneTitle" class="font-display text-3xl">Request sent</h2>
      <p class="mt-3 text-lg">Your reference is <strong id="refCode" class="font-display text-bronze"></strong>.</p>
      <p id="doneText" class="mt-3 max-w-xl text-lagoon/80"></p>
      <div id="imagePreview" class="mt-8 hidden max-w-sm" aria-live="polite">
        <p class="mb-2 text-sm text-lagoon/70">Your receipt image downloads automatically. If it does not, press and hold the image to save it.</p>
        <img id="receiptPreview" alt="Your booking receipt" class="w-full rounded-xl border border-lagoon/15 shadow-sm">
      </div>
      <div class="mt-8 flex flex-wrap gap-3">
        <a id="receiptLink" href="#" class="rounded-full bg-bronze px-7 py-3 font-semibold text-white hover:bg-lagoon">Download receipt (PDF)</a>
        <button type="button" id="receiptImage" class="rounded-full border border-lagoon/25 px-7 py-3 font-semibold hover:bg-lagoon hover:text-white">Save receipt as image</button>
        <?php if ($payOn): ?><a id="payLink" href="#" class="hidden rounded-full bg-lagoon px-7 py-3 font-semibold text-white hover:bg-tide">Pay now</a><?php endif; ?>
        <a href="index.php" class="rounded-full border border-lagoon/25 px-7 py-3 font-semibold hover:bg-lagoon hover:text-white">Back to home</a>
        <?php if (messenger_url()): ?><a href="<?= e(messenger_url()) ?>" rel="noopener" class="rounded-full border border-lagoon/25 px-7 py-3 font-semibold hover:bg-lagoon hover:text-white">Message us on Messenger</a><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>window.CRYPTO = <?= json_encode(CRYPTO_NETWORKS) ?>; window.SITE = <?= json_encode(['name'=>cfg('site_name'),'address'=>array_values((array)cfg('address',[])),'phone'=>cfg('phone'),'email'=>cfg('email'),'currency'=>cfg('currency'),'check_in'=>cfg('check_in'),'check_out'=>cfg('check_out')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;</script>
<?php render_lightbox(); ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>