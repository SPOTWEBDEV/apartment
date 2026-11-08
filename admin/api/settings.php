<?php
require_once __DIR__ . '/_boot.php';
require_once __DIR__ . '/../../includes/crypto.php';
require_once __DIR__ . '/../../includes/payinfo.php';

function put(string $k, string $v): void
{
  global $pdo;
  $pdo->prepare('INSERT INTO settings(k,v) VALUES(?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v')->execute([$k, $v]);
}

switch ($action) {
  case 'get':
    respond(['ok' => true, 'values' => [
      'site_name' => cfg('site_name'),
      'tagline' => cfg('tagline'),
      'email' => cfg('email'),
      'phone' => cfg('phone'),
      'messenger' => cfg('messenger'),
      'payment_instructions' => cfg('payment_instructions', ''),
      'address' => implode("\n", cfg('address', [])),
      'check_in' => cfg('check_in'),
      'check_out' => cfg('check_out'),
      'max_nights' => cfg('max_nights', 30),
      'currency' => cfg('currency'),
      'facebook' => cfg('social.facebook'),
      'instagram' => cfg('social.instagram'),
      'payment_accounts' => payment_accounts(),
      'crypto_enabled' => (settings_all()['crypto_enabled'] ?? '0') === '1',
      'wallets' => crypto_wallets(false)
    ]]);

  case 'save':
    $s = p('section');
    if ($s === 'business') {
      if (p('site_name') === '') respond(['ok' => false, 'error' => 'Enter the site name.'], 422);
      put('site_name', clean(p('site_name'), 80));
      put('tagline', clean(p('tagline'), 140));
    } elseif ($s === 'contact') {
      if (!filter_var(p('email'), FILTER_VALIDATE_EMAIL)) respond(['ok' => false, 'error' => 'Enter a valid email address.'], 422);
      $mh = messenger_handle(p('messenger'));
      if (p('messenger') !== '' && $mh === '') respond(['ok' => false, 'error' => 'That does not look like a Facebook page name. Use the part after facebook.com/ in your page link.'], 422);
      put('email', p('email'));
      put('phone', clean(p('phone'), 40));
      put('phone_tel', preg_replace('/[^\d+]/', '', p('phone')));
      put('messenger', $mh);
      put('address', implode("\n", array_filter(array_map(fn($x) => clean($x, 120), preg_split('/\R/', p('address'))))));
    } elseif ($s === 'accounts') {
      $list = json_decode(p('accounts', '[]'), true);
      if (!is_array($list)) $list = [];
      if (count($list) > 8) respond(['ok' => false, 'error' => 'You can list up to 8 accounts.'], 422);
      $clean = [];
      foreach ($list as $i => $x) {
        $type = (string)($x['type'] ?? '');
        $label = clean((string)($x['label'] ?? ''), 60);
        $name = clean((string)($x['name'] ?? ''), 80);
        $num = clean((string)($x['number'] ?? ''), 40);
        $n = $i + 1;
        if (!isset(ACCOUNT_TYPES[$type])) respond(['ok' => false, 'error' => "Account $n: choose a type."], 422);
        if ($label === '') respond(['ok' => false, 'error' => "Account $n: enter the bank or wallet name."], 422);
        if ($name === '') respond(['ok' => false, 'error' => "Account $n: enter the account name."], 422);
        if (!preg_match('/^[A-Za-z0-9 .\-_\/]{4,40}$/', $num)) respond(['ok' => false, 'error' => "Account $n: enter the account or mobile number (4 to 40 letters, numbers, spaces or dashes)."], 422);
        $clean[] = ['type' => $type, 'label' => $label, 'name' => $name, 'number' => $num, 'note' => clean((string)($x['note'] ?? ''), 120)];
      }
      put('payment_accounts', json_encode($clean));
      put('payment_instructions', mb_substr(trim(strip_tags(p('payment_instructions'))), 0, 1500));
    } elseif ($s === 'stay') {
      $n = pint('max_nights');
      if ($n < 1 || $n > 90) respond(['ok' => false, 'error' => 'Maximum nights must be between 1 and 90.'], 422);
      put('check_in', clean(p('check_in'), 20));
      put('check_out', clean(p('check_out'), 20));
      put('max_nights', (string)$n);
      put('currency', clean(p('currency'), 3) ?: '₱');
    } elseif ($s === 'social') {
      foreach (['facebook', 'instagram'] as $k) {
        $v = clean(p($k), 300);
        if (preg_match('/^\s*(javascript|data|vbscript):/i', $v)) $v = ''; // the only thing blocked
        put($k, $v);
      }
    } elseif ($s === 'crypto') {
      $list = json_decode(p('wallets', '[]'), true);
      if (!is_array($list)) $list = [];
      if (count($list) > 10) respond(['ok' => false, 'error' => 'You can list up to 10 wallets.'], 422);
      $clean = [];
      $seen = [];
      foreach ($list as $w) {
        $coin = strtoupper((string)($w['coin'] ?? ''));
        $net = (string)($w['network'] ?? '');
        $addr = trim((string)($w['address'] ?? ''));
        if (!isset(CRYPTO_NETWORKS[$coin]) || !in_array($net, CRYPTO_NETWORKS[$coin], true)) respond(['ok' => false, 'error' => 'Choose a coin and network for every wallet.'], 422);
        if (!crypto_address_ok($net, $addr)) respond(['ok' => false, 'error' => "The $coin ($net) address does not look right. Check it and try again."], 422);
        $key = "$coin|$net|$addr";
        if (isset($seen[$key])) respond(['ok' => false, 'error' => "The $coin ($net) wallet is listed twice."], 422);
        $seen[$key] = 1;
        $clean[] = ['coin' => $coin, 'network' => $net, 'address' => $addr, 'label' => clean((string)($w['label'] ?? ''), 60)];
      }
      if (pint('enabled') && !$clean) respond(['ok' => false, 'error' => 'Add at least one wallet before switching this on.'], 422);
      put('crypto_enabled', pint('enabled') ? '1' : '0');
      put('crypto_wallets', json_encode($clean));
    } else respond(['ok' => false, 'error' => 'Unknown section.'], 400);
    respond(['ok' => true]);

  case 'password':
    $u = row('SELECT * FROM users WHERE id=?', [admin_user()['id']]);
    if (!$u || !password_verify(p('current'), $u['password_hash'])) respond(['ok' => false, 'error' => 'Your current password is incorrect.'], 422);
    if (strlen(p('new')) < 10) respond(['ok' => false, 'error' => 'Use at least 10 characters for the new password.'], 422);
    if (p('new') !== p('confirm')) respond(['ok' => false, 'error' => 'The new passwords do not match.'], 422);
    $pdo->prepare('UPDATE users SET password_hash=?, initial_pw=0 WHERE id=?')->execute([password_hash(p('new'), PASSWORD_DEFAULT), $u['id']]);
    respond(['ok' => true]);
}
respond(['ok' => false, 'error' => 'Unknown action.'], 400);
