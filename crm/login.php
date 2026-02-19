<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

session_name(CRM_SESSION_NAME);
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');

  $stmt = db()->prepare("SELECT id,name,email,password_hash,role FROM crm_users WHERE email=? LIMIT 1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();

  if ($u && password_verify($pass, $u['password_hash'])) {
    $_SESSION['user'] = [
      'id' => (int)$u['id'],
      'name' => $u['name'],
      'email' => $u['email'],
      'role' => $u['role'],
    ];
    header('Location: ' . CRM_BASE_URL . '/index.php');
    exit;
  } else {
    $error = 'Credenziali non valide';
  }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HostUp CRM — Login</title>
  <link rel="stylesheet" href="<?= CRM_BASE_URL ?>/assets/crm.css">
</head>
<body class="crm-bg">
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-brand">
        <div class="badge"></div>
        <div>
          <div class="bn">HostUp <span>CRM</span></div>
          <div class="sub">Accesso dashboard</div>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" class="form">
        <label>Email</label>
        <input name="email" type="email" required placeholder="nome@email.it" />
        <label>Password</label>
        <input name="password" type="password" required placeholder="••••••••" />
        <button class="btn-primary" type="submit">Entra</button>
      </form>

      <div class="foot">© <?= date('Y') ?> HostUp</div>
    </div>
  </div>
</body>
</html>
