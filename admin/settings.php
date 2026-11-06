<?php
require_once __DIR__ . '/_layout.php';
admin_open('Settings', 'settings', 'These override the defaults in includes/config.php across the whole website.');
$sec = fn($id, $title, $desc, $body, $btn = 'Save changes') => print('<section id="' . $id . '" class="card mb-6 grid gap-6 p-6 lg:grid-cols-3 lg:p-8"><div><h2 class="font-semibold">' . $title . '</h2><p class="mt-1 text-sm text-slate-500">' . $desc . '</p></div>'
  . '<form data-section="' . $id . '" class="space-y-4 lg:col-span-2" novalidate>' . $body . '<div class="flex items-center gap-3"><button class="btn-primary">' . $btn . '</button><p class="text-sm" data-msg></p></div></form></section>');
$f = fn($n, $l, $t = 'text', $x = '') => '<div><label class="lbl" for="s_' . $n . '">' . $l . '</label><input class="input" id="s_' . $n . '" name="' . $n . '" type="' . $t . '" ' . $x . '></div>';
?>
<div class="max-w-5xl">
<?php
$sec('business', 'Business', 'Shown in the header, footer, page titles and emails.', $f('site_name', 'Site name') . $f('tagline', 'Tagline'));
$sec('contact', 'Contact', 'Used on the contact page, footer, Messenger chat button and as the inbox for inquiries and booking requests.',
  $f('email', 'Reservations email', 'email') . '<div class="grid gap-4 sm:grid-cols-2">' . $f('phone', 'Phone') . $f('messenger', 'Facebook page for Messenger', 'text', 'placeholder="yourpagename"') . '</div>'
  . '<p class="-mt-2 text-xs text-slate-500">Type the page name from your page link (facebook.com/<strong>yourpagename</strong>). Guests who tap Chat with us open a Messenger conversation with that page. Leave empty to hide the button.</p>'
  . '<div><label class="lbl" for="s_address">Address (one line per row)</label><textarea class="input" id="s_address" name="address" rows="3"></textarea></div>');
$sec('payments', 'Payment instructions', 'Shown to guests on their payment page, where they send you their payment details for you to approve.',
  '<div><label class="lbl" for="s_payment_instructions">How guests should pay</label><textarea class="input" id="s_payment_instructions" name="payment_instructions" rows="6" placeholder="Bank name, account name and number, GCash or Maya number, and anything else guests need"></textarea></div>');
$sec('stay', 'Stay rules', 'Applied to the booking flow and shown on the FAQ and contact pages.',
  '<div class="grid gap-4 sm:grid-cols-2">' . $f('check_in', 'Check-in time') . $f('check_out', 'Check-out time') . $f('max_nights', 'Maximum nights per request', 'number', 'min="1" max="90"') . $f('currency', 'Currency symbol', 'text', 'maxlength="3"') . '</div>');
$sec('social', 'Social links', 'Leave empty to keep the placeholder link.', $f('facebook', 'Facebook page', 'url', 'placeholder="https://facebook.com/yourpage"') . $f('instagram', 'Instagram profile', 'url', 'placeholder="https://instagram.com/yourhandle"'));
?>
  <section id="crypto" class="card mb-6 grid gap-6 p-6 lg:grid-cols-3 lg:p-8">
    <div><h2 class="font-semibold">Crypto payments</h2><p class="mt-1 text-sm text-slate-500">Wallets guests can pay to after they send a booking request. You record each payment yourself on the Payments page once the transaction arrives.</p></div>
    <form id="cryptoForm" class="space-y-4 lg:col-span-2" novalidate>
      <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="enabled" value="1" class="h-4 w-4 accent-[#A8743B]"> Show crypto payment details to guests after they book</label>
      <div id="wallets" class="space-y-3"></div>
      <button type="button" id="addWallet" class="btn-ghost btn-sm">Add wallet</button>
      <p class="rounded-lg bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">Guests will send real money to these addresses and a wrong address cannot be recovered. Check every character, use wallets you control, and avoid exchange deposit addresses that rotate.</p>
      <div class="flex items-center gap-3"><button class="btn-primary">Save wallets</button><p class="text-sm" data-msg></p></div>
    </form>
  </section>
  <section id="account" class="card grid gap-6 p-6 lg:grid-cols-3 lg:p-8">
    <div><h2 class="font-semibold">Password</h2><p class="mt-1 text-sm text-slate-500">Use at least 10 characters.</p></div>
    <form id="pwForm" class="space-y-4 lg:col-span-2" novalidate>
      <?= $f('current', 'Current password', 'password', 'autocomplete="current-password"') . $f('new', 'New password', 'password', 'autocomplete="new-password"') . $f('confirm', 'Confirm new password', 'password', 'autocomplete="new-password"') ?>
      <div class="flex items-center gap-3"><button class="btn-primary">Change password</button><p class="text-sm" data-msg></p></div>
    </form>
  </section>
</div>
<?php admin_close(['assets/settings.js']);
