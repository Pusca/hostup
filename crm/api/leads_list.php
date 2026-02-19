<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth.php';
require_login();

$q = trim((string)($_GET['q'] ?? ''));
$stage = trim((string)($_GET['stage'] ?? ''));

$sql = "SELECT id, created_at, name, email, phone, units, stage FROM crm_leads WHERE 1=1";
$args = [];

if ($stage !== '') { $sql .= " AND stage=?"; $args[] = $stage; }
if ($q !== '') {
  $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
  $args[] = "%$q%"; $args[] = "%$q%"; $args[] = "%$q%";
}
$sql .= " ORDER BY created_at DESC LIMIT 300";

$stmt = db()->prepare($sql);
$stmt->execute($args);
json_out(200, ['ok'=>true, 'items'=>$stmt->fetchAll()]);
