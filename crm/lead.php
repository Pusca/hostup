<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
$u = require_login();
$id = (int)($_GET['id'] ?? 0);
if ($id<=0) { header('Location: '.CRM_BASE_URL.'/index.php'); exit; }
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HostUp CRM — Lead #<?= $id ?></title>
  <link rel="stylesheet" href="<?= CRM_BASE_URL ?>/assets/crm.css">
</head>
<body class="crm-bg">
  <header class="topbar">
    <div class="wrap">
      <div class="brand">
        <div class="badge"></div>
        <div class="bn">HostUp <span>CRM</span></div>
      </div>
      <div class="right">
        <a class="btn" href="<?= CRM_BASE_URL ?>/index.php">← Pipeline</a>
        <a class="btn" href="<?= CRM_BASE_URL ?>/logout.php">Esci</a>
      </div>
    </div>
  </header>

  <main class="wrap">
    <section class="leadCard" id="leadCard" data-id="<?= $id ?>">
      <div class="leadTop">
        <div>
          <h1>Lead #<?= $id ?></h1>
          <p id="sub"></p>
        </div>
        <div class="leadActions">
          <select id="stage" class="select">
            <option value="new">Nuovo</option>
            <option value="contacted">Contattato</option>
            <option value="qualified">Qualificato</option>
            <option value="negotiation">Trattativa</option>
            <option value="won">Acquisito</option>
            <option value="lost">Perso</option>
          </select>
          <button id="saveStage" class="btn-primary">Aggiorna stage</button>
        </div>
      </div>

      <div class="grid2">
        <div class="box" id="leadInfo"></div>
        <div class="box">
          <div class="boxTitle">Aggiungi nota</div>
          <select id="kind" class="select">
            <option value="note">Nota</option>
            <option value="call">Chiamata</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="email">Email</option>
          </select>
          <textarea id="note" class="textarea" placeholder="Scrivi nota..."></textarea>
          <button id="addNote" class="btn-primary">Salva nota</button>
        </div>
      </div>

      <div class="box" style="margin-top:14px;">
        <div class="boxTitle">Storico</div>
        <div id="notes"></div>
      </div>
    </section>
  </main>

  <script>const CRM_BASE_URL = "<?= CRM_BASE_URL ?>";</script>
  <script src="<?= CRM_BASE_URL ?>/assets/crm.js"></script>
</body>
</html>
