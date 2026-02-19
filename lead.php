<?php
declare(strict_types=1);

require_once __DIR__ . '/crm/config.php'; // <-- path: se lead.php è in root, va bene così

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  header('Access-Control-Allow-Methods: POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Metodo non consentito']);
  exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$raw = file_get_contents('php://input');
$data = [];

if (stripos($contentType, 'application/json') !== false) {
  $data = json_decode($raw, true) ?: [];
} else {
  $data = $_POST ?: [];
}

// Honeypot: se ancora lo vuoi, ma ora ti consiglio di DISABILITARLO o rinominarlo
$hp = trim((string)($data['_hp'] ?? ''));
if ($hp !== '') {
  echo json_encode(['ok'=>true]);
  exit;
}

function clean(string $v): string {
  $v = trim($v);
  $v = str_replace(["\r","\n"], ' ', $v);
  return $v;
}

$name  = clean((string)($data['name'] ?? ''));
$email = clean((string)($data['email'] ?? ''));
$phone = clean((string)($data['phone'] ?? ''));
$units = clean((string)($data['units'] ?? ''));
$msg   = trim((string)($data['msg'] ?? ''));

if ($name === '' || mb_strlen($name) < 2) json_out(422, ['ok'=>false,'error'=>'Nome non valido']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(422, ['ok'=>false,'error'=>'Email non valida']);
if ($units === '') json_out(422, ['ok'=>false,'error'=>'Seleziona le unità']);

$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

// Salvataggio DB
$pdo = db();
$stmt = $pdo->prepare("
  INSERT INTO crm_leads (source,name,email,phone,units,msg,ip,user_agent,stage)
  VALUES (?,?,?,?,?,?,?,?, 'new')
");
$stmt->execute([
  'hostup.it', $name, $email, ($phone!==''?$phone:null), $units, ($msg!==''?$msg:null), $ip, $ua
]);

$leadId = (int)$pdo->lastInsertId();

// Nota di sistema
$stmt2 = $pdo->prepare("INSERT INTO crm_lead_notes (lead_id, user_id, note, kind) VALUES (?,?,?,?)");
$stmt2->execute([$leadId, null, 'Lead acquisito dalla landing HostUp', 'system']);

// Email opzionale (se vuoi mantenere la notifica)
$to = 'info@smartera.it';
$fromEmail = 'info@smartera.it';
$subject = 'Nuovo lead HostUp (CRM) #' . $leadId;

$body  = "Nuovo lead salvato nel CRM.\n\n";
$body .= "ID: $leadId\nNome: $name\nEmail: $email\nTelefono: ".($phone?:'n/d')."\nUnità: $units\n";
$body .= "Note: ".($msg?:'n/d')."\nIP: ".($ip?:'n/d')."\n";

$headers = "From: HostUp <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

@mail($to, $subject, $body, $headers, "-f{$fromEmail}");

echo json_encode(['ok'=>true, 'lead_id'=>$leadId]);
