<?php
require_once __DIR__ . '/_boot.php';

switch ($action) {
  case 'list':
    $status = p('status', 'all'); $type = p('type'); $q = p('q'); $page = max(1, pint('page', 1)); $per = 15;
    $w = []; $a = [];
    if (in_array($status, ['new', 'handled'], true)) { $w[] = 'status=?'; $a[] = $status; }
    if (in_array($type, ['stay', 'table', 'wedding', 'corporate', 'other'], true)) { $w[] = 'type=?'; $a[] = $type; }
    if ($q !== '') { $w[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)'; $l = "%$q%"; array_push($a, $l, $l, $l, $l); }
    $where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
    $total = (int)val("SELECT COUNT(*) FROM inquiries $where", $a);
    respond(['ok'=>true, 'inquiries'=>rows("SELECT * FROM inquiries $where ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $a),
             'total'=>$total, 'page'=>$page, 'per'=>$per, 'pages'=>max(1, (int)ceil($total / $per)),
             'counts'=>array_column(rows('SELECT status, COUNT(*) c FROM inquiries GROUP BY status'), 'c', 'status')]);

  case 'status':
    $to = p('status') === 'handled' ? 'handled' : 'new';
    $pdo->prepare('UPDATE inquiries SET status=? WHERE id=?')->execute([$to, pint('id')]);
    respond(['ok'=>true]);

  case 'notes':
    $pdo->prepare('UPDATE inquiries SET admin_notes=? WHERE id=?')->execute([clean(p('admin_notes'), 2000), pint('id')]);
    respond(['ok'=>true]);

  case 'delete':
    $pdo->prepare('DELETE FROM inquiries WHERE id=?')->execute([pint('id')]);
    respond(['ok'=>true]);
}
respond(['ok'=>false,'error'=>'Unknown action.'], 400);
