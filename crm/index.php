<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$u = require_login();

/**
 * Single-file CRM:
 * - GET  index.php                 -> HTML UI
 * - GET  index.php?api=list         -> JSON leads
 * - POST index.php?api=update_stage -> JSON {id, stage}
 * - POST index.php?api=delete       -> JSON {id}
 *
 * Uses:
 * - db() -> PDO
 * - json_out($status, $array)
 *
 * Tables:
 *  crm_leads(id, created_at, name, email, phone, stage, ...)
 *  crm_lead_notes(id, lead_id, user_id, note, kind, created_at, ...)
 */

function read_json_body(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

$allowedStages = ['new','contacted','qualified','negotiation','won','lost'];

if (isset($_GET['api'])) {
  $api = (string)$_GET['api'];

  if ($api === 'list') {
    try {
      $st = db()->query("
        SELECT id, created_at, name, email, phone, stage
        FROM crm_leads
        ORDER BY id DESC
      ");
      $leads = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
      json_out(200, ['ok' => true, 'leads' => $leads]);
    } catch (Throwable $e) {
      json_out(500, ['ok' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    }
  }

  if ($api === 'update_stage') {
    $data = read_json_body();
    $id = (int)($data['id'] ?? 0);
    $stage = (string)($data['stage'] ?? '');

    if ($id <= 0 || !in_array($stage, $allowedStages, true)) {
      json_out(400, ['ok' => false, 'error' => 'Parametri non validi']);
    }

    try {
      db()->prepare("UPDATE crm_leads SET stage=? WHERE id=?")->execute([$stage, $id]);
      db()->prepare("INSERT INTO crm_lead_notes (lead_id,user_id,note,kind) VALUES (?,?,?,?)")
         ->execute([$id, (int)$u['id'], "Stage aggiornato a: $stage", 'system']);

      json_out(200, ['ok' => true]);
    } catch (Throwable $e) {
      json_out(500, ['ok' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    }
  }

  if ($api === 'delete') {
    $data = read_json_body();
    $id = (int)($data['id'] ?? 0);

    if ($id <= 0) {
      json_out(400, ['ok' => false, 'error' => 'Parametri non validi']);
    }

    try {
      db()->prepare("DELETE FROM crm_lead_notes WHERE lead_id=?")->execute([$id]);
      db()->prepare("DELETE FROM crm_leads WHERE id=?")->execute([$id]);

      json_out(200, ['ok' => true]);
    } catch (Throwable $e) {
      json_out(500, ['ok' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    }
  }

  json_out(404, ['ok' => false, 'error' => 'API non trovata']);
}

?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HostUp CRM — Pipeline</title>
  <link rel="stylesheet" href="<?= CRM_BASE_URL ?>/assets/crm.css">
  <style>
    .iconBtn{border:0;background:transparent;cursor:pointer;font-size:16px;line-height:1;padding:6px;border-radius:10px}
    .iconBtn:hover{background:rgba(255,255,255,.08)}
    .droppable.dropOver{outline:2px dashed rgba(255,255,255,.35); outline-offset:-6px}
    .card.dragging{opacity:.55}
    .cardTop{display:flex;align-items:center;justify-content:space-between;gap:10px}
    .cardLines .line{margin-top:6px;opacity:.92}
  </style>
</head>
<body class="crm-bg">
  <header class="topbar">
    <div class="wrap">
      <div class="brand">
        <div class="badge"></div>
        <div class="bn">HostUp <span>CRM</span></div>
      </div>

      <div class="right">
        <div class="who"><?= htmlspecialchars($u['name']) ?></div>
        <a class="btn" href="<?= CRM_BASE_URL ?>/logout.php">Esci</a>
      </div>
    </div>
  </header>

  <main class="wrap">
    <section class="head">
      <div>
        <h1>Pipeline Lead</h1>
        <p>Gestisci contatti, step commerciali, note e follow-up. (Ogni lead nasce dalla landing.)</p>
        <p style="margin-top:6px; opacity:.85; font-size:.95rem;">
          💡 Trascina i lead tra le colonne per cambiare stage. Usa 🗑️ per eliminare.
        </p>
      </div>

      <div class="actions">
        <input id="q" class="search" placeholder="Cerca nome / email / tel..." />
        <select id="stageFilter" class="select">
          <option value="">Tutti gli stage</option>
          <option value="new">Nuovi</option>
          <option value="contacted">Contattati</option>
          <option value="qualified">Qualificati</option>
          <option value="negotiation">Trattativa</option>
          <option value="won">Acquisiti</option>
          <option value="lost">Persi</option>
        </select>
      </div>
    </section>

    <section class="kanban" id="kanban"></section>

    <section class="tableCard">
      <div class="tableTop">
        <div class="tt">Lista Lead</div>
        <div class="hint">Click su un lead per aprire la scheda — oppure 🗑️ per eliminarlo</div>
      </div>
      <div class="tableWrap">
        <table class="tbl" id="tbl">
          <thead>
            <tr>
              <th>ID</th><th>Creato</th><th>Nome</th><th>Email</th><th>Telefono</th><th>Stage</th><th>Azioni</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    const CRM_BASE_URL = "<?= CRM_BASE_URL ?>";

    const STAGES = [
      { key: "new",          title: "Nuovi" },
      { key: "contacted",    title: "Contattati" },
      { key: "qualified",    title: "Qualificati" },
      { key: "negotiation",  title: "Trattativa" },
      { key: "won",          title: "Acquisiti" },
      { key: "lost",         title: "Persi" },
    ];

    let ALL = [];
    let VIEW = [];
    let dragId = null;

    const $ = (s) => document.querySelector(s);

    function esc(str){
      return String(str ?? "").replace(/[&<>"']/g, m => ({
        "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"
      }[m]));
    }

    function fmtDate(s){
      if(!s) return "";
      const d = new Date(String(s).replace(" ", "T"));
      if (isNaN(d.getTime())) return String(s);
      return d.toLocaleString("it-IT", { day:"2-digit", month:"2-digit", year:"numeric", hour:"2-digit", minute:"2-digit" });
    }

    async function apiGet(api){
      const res = await fetch(`${CRM_BASE_URL}/index.php?api=${encodeURIComponent(api)}`, { credentials:"same-origin" });
      const txt = await res.text();
      let json;
      try { json = JSON.parse(txt); } catch { json = { ok:false, error:"Risposta non JSON", raw:txt }; }
      if(!res.ok || !json.ok) throw new Error(json.error || `Errore API (${res.status})`);
      return json;
    }

    async function apiPost(api, data){
      const res = await fetch(`${CRM_BASE_URL}/index.php?api=${encodeURIComponent(api)}`, {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        credentials:"same-origin",
        body: JSON.stringify(data || {})
      });
      const txt = await res.text();
      let json;
      try { json = JSON.parse(txt); } catch { json = { ok:false, error:"Risposta non JSON", raw:txt }; }
      if(!res.ok || !json.ok) throw new Error(json.error || `Errore API (${res.status})`);
      return json;
    }

    async function loadLeads(){
      try{
        const json = await apiGet("list");
        ALL = Array.isArray(json.leads) ? json.leads : [];
      } catch(err){
        console.error(err);
        alert("Errore caricamento lead: " + err.message);
        ALL = [];
      }
      applyFilters();
      render();
    }

    function applyFilters(){
      const q = ($("#q")?.value || "").trim().toLowerCase();
      const st = ($("#stageFilter")?.value || "").trim();

      VIEW = ALL.filter(l => {
        const hay = [l.id,l.name,l.email,l.phone,l.stage].map(x => String(x??"").toLowerCase()).join(" ");
        if(q && !hay.includes(q)) return false;
        if(st && String(l.stage) !== st) return false;
        return true;
      });
    }

    function render(){
      renderKanban();
      renderTable();
    }

    function renderKanban(){
      const root = $("#kanban");
      if(!root) return;

      const byStage = {};
      for (const s of STAGES) byStage[s.key] = [];
      for (const l of VIEW) {
        const k = byStage[l.stage] ? l.stage : "new";
        byStage[k].push(l);
      }

      root.innerHTML = STAGES.map(s => `
        <div class="col" data-stage="${esc(s.key)}">
          <div class="colHead">
            <div class="colTitle">${esc(s.title)}</div>
            <div class="colMeta">${byStage[s.key].length}</div>
          </div>
          <div class="colBody droppable" data-stage="${esc(s.key)}"></div>
        </div>
      `).join("");

      for (const s of STAGES) {
        const body = root.querySelector(\`.colBody[data-stage="\${CSS.escape(s.key)}"]\`);
        if(!body) continue;

        const list = byStage[s.key].slice().sort((a,b) => Number(b.id||0)-Number(a.id||0));
        body.innerHTML = list.map(l => cardTpl(l)).join("");
      }

      bindDnD(root);

      root.querySelectorAll("[data-action='delete-lead']").forEach(btn=>{
        btn.addEventListener("click", async (e)=>{
          e.preventDefault(); e.stopPropagation();
          const id = btn.getAttribute("data-id");
          await doDelete(id);
        });
      });

      root.querySelectorAll(".card[data-id]").forEach(card=>{
        card.addEventListener("click", (e)=>{
          if (e.target && e.target.closest && e.target.closest("[data-action='delete-lead']")) return;
          const id = card.getAttribute("data-id");
          if (!id) return;
          window.location.href = `${CRM_BASE_URL}/lead.php?id=${encodeURIComponent(id)}`;
        });
      });
    }

    function cardTpl(l){
      const id = esc(l.id);
      const name = esc(l.name || "—");
      const email = esc(l.email || "");
      const phone = esc(l.phone || "");
      const created = fmtDate(l.created_at);

      return `
        <div class="card" draggable="true" data-id="${id}">
          <div class="cardTop">
            <div class="cardName">${name}</div>
            <button class="iconBtn" title="Elimina" data-action="delete-lead" data-id="${id}">🗑️</button>
          </div>
          <div class="cardMeta">${esc(created)}</div>
          <div class="cardLines">
            ${email ? `<div class="line">✉️ ${email}</div>` : ``}
            ${phone ? `<div class="line">📞 ${phone}</div>` : ``}
          </div>
        </div>
      `;
    }

    function bindDnD(root){
      root.querySelectorAll(".card[draggable='true']").forEach(card=>{
        card.addEventListener("dragstart", (e)=>{
          dragId = card.getAttribute("data-id");
          card.classList.add("dragging");
          e.dataTransfer.effectAllowed = "move";
          try{ e.dataTransfer.setData("text/plain", dragId); }catch{}
        });

        card.addEventListener("dragend", ()=>{
          dragId = null;
          card.classList.remove("dragging");
          root.querySelectorAll(".droppable").forEach(d => d.classList.remove("dropOver"));
        });
      });

      root.querySelectorAll(".droppable").forEach(drop=>{
        drop.addEventListener("dragover", (e)=>{
          e.preventDefault();
          drop.classList.add("dropOver");
          e.dataTransfer.dropEffect = "move";
        });

        drop.addEventListener("dragleave", ()=> drop.classList.remove("dropOver"));

        drop.addEventListener("drop", async (e)=>{
          e.preventDefault();
          drop.classList.remove("dropOver");

          const stage = drop.getAttribute("data-stage");
          const id = dragId || (function(){
            try { return e.dataTransfer.getData("text/plain"); } catch { return null; }
          })();

          if(!id || !stage) return;

          const lead = ALL.find(x => String(x.id) === String(id));
          if(!lead) return;
          if(String(lead.stage) === String(stage)) return;

          const prev = lead.stage;
          lead.stage = stage;
          applyFilters();
          render();

          try{
            await apiPost("update_stage", { id, stage });
          }catch(err){
            lead.stage = prev;
            applyFilters();
            render();
            alert("Errore spostamento: " + err.message);
          }
        });
      });
    }

    function renderTable(){
      const tbody = $("#tbl tbody");
      if(!tbody) return;

      tbody.innerHTML = VIEW.slice().sort((a,b)=>Number(b.id||0)-Number(a.id||0)).map(l=>{
        const id = esc(l.id);
        return `
          <tr data-id="${id}">
            <td>${id}</td>
            <td>${esc(fmtDate(l.created_at))}</td>
            <td>${esc(l.name || "—")}</td>
            <td>${esc(l.email || "")}</td>
            <td>${esc(l.phone || "")}</td>
            <td>${esc(l.stage || "")}</td>
            <td><button class="iconBtn" title="Elimina" data-action="delete-lead" data-id="${id}">🗑️</button></td>
          </tr>
        `;
      }).join("");

      tbody.querySelectorAll("[data-action='delete-lead']").forEach(btn=>{
        btn.addEventListener("click", async (e)=>{
          e.preventDefault(); e.stopPropagation();
          const id = btn.getAttribute("data-id");
          await doDelete(id);
        });
      });

      tbody.querySelectorAll("tr[data-id]").forEach(tr=>{
        tr.addEventListener("click", (e)=>{
          if (e.target && e.target.closest && e.target.closest("[data-action='delete-lead']")) return;
          const id = tr.getAttribute("data-id");
          if (!id) return;
          window.location.href = `${CRM_BASE_URL}/lead.php?id=${encodeURIComponent(id)}`;
        });
      });
    }

    async function doDelete(id){
      if(!id) return;
      const lead = ALL.find(x => String(x.id) === String(id));
      const label = lead ? `${lead.name || ""} (#${id})` : `#${id}`;
      if(!confirm(`Eliminare definitivamente il lead ${label}?`)) return;

      const prevAll = ALL.slice();
      ALL = ALL.filter(x => String(x.id) !== String(id));
      applyFilters();
      render();

      try{
        await apiPost("delete", { id });
      }catch(err){
        ALL = prevAll;
        applyFilters();
        render();
        alert("Errore eliminazione: " + err.message);
      }
    }

    window.addEventListener("DOMContentLoaded", ()=>{
      $("#q")?.addEventListener("input", ()=>{ applyFilters(); render(); });
      $("#stageFilter")?.addEventListener("change", ()=>{ applyFilters(); render(); });
      loadLeads();
    });
  </script>
</body>
</html>
