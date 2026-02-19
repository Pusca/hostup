<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

session_name(CRM_SESSION_NAME);
session_start();

$error = '';
$ok = '';

function clean(string $v): string {
  return trim($v);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name  = clean((string)($_POST['name'] ?? ''));
  $email = clean((string)($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');
  $code  = clean((string)($_POST['code'] ?? ''));

  if ($code !== CRM_INVITE_CODE) {
    $error = 'Chiave segreta errata.';
  } elseif ($name === '' || mb_strlen($name) < 2) {
    $error = 'Nome non valido.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Email non valida.';
  } elseif (mb_strlen($pass) < 8) {
    $error = 'Password troppo corta (min 8 caratteri).';
  } else {
    // controlla se email esiste
    $stmt = db()->prepare("SELECT id FROM crm_users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $error = 'Email già registrata.';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);

      // primo utente admin? (opzionale: qui lasciamo "user" sempre)
      $role = 'user';

      $ins = db()->prepare("INSERT INTO crm_users (name,email,password_hash,role) VALUES (?,?,?,?)");
      $ins->execute([$name,$email,$hash,$role]);

      $ok = 'Registrazione completata. Ora puoi fare login.';
    }
  }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HostUp CRM — Registrazione</title>
  <link rel="stylesheet" href="<?= CRM_BASE_URL ?>/assets/crm.css">
</head>
<body class="crm-bg">
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-brand">
        <div class="badge"></div>
        <div>
          <div class="bn">HostUp <span>CRM</span></div>
          <div class="sub">Registrazione (protetta)</div>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($ok): ?>
        <div class="alert" style="background:rgba(17,211,197,.10); border-color: rgba(17,211,197,.35);">
          <?= htmlspecialchars($ok) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="form" autocomplete="off">
        <label>Nome e cognome</label>
        <input name="name" required placeholder="Es. Luca Rizzo" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />

        <label>Email</label>
        <input name="email" type="email" required placeholder="nome@email.it" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />

        <label>Password</label>
        <input name="password" type="password" required placeholder="Min 8 caratteri" />

        <label>Chiave segreta</label>
        <input name="code" required placeholder="" />

        <button class="btn-primary" type="submit">Crea account</button>
      </form>

      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="<?= CRM_BASE_URL ?>/login.php">← Torna al login</a>
      </div>

      <div class="foot">© <?= date('Y') ?> HostUp</div>
    </div>
  </div>
</body>
</html>
