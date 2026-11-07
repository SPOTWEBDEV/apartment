<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/crypto.php';

const ACCOUNT_TYPES = ['bank' => 'Bank transfer', 'gcash' => 'GCash', 'maya' => 'Maya', 'other' => 'Other'];
const ONLINE_PAY = false; // set to true once api/gateway-init.php exists

/** Bank and e-wallet accounts the admin added in Settings. */
function payment_accounts(): array {
  $a = json_decode(settings_all()['payment_accounts'] ?? '[]', true);
  return is_array($a) ? $a : [];
}
/** True when there is anything to show guests on a payment step. */
function has_pay_info(): bool {
  return payment_accounts() || crypto_wallets() || trim((string)cfg('payment_instructions', '')) !== '';
}
/** Payment methods a guest can pick: the account types the admin added, plus crypto when wallets exist. */
function pay_method_options(): array {
  $present = array_unique(array_column(payment_accounts(), 'type'));
  $opts = [];
  foreach (ACCOUNT_TYPES as $k => $l) if (in_array($k, $present, true)) $opts[$k] = $l;
  if (crypto_wallets()) $opts['crypto'] = 'Crypto';
  return $opts ?: ACCOUNT_TYPES;
}

/**
 * Amount choices for the "I have paid" form: [amount => label].
 * Half is only offered when nothing has been paid yet ($due is the whole total, or $total is not given).
 */
function pay_amount_options(int $due, int $total = 0): array {
  $opts = []; $half = (int)ceil($due / 2);
  if ($due > 1 && $half < $due && ($total === 0 || $due >= $total)) $opts[$half] = 'Half payment';
  if ($due > 0) $opts[$due] = ($total > 0 && $due < $total) ? 'Remaining balance' : 'Full payment';
  return $opts;
}

/** Method picker: one button per account type the admin added, plus Crypto. */
function render_pay_methods(): void {
  $opts = pay_method_options(); $first = array_key_first($opts); ?>
  <div class="flex flex-wrap gap-2" role="group" aria-label="Payment method" data-pay-methods>
    <?php foreach ($opts as $k => $l): ?>
      <button type="button" data-method="<?= e($k) ?>" aria-pressed="<?= $k === $first ? 'true' : 'false' ?>" class="pay-method rounded-full border border-lagoon/25 px-5 py-2.5 text-sm font-semibold"><?= e($l) ?></button>
    <?php endforeach; ?>
  </div>
<?php }

/** Where to send money: every bank, e-wallet and crypto account the admin added, with Copy buttons. */
function render_payment_accounts(): void {
  $accounts = payment_accounts(); $wallets = crypto_wallets(); $note = trim((string)cfg('payment_instructions', ''));
  $copy = fn(string $v) => '<button type="button" data-copy="' . e($v) . '" class="shrink-0 rounded-lg border border-lagoon/20 px-3 py-1 text-xs font-semibold hover:bg-lagoon hover:text-white">Copy</button>';
  if ($accounts): ?>
  <section data-acct-group="fiat" aria-labelledby="acctH"><h3 id="acctH" class="font-display text-2xl">Bank and e-wallet</h3>
    <ul class="mt-4 space-y-3">
      <?php foreach ($accounts as $a): ?>
      <li data-acct-type="<?= e($a['type']) ?>" class="rounded-2xl border border-lagoon/15 bg-white p-4">
        <p class="flex items-center justify-between gap-3 text-sm font-semibold"><span><?= e($a['label']) ?></span><span class="rounded-full bg-foam px-2.5 py-0.5 text-xs font-medium text-lagoon/70"><?= e(ACCOUNT_TYPES[$a['type']] ?? 'Other') ?></span></p>
        <p class="mt-1 text-sm text-lagoon/70">Account name: <strong class="text-lagoon"><?= e($a['name']) ?></strong></p>
        <div class="mt-2 flex items-center justify-between gap-3"><code class="text-base font-semibold tracking-wide"><?= e($a['number']) ?></code><?= $copy($a['number']) ?></div>
        <?php if (!empty($a['note'])): ?><p class="mt-2 text-xs text-lagoon/60"><?= e($a['note']) ?></p><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul></section>
  <?php endif;
  if ($wallets): ?>
  <section data-acct-group="crypto" class="<?= $accounts ? 'mt-8' : '' ?>" aria-labelledby="cryptoH"><h3 id="cryptoH" class="font-display text-2xl">Crypto</h3>
    <p class="mt-1 text-sm text-lagoon/70">Send only to these addresses, on the network shown.</p>
    <ul class="mt-4 space-y-3">
      <?php foreach ($wallets as $w): ?>
      <li class="rounded-2xl border border-lagoon/15 bg-white p-4"><p class="text-sm font-semibold"><?= e($w['coin']) ?> on <?= e($w['network']) ?><?= !empty($w['label']) ? ' <span class="font-normal text-lagoon/60">· ' . e($w['label']) . '</span>' : '' ?></p>
        <div class="mt-2 flex items-start justify-between gap-3"><code class="break-all text-xs"><?= e($w['address']) ?></code><?= $copy($w['address']) ?></div></li>
      <?php endforeach; ?>
    </ul></section>
  <?php endif;
  if ($note !== ''): ?><p class="<?= $accounts || $wallets ? 'mt-6' : '' ?> whitespace-pre-line text-sm leading-relaxed text-lagoon/80"><?= e($note) ?></p><?php endif;
}

