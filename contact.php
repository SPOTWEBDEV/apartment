<?php
require __DIR__ . '/includes/bootstrap.php';
$page_title = 'Contact | ' . cfg('site_name');
$page_desc = 'Contact ' . cfg('site_name') . ' reservations for stays, weddings, corporate events and table bookings.';
$active = 'contact';

$type = in_array($_GET['type'] ?? '', ['stay','wedding','corporate','other'], true) ? $_GET['type'] : 'stay';
$msg = '';
if (!empty($_GET['room'])) {
  $date = fn($k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET[$k] ?? '') ? $_GET[$k] : '';
  $msg = 'I would like to book the ' . mb_substr(strip_tags($_GET['room']), 0, 80);
  if ($date('checkin') && $date('checkout')) $msg .= ' from ' . $date('checkin') . ' to ' . $date('checkout');
  if (!empty($_GET['guests'])) $msg .= ' for ' . (int)$_GET['guests'] . ' guests';
  $msg .= '.';
}
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="bg-lagoon pb-16 pt-36 text-white">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <h1 data-reveal class="font-display text-5xl sm:text-6xl">Plan your visit</h1>
    <p data-reveal class="mt-5 max-w-xl text-lg text-white/80">Tell us what you have in mind. The reservations team replies by email, usually within a day.</p>
  </div>
</section>

<section class="mx-auto grid max-w-7xl gap-12 px-5 py-20 lg:grid-cols-5 lg:px-8">
  <div class="space-y-8 lg:col-span-2">
    <div data-reveal><h2 class="font-display text-xl">Address</h2>
      <address class="mt-2 not-italic text-lagoon/80"><?= implode('<br>', array_map('e', cfg('address'))) ?></address>
      <a href="<?= e(maps_url()) ?>" rel="noopener" class="mt-2 inline-block font-medium text-bronze underline underline-offset-4">Open in Google Maps</a></div>
    <div data-reveal><h2 class="font-display text-xl">Phone and email</h2>
      <p class="mt-2 text-lagoon/80"><a class="underline" href="tel:<?= e(cfg('phone_tel')) ?>"><?= e(cfg('phone')) ?></a><br>
      <a class="underline" href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a></p></div>
    <div data-reveal><h2 class="font-display text-xl">WhatsApp</h2>
      <a href="<?= e(whatsapp_url()) ?>" rel="noopener" class="mt-3 inline-block rounded-full bg-lagoon px-6 py-3 font-semibold text-white hover:bg-tide">Chat on WhatsApp</a></div>
    <div data-reveal><h2 class="font-display text-xl">Check-in and check-out</h2>
      <p class="mt-2 text-lagoon/80">Check-in from <?= e(cfg('check_in')) ?>. Check-out by <?= e(cfg('check_out')) ?>.</p></div>
  </div>

  <form id="inquiryForm" data-reveal class="space-y-4 rounded-3xl bg-white p-6 shadow-sm sm:p-8 lg:col-span-3" novalidate>
    <div class="grid gap-4 sm:grid-cols-2">
      <div><label class="mb-1 block text-sm font-medium" for="name">Name</label><input class="field" id="name" name="name" autocomplete="name"><p class="err mt-1 text-sm text-red-700" data-for="name"></p></div>
      <div><label class="mb-1 block text-sm font-medium" for="email">Email</label><input class="field" id="email" name="email" type="email" autocomplete="email"><p class="err mt-1 text-sm text-red-700" data-for="email"></p></div>
    </div>
    <div><label class="mb-1 block text-sm font-medium" for="type">I am asking about</label>
      <select class="field" id="type" name="type">
        <?php foreach (['stay'=>'A stay','wedding'=>'A wedding','corporate'=>'A corporate event','other'=>'Something else'] as $v=>$l): ?>
          <option value="<?= $v ?>"<?= $v === $type ? ' selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select></div>
    <div><label class="mb-1 block text-sm font-medium" for="message">Message</label><textarea class="field" id="message" name="message" rows="5"><?= e($msg) ?></textarea><p class="err mt-1 text-sm text-red-700" data-for="message"></p></div>
    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
    <button class="w-full rounded-full bg-bronze px-7 py-3.5 font-semibold text-white hover:bg-lagoon disabled:opacity-60">Send inquiry</button>
    <p id="formMsg" class="min-h-6 text-sm" role="status" aria-live="polite"></p>
  </form>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
