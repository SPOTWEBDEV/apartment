<?php
require __DIR__ . '/includes/bootstrap.php';
$page_title = 'Contact | ' . cfg('site_name');
$page_desc = 'Contact ' . cfg('site_name') . ' reservations for stays, weddings, corporate events and table bookings.';
$active = 'contact';

$type = in_array($_GET['type'] ?? '', ['stay','table','wedding','corporate','other'], true) ? $_GET['type'] : 'stay';
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
    <?php if (messenger_url()): ?><div data-reveal><h2 class="font-display text-xl">Messenger</h2>
      <a href="<?= e(messenger_url()) ?>" rel="noopener" class="mt-3 inline-block rounded-full bg-lagoon px-6 py-3 font-semibold text-white hover:bg-tide">Chat on Messenger</a></div><?php endif; ?>
    <?php if (cfg('check_in') && cfg('check_out')): ?><div data-reveal><h2 class="font-display text-xl">Check-in and check-out</h2>
      <p class="mt-2 text-lagoon/80">Check-in from <?= e(cfg('check_in')) ?>. Check-out by <?= e(cfg('check_out')) ?>.</p></div><?php endif; ?>
  </div>

  <?php $form_class = 'lg:col-span-3'; include __DIR__ . '/includes/inquiry-form.php'; ?>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
