<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth.php';
$u = require_login();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$id = (int)($data['id'] ?? 0);
$note = trim((string)($data['note'] ?? ''));
$kind = (string)($data['kind'] ?? 'note');
$allowedKinds = ['note','call','email','whatsapp','system'];

if ($id<=0 || $note==='' || !in_array($kind,$allowedKinds,true)) {
  json_out(400, ['ok'=>false,'error'=>'Parametri non validi']);
}

db()->prepare("INSERT INTO crm_lead_notes (lead_id,user_id,note,kind) VALUES (?,?,?,?)")
  ->execute([$id,$u['id'],$note,$kind]);

db()->prepare("UPDATE crm_leads SET last_contact_at=NOW() WHERE id=?")->execute([$id]);

json_out(200, ['ok'=>true]);
