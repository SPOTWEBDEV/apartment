<?php
require __DIR__ . '/includes/bootstrap.php';
$page_title = 'Terms and conditions | ' . cfg('site_name');
$page_desc = 'Booking, payment and stay terms for ' . cfg('site_name') . '.';
$name = cfg('site_name'); $mail = cfg('email');
$sections = [
  ['Booking requests', "Sending a booking request on this website does not confirm your stay. A booking is confirmed only when our reservations team tells you so by email or phone. Until then we may offer other dates or rooms if the room is no longer available."],
  ['Rates and totals', "Prices are shown in " . cfg('currency') . " (Philippine pesos) per room per night. The total on the booking page is an estimate. The reservations team confirms the final rate, including any discount code you entered."],
  ['Payments', "We take no online card payments through this website. We tell you how to pay when we confirm your booking, for example by bank transfer, e-wallet, cash on arrival or crypto. We record each payment against your booking reference and your receipt shows what has been paid and the balance due."],
  ['Crypto payments', "If we offer crypto, send only to the wallet addresses shown on our booking page or sent to you by our team, on the network stated. Payments sent to any other address, or on the wrong network, cannot be recovered by us. Network fees are paid by the sender. We credit your booking in Philippine pesos at the value we confirm when the payment arrives."],
  ['Cancellations and refunds', "Cancellation and refund terms for your stay are confirmed by our reservations team when your booking is confirmed. Please read them in your confirmation before paying."],
  ['During your stay', "Please look after the property and follow the staff instructions. Guests are responsible for damage they cause. Pets are welcome on the grounds. Tell us when you book. Water and outdoor activities carry some risk, so keep children supervised and follow the safety advice of our staff."],
  ['Changes to these terms', "We may update these terms from time to time. The terms that apply to you are those shown on this page when you made your booking request."],
  ['Governing law and contact', "These terms are governed by the laws of the Republic of the Philippines. Questions: $mail."],
];
if (cfg('check_in') && cfg('check_out')) array_splice($sections, 5, 0, [['Check-in and check-out', "Check-in is from " . cfg('check_in') . " and check-out is by " . cfg('check_out') . ". Please tell us if you will arrive late."]]);
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="bg-lagoon pb-14 pt-36 text-white"><div class="mx-auto max-w-3xl px-5">
  <h1 data-reveal class="font-display text-5xl">Terms and conditions</h1>
  <p data-reveal class="mt-4 text-white/75">Last updated <?= date('F j, Y', filemtime(__FILE__)) ?></p>
</div></section>
<article class="mx-auto max-w-3xl space-y-10 px-5 py-16">
  <p class="text-lg text-lagoon/80">These terms cover bookings and stays with <?= e($name) ?>. By sending a booking request you agree to them.</p>
  <?php foreach ($sections as [$h, $t]): ?>
    <section><h2 class="font-display text-2xl"><?= e($h) ?></h2><p class="mt-3 leading-relaxed text-lagoon/80"><?= e($t) ?></p></section>
  <?php endforeach; ?>
</article>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
