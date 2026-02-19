<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HostUp | Gestione Affitti Brevi 360°</title>
  <meta name="description" content="HostUp gestisce il tuo immobile in affitto breve a 360°: burocrazia, immagine online, automazione, ospiti e controllo via app." />
  <style>
    :root{
      --bg:#0B1220;
      --panel:rgba(255,255,255,.06);
      --panel-2:rgba(255,255,255,.04);
      --text:rgba(255,255,255,.92);
      --muted:rgba(255,255,255,.72);
      --stroke:rgba(255,255,255,.14);
      --blue:#1F6BFF;
      --cyan:#11D3C5;
      --grad:linear-gradient(135deg,var(--cyan) 0%,var(--blue) 55%,#4A8BFF 100%);
      --shadow:0 16px 50px rgba(0,0,0,.35);
      --radius:18px;
      --container:1120px;
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
      background:
        radial-gradient(900px 360px at 10% 5%, rgba(17,211,197,.15), transparent 60%),
        radial-gradient(900px 360px at 90% 15%, rgba(31,107,255,.17), transparent 60%),
        var(--bg);
      color:var(--text);
      line-height:1.45;
    }

    a{color:inherit;text-decoration:none}
    input,select,textarea,button{font:inherit}
    .container{max-width:var(--container);padding:0 20px;margin:0 auto}

    .topbar{
      position:sticky;
      top:0;
      z-index:20;
      border-bottom:1px solid var(--stroke);
      background:rgba(11,18,32,.62);
      backdrop-filter:blur(14px);
    }
    .topbar-inner{
      min-height:70px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
    }
    .brand{display:flex;align-items:center;gap:10px}
    .logo{
      width:40px;
      height:40px;
      border-radius:12px;
      background:var(--grad);
      display:grid;
      place-items:center;
      border:1px solid rgba(255,255,255,.2);
    }
    .logo svg{width:20px;height:20px;fill:#fff}
    .brand-name{font-weight:800;letter-spacing:-.02em}
    .brand-name span:last-child{
      color:transparent;
      background:var(--grad);
      background-clip:text;
      -webkit-background-clip:text;
    }

    .btn{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 14px;
      border-radius:13px;
      border:1px solid var(--stroke);
      background:var(--panel);
      color:var(--text);
      cursor:pointer;
      transition:.2s;
    }
    .btn:hover{transform:translateY(-1px);background:rgba(255,255,255,.1)}
    .btn-primary{border:0;background:var(--grad);box-shadow:var(--shadow)}

    .hero{padding:46px 0 26px}
    .hero-grid{
      display:grid;
      grid-template-columns:1.08fr .92fr;
      gap:18px;
      align-items:stretch;
    }
    .panel{
      border:1px solid var(--stroke);
      border-radius:calc(var(--radius) + 6px);
      background:var(--panel);
      box-shadow:var(--shadow);
    }
    .hero-copy{padding:26px}
    .pill{
      display:inline-flex;
      border:1px solid var(--stroke);
      border-radius:999px;
      padding:6px 10px;
      color:var(--muted);
      font-size:12px;
      background:var(--panel-2);
    }
    h1{
      margin:14px 0 10px;
      font-size:clamp(32px,4vw,54px);
      line-height:1.02;
      letter-spacing:-.03em;
    }
    .lead{margin:0;color:var(--muted);max-width:58ch}
    .hero-actions{margin-top:16px;display:flex;flex-wrap:wrap;gap:10px}

    .highlights{padding:18px}
    .kpi-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:10px;
    }
    .kpi{
      border:1px solid var(--stroke);
      border-radius:14px;
      padding:12px;
      background:rgba(0,0,0,.2);
    }
    .kpi strong{
      font-size:20px;
      letter-spacing:-.02em;
      color:transparent;
      background:var(--grad);
      background-clip:text;
      -webkit-background-clip:text;
      display:block;
    }
    .kpi span{color:var(--muted);font-size:13px}

    section{padding:18px 0 24px}
    .section-head{margin-bottom:12px}
    .section-head h2{margin:0;font-size:24px;letter-spacing:-.02em}
    .section-head p{margin:8px 0 0;color:var(--muted)}

    .cards{
      display:grid;
      grid-template-columns:repeat(3,1fr);
      gap:12px;
    }
    .card{
      border:1px solid var(--stroke);
      border-radius:var(--radius);
      background:var(--panel-2);
      padding:16px;
    }
    .card h3{margin:0 0 8px;font-size:17px}
    .card p{margin:0;color:var(--muted);font-size:14px}

    .cta-wrap{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:14px;
    }
    .cta-block{
      border:1px solid var(--stroke);
      border-radius:22px;
      background:var(--panel);
      padding:18px;
    }
    .cta-block h3{margin:0 0 6px;font-size:20px}
    .cta-block p{margin:0 0 14px;color:var(--muted);font-size:14px}

    .form{display:grid;gap:10px}
    .field{display:grid;gap:6px}
    label{font-size:13px;color:var(--muted)}
    input,select,textarea{
      width:100%;
      border:1px solid var(--stroke);
      background:rgba(0,0,0,.25);
      color:var(--text);
      border-radius:12px;
      padding:10px 12px;
      outline:none;
    }
    textarea{min-height:96px;resize:vertical}
    input:focus,select:focus,textarea:focus{border-color:rgba(17,211,197,.65)}
    .mini-note{font-size:12px;color:var(--muted)}

    footer{
      border-top:1px solid var(--stroke);
      margin-top:22px;
      padding:22px 0 30px;
      color:var(--muted);
      font-size:13px;
    }

    @media (max-width:980px){
      .hero-grid,.cta-wrap{grid-template-columns:1fr}
      .cards{grid-template-columns:1fr 1fr}
    }
    @media (max-width:680px){
      .cards,.kpi-grid{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="container topbar-inner">
      <a class="brand" href="#">
        <div class="logo" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M6.5 5.5c0-.55.45-1 1-1h2c.55 0 1 .45 1 1v4.2l3.25-3.25c.2-.2.46-.3.73-.3H17c.55 0 1 .45 1 1v11c0 .55-.45 1-1 1h-2c-.55 0-1-.45-1-1V11.7l-3.25 3.25c-.2.2-.46.3-.73.3H7.5c-.55 0-1-.45-1-1v-8.8Z"/></svg>
        </div>
        <div class="brand-name">Host<span>Up</span></div>
      </a>
      <a class="btn btn-primary" href="#contatto">Richiedi valutazione</a>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="container hero-grid">
        <div class="panel hero-copy">
          <span class="pill">Gestione affitti brevi 360°</span>
          <h1>Tu incassi. Noi gestiamo tutto.</h1>
          <p class="lead">
            HostUp segue burocrazia, pricing, operatività, ospiti, pulizie e distribuzione online. Con app e report chiari, hai il controllo senza occuparti del lavoro quotidiano.
          </p>
          <div class="hero-actions">
            <a class="btn btn-primary" href="#contatto">Parliamone via email</a>
            <a class="btn" href="mailto:puscastanislav@gmail.com?subject=Richiesta%20informazioni%20HostUp">Scrivi subito</a>
          </div>
        </div>

        <aside class="panel highlights">
          <div class="kpi-grid">
            <div class="kpi"><strong>+Visibilità</strong><span>OTA + canali diretti + partner esteri</span></div>
            <div class="kpi"><strong>Prezzi dinamici</strong><span>Revenue management continuo</span></div>
            <div class="kpi"><strong>Operatività completa</strong><span>Ospiti, check-in, pulizie, manutenzioni</span></div>
            <div class="kpi"><strong>Controllo via app</strong><span>Aggiornamenti e dati sempre disponibili</span></div>
          </div>
        </aside>
      </div>
    </section>

    <section>
      <div class="container">
        <div class="section-head">
          <h2>Cosa facciamo</h2>
          <p>Dalla preparazione dell’immobile alla gestione giornaliera, con un approccio automatizzato e orientato al risultato.</p>
        </div>
        <div class="cards">
          <article class="card">
            <h3>Setup e posizionamento</h3>
            <p>Foto professionali, annunci ottimizzati e strategia canali per partire con la massima efficacia.</p>
          </article>
          <article class="card">
            <h3>Gestione operativa</h3>
            <p>Assistenza ospiti, coordinamento pulizie e gestione imprevisti senza caricare il proprietario.</p>
          </article>
          <article class="card">
            <h3>Controllo e report</h3>
            <p>Monitoraggio performance, ricavi e stato unità con aggiornamenti chiari e frequenti.</p>
          </article>
        </div>
      </div>
    </section>

    <section id="contatto">
      <div class="container cta-wrap">
        <div class="cta-block">
          <h3>Richiedi una valutazione</h3>
          <p>Compila il form e si apre una bozza email pronta per l’invio a <strong>puscastanislav@gmail.com</strong>.</p>
          <form class="form js-mailto-form" data-subject="Richiesta valutazione immobile - HostUp">
            <div class="field">
              <label for="name">Nome e cognome</label>
              <input id="name" name="name" required placeholder="Es. Marco Rossi" />
            </div>
            <div class="field">
              <label for="email">Email</label>
              <input id="email" name="email" type="email" required placeholder="nome@email.it" />
            </div>
            <div class="field">
              <label for="phone">Telefono</label>
              <input id="phone" name="phone" placeholder="+39..." />
            </div>
            <div class="field">
              <label for="units">Unità da gestire</label>
              <select id="units" name="units" required>
                <option value="" selected disabled>Seleziona</option>
                <option>1</option>
                <option>2-3</option>
                <option>4-10</option>
                <option>10+</option>
              </select>
            </div>
            <div class="field">
              <label for="msg">Note</label>
              <textarea id="msg" name="msg" placeholder="Città, tipologia immobile, stato attuale..."></textarea>
            </div>
            <button class="btn btn-primary" type="submit">Invia richiesta via email</button>
            <p class="mini-note">Si aprirà il tuo client di posta con i dati precompilati.</p>
          </form>
        </div>

        <div class="cta-block">
          <h3>Prenota una call veloce</h3>
          <p>Lascia due dettagli e prepara direttamente una email di contatto rapido.</p>
          <form class="form js-mailto-form" data-subject="Prenotazione call conoscitiva - HostUp">
            <div class="field">
              <label for="name2">Nome</label>
              <input id="name2" name="name" required placeholder="Il tuo nome" />
            </div>
            <div class="field">
              <label for="city2">Città immobile</label>
              <input id="city2" name="city" required placeholder="Es. Milano" />
            </div>
            <div class="field">
              <label for="pref2">Preferenza oraria</label>
              <select id="pref2" name="time_pref" required>
                <option value="" selected disabled>Seleziona</option>
                <option>Mattina</option>
                <option>Pomeriggio</option>
                <option>Sera</option>
              </select>
            </div>
            <button class="btn btn-primary" type="submit">Prenota via email</button>
            <a class="btn" href="mailto:puscastanislav@gmail.com?subject=Contatto%20rapido%20HostUp">Oppure scrivi manualmente</a>
          </form>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="container">
      © <?= date('Y') ?> HostUp. Gestione affitti brevi 360°.
    </div>
  </footer>

  <script>
    function isValidEmail(v) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    }

    document.querySelectorAll(".js-mailto-form").forEach((form) => {
      form.addEventListener("submit", (e) => {
        e.preventDefault();

        const data = Object.fromEntries(new FormData(form).entries());
        const entries = Object.entries(data).map(([k, v]) => [k, String(v || "").trim()]);

        for (const [key, value] of entries) {
          const field = form.querySelector(`[name="${key}"]`);
          if (field && field.hasAttribute("required") && !value) {
            field.focus();
            return;
          }
        }

        if (data.email && !isValidEmail(String(data.email))) {
          const emailField = form.querySelector('[name="email"]');
          if (emailField) emailField.focus();
          return;
        }

        const subject = String(form.dataset.subject || "Richiesta informazioni HostUp");
        const body = entries
          .map(([k, v]) => `${k}: ${v || "-"}`)
          .join("\n");

        const href = `mailto:puscastanislav@gmail.com?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        window.location.href = href;
      });
    });
  </script>
</body>
</html>
