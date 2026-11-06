<?php
require __DIR__ . '/includes/bootstrap.php';
$page_title = 'Privacy policy | ' . cfg('site_name');
$page_desc = 'How ' . cfg('site_name') . ' collects, uses and protects your personal information.';
$name = cfg('site_name'); $mail = cfg('email');
$sections = [
  ['What we collect', "When you send a booking request or an inquiry we collect your name, email address, phone number, stay dates, number of guests and any notes you add. We record payments against your booking (amount, method and reference; for crypto payments the coin, network and transaction hash). We take no card payments on this website, so we never see or store card numbers. Inquiries also keep the IP address they were sent from, to help us block spam."],
  ['How we use it', "To handle your booking, confirm your stay, send your receipt, answer your questions and keep our accounting records. We email you about your own booking only. We do not sell your information."],
  ['Who sees it', "Our reservations and management staff, and the email service that delivers our messages to you. We share information with authorities only when the law requires it."],
  ['How long we keep it', "For as long as we need it to run your booking and to meet tax, accounting and legal duties. After that we delete it or remove what identifies you."],
  ['Your rights', "You can ask to see the information we hold about you, to correct it, or to have it deleted where the law allows. Write to us at the address below and we will respond. We handle personal data in line with the Data Privacy Act of 2012 (Republic Act 10173)."],
  ['Cookies and outside services', "Visitors to the website are not tracked, and we set no advertising cookies. Staff who sign in to the admin area get a session cookie that keeps them signed in. To display pages, your browser loads fonts and scripts from outside services such as Google Fonts and cdnjs, which can see your IP address when they do."],
  ['Receipt links', "Each booking has a private receipt link that is sent only to the email address on the booking. Anyone with the link can open the receipt, so please do not forward it."],
  ['Contact', "Questions about privacy: $mail."],
];
include __DIR__ . '/includes/header.php';
?>
<main>
<section class="bg-lagoon pb-14 pt-36 text-white"><div class="mx-auto max-w-3xl px-5">
  <h1 data-reveal class="font-display text-5xl">Privacy policy</h1>
  <p data-reveal class="mt-4 text-white/75">Last updated <?= date('F j, Y', filemtime(__FILE__)) ?></p>
</div></section>
<article class="mx-auto max-w-3xl space-y-10 px-5 py-16">
  <p class="text-lg text-lagoon/80"><?= e($name) ?> respects your privacy. This page explains what we collect when you use this website and why.</p>
  <?php foreach ($sections as [$h, $t]): ?>
    <section><h2 class="font-display text-2xl"><?= e($h) ?></h2><p class="mt-3 leading-relaxed text-lagoon/80"><?= e($t) ?></p></section>
  <?php endforeach; ?>
</article>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