/**
 * "I have paid" form. Works on the booking page (ref/token and amounts filled by script) and on the guest's private payment page.
 * $max = balance still due (0 = the script fills the amount choices). $total = booking total, so "half" is
 * only offered when nothing has been paid yet; leave it 0 if unknown. $amount is kept for older callers.
 */
function render_pay_form(string $id, int $amount, int $max, string $ref = '', string $token = '', bool $manual = false, bool $proofRequired = false, int $total = 0): void {
  $f = fn(string $n) => $id . '_' . $n;
  ?>
  <form id="<?= e($id) ?>" data-pay-form<?= $manual ? ' data-manual' : '' ?> class="space-y-4" novalidate>
    <input type="hidden" name="ref" value="<?= e($ref) ?>"><input type="hidden" name="t" value="<?= e($token) ?>">
    <div class="grid gap-4 sm:grid-cols-2">
      <div><label class="mb-1 block text-sm font-medium" for="<?= $f('amount') ?>">How much are you paying?</label><select class="field" id="<?= $f('amount') ?>" name="amount" data-amount><?php foreach (pay_amount_options($max, $total) as $v => $l): ?><option value="<?= $v ?>"<?= $v === $max ? ' selected' : '' ?>><?= e($l) ?> · <?= peso($v) ?></option><?php endforeach; ?></select></div>
      <div><label class="mb-1 block text-sm font-medium" for="<?= $f('method') ?>">Paid by</label><select class="field" id="<?= $f('method') ?>" name="method"><?php foreach (pay_method_options() as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div><label class="mb-1 block text-sm font-medium" for="<?= $f('date') ?>">Date paid</label><input class="field" id="<?= $f('date') ?>" name="paid_at" type="date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div>
      <div><label class="mb-1 block text-sm font-medium" for="<?= $f('ref') ?>">Reference number</label><input class="field" id="<?= $f('ref') ?>" name="reference" placeholder="From your receipt or app"></div>
    </div>
    <div data-crypto class="hidden space-y-4 rounded-2xl bg-white/70 p-4 ring-1 ring-lagoon/10">
      <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-sm font-medium" for="<?= $f('coin') ?>">Coin</label><select class="field" id="<?= $f('coin') ?>" name="coin"><?php foreach (array_keys(CRYPTO_NETWORKS) as $c): ?><option><?= $c ?></option><?php endforeach; ?></select></div>
        <div><label class="mb-1 block text-sm font-medium" for="<?= $f('net') ?>">Network</label><select class="field" id="<?= $f('net') ?>" name="network"></select></div>
        <div><label class="mb-1 block text-sm font-medium" for="<?= $f('camt') ?>">Amount of crypto sent</label><input class="field" id="<?= $f('camt') ?>" name="crypto_amount" type="number" step="any" min="0"></div>
      </div>
      <div><label class="mb-1 block text-sm font-medium" for="<?= $f('hash') ?>">Transaction hash</label><input class="field font-mono text-sm" id="<?= $f('hash') ?>" name="tx_hash" autocomplete="off" placeholder="Paste the transaction ID"></div>
    </div>
    <div><label class="mb-1 block text-sm font-medium" for="<?= $f('proof') ?>">Screenshot or receipt<?= $proofRequired ? '' : ' (optional)' ?></label><input class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-lagoon file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" id="<?= $f('proof') ?>" name="proof" type="file"<?= $proofRequired ? ' data-required' : '' ?> accept="image/jpeg,image/png,image/webp,application/pdf"><p class="mt-1 text-xs text-lagoon/60">JPG, PNG, WebP or PDF, up to 5 MB.</p></div>
    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
    <button class="w-full rounded-full bg-bronze px-7 py-3.5 font-semibold text-white hover:bg-lagoon disabled:opacity-60">Send payment details</button>
    <p data-pay-msg class="min-h-6 text-sm text-red-700" role="status" aria-live="polite"></p>
  </form>
<?php }