<?php
require_once __DIR__ . '/_boot.php';

switch ($action) {
  case 'list':
    respond(['ok'=>true, 'discounts'=>rows('SELECT * FROM discounts ORDER BY active DESC, id DESC'), 'today'=>date('Y-m-d')]);

  case 'save':
    $id = pint('id'); $code = strtoupper(preg_replace('/\s+/', '', p('code'))); $type = p('type') === 'fixed' ? 'fixed' : 'percent'; $value = pint('value');
    if (!preg_match('/^[A-Z0-9_-]{3,20}$/', $code)) respond(['ok'=>false,'error'=>'Use 3 to 20 letters, numbers, dashes or underscores for the code.'], 422);
    if ($value < 1 || ($type === 'percent' && $value > 100)) respond(['ok'=>false,'error'=>$type === 'percent' ? 'A percentage must be between 1 and 100.' : 'Enter an amount greater than zero.'], 422);
    $d = fn($k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', p($k)) ? p($k) : '';
    if ($d('starts') && $d('ends') && $d('ends') < $d('starts')) respond(['ok'=>false,'error'=>'The end date is before the start date.'], 422);
    $dup = val('SELECT id FROM discounts WHERE code=? AND id!=?', [$code, $id]);
    if ($dup) respond(['ok'=>false,'error'=>'That code already exists.'], 409);
    $f = [$code, clean(p('label'), 80), $type, $value, max(0, pint('min_nights')), $d('starts'), $d('ends'), max(0, pint('max_uses')), pint('active') ? 1 : 0];
    if ($id) $pdo->prepare('UPDATE discounts SET code=?, label=?, type=?, value=?, min_nights=?, starts=?, ends=?, max_uses=?, active=? WHERE id=?')->execute([...$f, $id]);
    else $pdo->prepare('INSERT INTO discounts(code,label,type,value,min_nights,starts,ends,max_uses,active,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([...$f, date('Y-m-d H:i:s')]);
    respond(['ok'=>true]);

  case 'toggle':
    $pdo->prepare('UPDATE discounts SET active=? WHERE id=?')->execute([pint('active') ? 1 : 0, pint('id')]);
    respond(['ok'=>true]);

  case 'delete':
    $pdo->prepare('DELETE FROM discounts WHERE id=?')->execute([pint('id')]);
    respond(['ok'=>true]);
}
respond(['ok'=>false,'error'=>'Unknown action.'], 400);
