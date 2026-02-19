/* HostUp CRM — Pipeline
 * - Drag & Drop tra stage (update via api/leads_stage.php)
 * - Eliminazione lead (api/leads_delete.php)
 *
 * NOTE: aggiorna solo questa riga se il tuo endpoint lista lead ha un nome diverso.
 */
const LEADS_LIST_ENDPOINT = `${CRM_BASE_URL}/api/leads_list.php`;

const STAGES = [
  { key: "new", title: "Nuovi" },
  { key: "contacted", title: "Contattati" },
  { key: "qualified", title: "Qualificati" },
  { key: "negotiation", title: "Trattativa" },
  { key: "won", title: "Acquisiti" },
  { key: "lost", title: "Persi" },
];

let ALL = [];          // lista completa
let VIEW = [];         // filtrata
let dragId = null;     // lead id in drag

const $ = (s) => document.querySelector(s);

function esc(str) {
  return String(str ?? "").replace(/[&<>"']/g, (m) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;", "'": "&#039;"
  }[m]));
}

function fmtDate(s) {
  if (!s) return "";
  // accetta "YYYY-MM-DD HH:MM:SS" o ISO
  const d = new Date(s.replace(" ", "T"));
  if (isNaN(d.getTime())) return String(s);
  return d.toLocaleString("it-IT", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

async function apiPost(url, data) {
  const res = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
    body: JSON.stringify(data || {})
  });
  const txt = await res.text();
  let json;
  try { json = JSON.parse(txt); } catch (e) { json = { ok: false, error: "Risposta non JSON", raw: txt }; }
  if (!res.ok || !json.ok) {
    throw new Error(json.error || `Errore API (${res.status})`);
  }
  return json;
}

async function loadLeads() {
  const res = await fetch(LEADS_LIST_ENDPOINT, { credentials: "same-origin" });
  const txt = await res.text();
  let json;
  try { json = JSON.parse(txt); } catch (e) { json = { ok: false, error: "Risposta non JSON", raw: txt }; }

  if (!json.ok) {
    console.error("Leads list error:", json);
    alert("Errore caricamento lead. Controlla endpoint lista lead e log console.");
    ALL = [];
    VIEW = [];
    render();
    return;
  }

  // atteso: { ok:true, leads:[ ... ] }
  ALL = Array.isArray(json.leads) ? json.leads : [];
  applyFilters();
  render();
}

function applyFilters() {
  const q = ($("#q")?.value || "").trim().toLowerCase();
  const st = ($("#stageFilter")?.value || "").trim();

  VIEW = ALL.filter(l => {
    const hay = [
      l.id, l.name, l.email, l.phone, l.unit, l.stage
    ].map(x => String(x ?? "").toLowerCase()).join(" ");

    if (q && !hay.includes(q)) return false;
    if (st && String(l.stage) !== st) return false;
    return true;
  });
}

function render() {
  renderKanban();
  renderTable();
}

function renderKanban() {
  const root = $("#kanban");
  if (!root) return;

  // group by stage
  const byStage = {};
  for (const s of STAGES) byStage[s.key] = [];
  for (const l of VIEW) {
    const k = byStage[l.stage] ? l.stage : "new";
    byStage[k].push(l);
  }

  root.innerHTML = STAGES.map(s => {
    const count = byStage[s.key].length;
    return `
      <div class="col" data-stage="${esc(s.key)}">
        <div class="colHead">
          <div class="colTitle">${esc(s.title)}</div>
          <div class="colMeta">${count}</div>
        </div>
        <div class="colBody droppable" data-stage="${esc(s.key)}"></div>
      </div>
    `;
  }).join("");

  // inject cards
  for (const s of STAGES) {
    const body = root.querySelector(`.colBody[data-stage="${CSS.escape(s.key)}"]`);
    if (!body) continue;

    const list = byStage[s.key]
      .slice()
      .sort((a, b) => (String(b.created_at || "").localeCompare(String(a.created_at || ""))));

    body.innerHTML = list.map(l => cardTpl(l)).join("");
  }

  // bind drag/drop events
  bindDnD(root);

  // bind delete buttons
  root.querySelectorAll("[data-action='delete-lead']").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();
      e.stopPropagation();
      const id = btn.getAttribute("data-id");
      await doDelete(id);
    });
  });

  // bind card click (open scheda) -> se hai già un dettaglio, sostituisci qui
  root.querySelectorAll(".card[data-id]").forEach(card => {
    card.addEventListener("click", () => {
      const id = card.getAttribute("data-id");
      // TODO: se hai già una pagina scheda: window.location = `${CRM_BASE_URL}/lead.php?id=${id}`;
      // per ora: highlight
      console.log("Open lead:", id);
    });
  });
}

