<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/crypto.php';
respond(['ok'=>true, 'wallets'=>array_map(fn($w) => ['coin'=>$w['coin'],'network'=>$w['network'],'address'=>$w['address'],'label'=>$w['label'] ?? ''], crypto_wallets())]);
