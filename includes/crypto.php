<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

/** Coins and the networks each can be sent on. Add more here and the admin forms pick them up. */
const CRYPTO_NETWORKS = [
  'BTC'  => ['Bitcoin'],
  'ETH'  => ['Ethereum'],
  'USDT' => ['TRC20', 'ERC20', 'BEP20'],
  'USDC' => ['ERC20', 'Solana'],
  'SOL'  => ['Solana'],
];

/** Format check only (it cannot prove you own the wallet). */
function crypto_address_ok(string $network, string $addr): bool {
  return match ($network) {
    'Bitcoin' => (bool)preg_match('/^(bc1[a-z0-9]{25,60}|[13][a-km-zA-HJ-NP-Z1-9]{25,34})$/', $addr),
    'Ethereum', 'ERC20', 'BEP20' => (bool)preg_match('/^0x[a-fA-F0-9]{40}$/', $addr),
    'TRC20' => (bool)preg_match('/^T[1-9A-HJ-NP-Za-km-z]{33}$/', $addr),
    'Solana' => (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $addr),
    default => false,
  };
}

/** Wallets saved in Admin > Settings, or [] when crypto payments are switched off. */
function crypto_wallets(bool $onlyIfEnabled = true): array {
  $s = settings_all();
  if ($onlyIfEnabled && ($s['crypto_enabled'] ?? '0') !== '1') return [];
  $w = json_decode($s['crypto_wallets'] ?? '[]', true);
  return is_array($w) ? $w : [];
}
