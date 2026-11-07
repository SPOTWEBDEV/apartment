<?php
require __DIR__ . '/includes/bookings.php';
require_once __DIR__ . '/includes/receipt.php';
require_once __DIR__ . '/includes/payinfo.php';
$b = receipt_booking((string)($_GET['ref'] ?? ''), (string)($_GET['t'] ?? ''));
$robots = 'noindex,nofollow';
if (!$b) {
  http_response_code(404);
  $page_title = 'Payment link not valid | ' . cfg('site_name');
  include __DIR__ . '/includes/header.php';
  echo '<main class="mx-auto max-w-xl px-5 pb-24 pt-44"><h1 class="font-display text-4xl">This payment link is not valid</h1><p class="mt-4 text-lagoon/80">Use the link in your booking email, or contact us with your booking reference.</p><a href="contact.php" class="mt-8 inline-block rounded-full bg-lagoon px-7 py-3 font-semibold text-white">Contact us</a></main>';
  include __DIR__ . '/includes/footer.php'; exit;
}
$page_title = 'Send your payment | ' . cfg('site_name');
$extra_scripts = ['assets/js/pay.js'];
$total = (int)$b['total']; $paid = booking_paid((int)$b['id']); $balance = max(0, $total - $paid);
$st = db()->prepare('SELECT * FROM payments WHERE booking_id=? ORDER BY id DESC'); $st->execute([$b['id']]); $pays = $st->fetchAll();
$methods = ['bank'=>'Bank transfer', 'gcash'=>'GCash', 'maya'=>'Maya', 'crypto'=>'Crypto', 'other'=>'Other', 'cash'=>'Cash', 'card'=>'Card'];
$statusUi = ['pending'=>['Under review','bg-amber-100 text-amber-800'], 'paid'=>['Approved','bg-emerald-100 text-emerald-800'], 'declined'=>['Declined','bg-red-100 text-red-800'], 'refunded'=>['Refunded','bg-slate-200 text-slate-700']];
$open = $b['status'] !== 'cancelled' && $balance > 0;
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="bg-lagoon pb-14 pt-36 text-white"><div class="mx-auto max-w-5xl px-5">
  <h1 class="font-display text-4xl sm:text-5xl">Send your payment</h1>
  <p class="mt-3 text-white/75">Booking <strong class="font-mono text-white"><?= e($b['ref']) ?></strong> · <?= e($b['room_name']) ?></p>
</div></section>

<div class="mx-auto grid max-w-5xl gap-10 px-5 py-14 lg:grid-cols-5">
  <div class="space-y-8 lg:col-span-2">
    <?php if (isset($_GET['sent'])): ?><p class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800" role="status">Thank you. We received your payment details and will review them shortly. You will get an email once they are approved.</p><?php endif; ?>
    <section class="rounded-3xl bg-foam p-6">
      <dl class="space-y-2 text-sm">
        <div class="flex justify-between"><dt class="text-lagoon/60">Stay</dt><dd class="font-medium"><?= date('j M', strtotime($b['checkin'])) ?> to <?= date('j M Y', strtotime($b['checkout'])) ?></dd></div>
        <div class="flex justify-between"><dt class="text-lagoon/60">Total</dt><dd class="font-medium"><?= peso($total) ?></dd></div>
        <div class="flex justify-between"><dt class="text-lagoon/60">Paid and approved</dt><dd class="font-medium"><?= peso($paid) ?></dd></div>
        <div class="flex justify-between border-t border-lagoon/15 pt-3 text-base"><dt class="font-semibold">Balance due</dt><dd class="font-display text-xl"><?= peso($balance) ?></dd></div>
      </dl>
      <a href="<?= e(receipt_path($b)) ?>" class="mt-4 inline-block text-sm font-semibold text-bronze underline underline-offset-4">Download receipt (PDF)</a>
    </section>
    <?php if (has_pay_info()): ?><div><?php render_payment_accounts(); ?></div>
    <?php else: ?><p class="text-sm text-lagoon/70">We will tell you how to pay when we confirm your booking. You can also <a class="font-semibold text-bronze underline" href="contact.php">contact us</a>.</p><?php endif; ?>
  </div>

  <div class="space-y-8 lg:col-span-3">
    <?php if ($open): ?>
    <section class="rounded-3xl bg-white p-6 shadow-sm sm:p-8">
      <h2 class="font-display text-2xl">I have made a payment</h2>
      <div class="mt-4"><?php render_pay_form('payForm', $balance, $balance, $b['ref'], $b['token']); ?></div>
    </section>
    <?php elseif ($b['status'] === 'cancelled'): ?>
      <p class="rounded-2xl bg-slate-100 p-5 text-lagoon/80">This booking was cancelled, so no payment is needed.</p>
    <?php else: ?>
      <p class="rounded-2xl bg-emerald-50 p-5 text-emerald-800">This booking is paid in full. Thank you!</p>
    <?php endif; ?>

    <?php if ($pays): ?>
    <section><h2 class="font-display text-2xl">Your payments</h2>
      <ul class="mt-4 divide-y divide-lagoon/10 rounded-2xl border border-lagoon/10">
        <?php foreach ($pays as $p): [$l, $cls] = $statusUi[$p['status']] ?? [$p['status'], 'bg-slate-100']; ?>
        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
          <div><p class="font-semibold"><?= peso($p['amount']) ?> <span class="font-normal text-lagoon/60">· <?= e($methods[$p['method']] ?? ucfirst($p['method'])) ?>, <?= date('j M Y', strtotime($p['paid_at'])) ?></span></p>
            <?php if ($p['status'] === 'declined' && $p['decline_reason']): ?><p class="mt-1 text-sm text-red-700">Reason: <?= e($p['decline_reason']) ?></p><?php endif; ?></div>
          <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $cls ?>"><?= $l ?></span>
        </li>
        <?php endforeach; ?>
      </ul></section>
    <?php endif; ?>
  </div>
</div>
<script>window.CRYPTO = <?= json_encode(CRYPTO_NETWORKS) ?>;</script>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
