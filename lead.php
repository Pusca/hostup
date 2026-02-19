<?php
declare(strict_types=1);

require_once __DIR__ . '/crm/config.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  header('Access-Control-Allow-Methods: POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  header('Access-Control-Allow-Origin: *');
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  json_out(405, ['ok' => false, 'error' => 'Metodo non consentito']);
}

function clean(string $v): string {
  $v = trim($v);
  return str_replace(["\r", "\n"], ' ', $v);
}

function request_data(): array {
  $contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
  $raw = file_get_contents('php://input');
  if (stripos($contentType, 'application/json') !== false) {
    $json = json_decode((string)$raw, true);
    return is_array($json) ? $json : [];
  }
  return $_POST ?: [];
}

function try_insert_lead(PDO $pdo, array $data): int {
  $attempts = [
    [
      "INSERT INTO crm_leads (source,name,email,phone,units,msg,ip,user_agent,stage) VALUES (?,?,?,?,?,?,?,?,?)",
      [$data['source'], $data['name'], $data['email'], $data['phone'], $data['units'], $data['msg'], $data['ip'], $data['user_agent'], 'new'],
    ],
    [
      "INSERT INTO crm_leads (name,email,phone,units,msg,stage) VALUES (?,?,?,?,?,?)",
      [$data['name'], $data['email'], $data['phone'], $data['units'], $data['msg'], 'new'],
    ],
    [
      "INSERT INTO crm_leads (name,email,phone,unit,msg,stage) VALUES (?,?,?,?,?,?)",
      [$data['name'], $data['email'], $data['phone'], $data['units'], $data['msg'], 'new'],
    ],
    [
      "INSERT INTO crm_leads (name,email,phone,units,message,stage) VALUES (?,?,?,?,?,?)",
      [$data['name'], $data['email'], $data['phone'], $data['units'], $data['msg'], 'new'],
    ],
    [
      "INSERT INTO crm_leads (name,email,phone,stage) VALUES (?,?,?,?)",
      [$data['name'], $data['email'], $data['phone'], 'new'],
    ],
  ];

  $lastError = 'Inserimento non riuscito';
  foreach ($attempts as [$sql, $params]) {
    try {
      $st = $pdo->prepare($sql);
      $st->execute($params);
      return (int)$pdo->lastInsertId();
    } catch (Throwable $e) {
      $lastError = $e->getMessage();
    }
  }

  throw new RuntimeException($lastError);
}

$input = request_data();
$name = clean((string)($input['name'] ?? ''));
$email = clean((string)($input['email'] ?? ''));
$phone = clean((string)($input['phone'] ?? ''));
$units = clean((string)($input['units'] ?? ''));
$msg = trim((string)($input['msg'] ?? ''));

if ($name === '' || mb_strlen($name) < 2) {
  json_out(422, ['ok' => false, 'error' => 'Nome non valido']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_out(422, ['ok' => false, 'error' => 'Email non valida']);
}
if ($units === '') {
  json_out(422, ['ok' => false, 'error' => 'Seleziona le unita']);
}

$leadData = [
  'source' => 'hostup.it',
  'name' => $name,
  'email' => $email,
  'phone' => ($phone !== '' ? $phone : null),
  'units' => $units,
  'msg' => ($msg !== '' ? $msg : null),
  'ip' => ($_SERVER['REMOTE_ADDR'] ?? null),
  'user_agent' => ($_SERVER['HTTP_USER_AGENT'] ?? null),
];

try {
  $pdo = db();
  $pdo->beginTransaction();

  $leadId = try_insert_lead($pdo, $leadData);

  try {
    $pdo->prepare("INSERT INTO crm_lead_notes (lead_id, user_id, note, kind) VALUES (?,?,?,?)")
      ->execute([$leadId, null, 'Lead acquisito dalla landing HostUp', 'system']);
  } catch (Throwable $e) {
    // Note non bloccanti: il lead e' gia' salvato.
  }

  $pdo->commit();

  $to = 'info@smartera.it';
  $fromEmail = 'info@smartera.it';
  $subject = 'Nuovo lead HostUp (CRM) #' . $leadId;
  $body  = "Nuovo lead salvato nel CRM.\n\n";
  $body .= "ID: $leadId\nNome: $name\nEmail: $email\nTelefono: " . ($phone ?: 'n/d') . "\nUnita: $units\n";
  $body .= "Note: " . ($msg ?: 'n/d') . "\nIP: " . (string)($leadData['ip'] ?: 'n/d') . "\n";
  $headers = "From: HostUp <{$fromEmail}>\r\n";
  $headers .= "Reply-To: {$name} <{$email}>\r\n";
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
  @mail($to, $subject, $body, $headers, "-f{$fromEmail}");

  json_out(200, ['ok' => true, 'lead_id' => $leadId]);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  json_out(500, ['ok' => false, 'error' => 'Errore salvataggio lead: ' . $e->getMessage()]);
}
