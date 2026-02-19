<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth.php';
$u = require_login();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$id = (int)($data['id'] ?? 0);
$stage = (string)($data['stage'] ?? '');

$allowed = ['new','contacted','qualified','negotiation','won','lost'];
if ($id<=0 || !in_array($stage, $allowed, true)) json_out(400, ['ok'=>false,'error'=>'Parametri non validi']);

db()->prepare("UPDATE crm_leads SET stage=? WHERE id=?")->execute([$stage,$id]);
db()->prepare("INSERT INTO crm_lead_notes (lead_id,user_id,note,kind) VALUES (?,?,?,?)")
   ->execute([$id, $u['id'], "Stage aggiornato a: $stage", 'system']);

json_out(200, ['ok'=>true]);