function cardTpl(l) {
  const id = esc(l.id);
  const name = esc(l.name || "—");
  const email = esc(l.email || "");
  const phone = esc(l.phone || "");
  const unit = esc(l.unit || "");
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
        ${unit ? `<div class="line">🏠 ${unit}</div>` : ``}
      </div>
    </div>
  `;
}

function bindDnD(root) {
  // cards
  root.querySelectorAll(".card[draggable='true']").forEach(card => {
    card.addEventListener("dragstart", (e) => {
      dragId = card.getAttribute("data-id");
      card.classList.add("dragging");
      e.dataTransfer.effectAllowed = "move";
      try { e.dataTransfer.setData("text/plain", dragId); } catch { }
    });

    card.addEventListener("dragend", () => {
      dragId = null;
      card.classList.remove("dragging");
      root.querySelectorAll(".droppable").forEach(d => d.classList.remove("dropOver"));
    });
  });

  // columns
  root.querySelectorAll(".droppable").forEach(drop => {
    drop.addEventListener("dragover", (e) => {
      e.preventDefault();
      drop.classList.add("dropOver");
      e.dataTransfer.dropEffect = "move";
    });

    drop.addEventListener("dragleave", () => {
      drop.classList.remove("dropOver");
    });

    drop.addEventListener("drop", async (e) => {
      e.preventDefault();
      drop.classList.remove("dropOver");

      const stage = drop.getAttribute("data-stage");
      const id = dragId || (function () {
        try { return e.dataTransfer.getData("text/plain"); } catch { return null; }
      })();

      if (!id || !stage) return;

      // se non cambia stage, nulla
      const lead = ALL.find(x => String(x.id) === String(id));
      if (!lead) return;
      if (String(lead.stage) === String(stage)) return;

      // optimistic UI
      const prev = lead.stage;
      lead.stage = stage;
      applyFilters();
      render();

      try {
        await apiPost(`${CRM_BASE_URL}/api/leads_stage.php`, { id, stage });
      } catch (err) {
        // rollback
        lead.stage = prev;
        applyFilters();
        render();
        alert("Errore spostamento: " + err.message);
      }
    });
  });
}

function renderTable() {
  const tbody = $("#tbl tbody");
  if (!tbody) return;

  const rows = VIEW
    .slice()
    .sort((a, b) => Number(b.id || 0) - Number(a.id || 0))
    .map(l => {
      const id = esc(l.id);
      return `
        <tr data-id="${id}">
          <td>${id}</td>
          <td>${esc(fmtDate(l.created_at))}</td>
          <td>${esc(l.name || "—")}</td>
          <td>${esc(l.email || "")}</td>
          <td>${esc(l.phone || "")}</td>
          <td>${esc(l.unit || "")}</td>
          <td>${esc(l.stage || "")}</td>
          <td><button class="iconBtn" title="Elimina" data-action="delete-lead" data-id="${id}">🗑️</button></td>
        </tr>
      `;
    });

  tbody.innerHTML = rows.join("");

  // click riga (scheda)
  tbody.querySelectorAll("tr[data-id]").forEach(tr => {
    tr.addEventListener("click", (e) => {
      // se clicchi sul bottone delete, non aprire
      if (e.target && e.target.closest && e.target.closest("[data-action='delete-lead']")) return;
      const id = tr.getAttribute("data-id");
      console.log("Open lead:", id);
      // TODO: window.location = `${CRM_BASE_URL}/lead.php?id=${id}`;
    });
  });

  // delete buttons
  tbody.querySelectorAll("[data-action='delete-lead']").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();
      e.stopPropagation();
      const id = btn.getAttribute("data-id");
      await doDelete(id);
    });
  });
}

async function doDelete(id) {
  if (!id) return;
  const lead = ALL.find(x => String(x.id) === String(id));
  const label = lead ? `${lead.name || ""} (#${id})` : `#${id}`;
  if (!confirm(`Eliminare definitivamente il lead ${label}?`)) return;

  // optimistic remove
  const prevAll = ALL.slice();
  ALL = ALL.filter(x => String(x.id) !== String(id));
  applyFilters();
  render();

  try {
    await apiPost(`${CRM_BASE_URL}/api/leads_delete.php`, { id });
  } catch (err) {
    // rollback
    ALL = prevAll;
    applyFilters();
    render();
    alert("Errore eliminazione: " + err.message);
  }
}

// listeners
window.addEventListener("DOMContentLoaded", () => {
  $("#q")?.addEventListener("input", () => { applyFilters(); render(); });
  $("#stageFilter")?.addEventListener("change", () => { applyFilters(); render(); });

  loadLeads();

  // refresh ogni 60s (se vuoi)
  // setInterval(loadLeads, 60000);
});
