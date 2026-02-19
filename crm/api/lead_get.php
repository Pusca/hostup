<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth.php';
$u = require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json_out(400, ['ok'=>false,'error'=>'ID non valido']);

$stmt = db()->prepare("SELECT * FROM crm_leads WHERE id=?");
$stmt->execute([$id]);
$lead = $stmt->fetch();
if (!$lead) json_out(404, ['ok'=>false,'error'=>'Lead non trovato']);

$notes = db()->prepare("
  SELECT n.id,n.created_at,n.note,n.kind,u.name AS user_name
  FROM crm_lead_notes n
  LEFT JOIN crm_users u ON u.id=n.user_id
  WHERE n.lead_id=?
  ORDER BY n.created_at DESC
");
$notes->execute([$id]);

json_out(200, ['ok'=>true, 'lead'=>$lead, 'notes'=>$notes->fetchAll()]);
