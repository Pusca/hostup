const $ = (s) => document.querySelector(s);

function esc(str) {
  return String(str ?? "").replace(/[&<>"']/g, (m) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  }[m]));
}

function fmtDate(s) {
  if (!s) return "";
  const d = new Date(String(s).replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return String(s);
  return d.toLocaleString("it-IT", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

async function fetchJson(url, options = {}) {
  const res = await fetch(url, {
    credentials: "same-origin",
    ...options,
  });
  const txt = await res.text();
  let json;
  try {
    json = JSON.parse(txt);
  } catch {
    json = { ok: false, error: "Risposta non JSON", raw: txt };
  }
  if (!res.ok || !json.ok) {
    throw new Error(json.error || `Errore API (${res.status})`);
  }
  return json;
}

async function apiGet(path, params = {}) {
  const qs = new URLSearchParams(params).toString();
  const url = `${CRM_BASE_URL}${path}${qs ? `?${qs}` : ""}`;
  return fetchJson(url);
}

async function apiPost(path, payload = {}) {
  return fetchJson(`${CRM_BASE_URL}${path}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

function noteKindLabel(kind) {
  const map = {
    note: "Nota",
    call: "Chiamata",
    email: "Email",
    whatsapp: "WhatsApp",
    system: "Sistema",
  };
  return map[kind] || kind || "Nota";
}

function renderLeadInfo(lead) {
  const el = $("#leadInfo");
  if (!el) return;

  const email = lead.email ? `<a href="mailto:${esc(lead.email)}">${esc(lead.email)}</a>` : "-";
  const phone = lead.phone ? `<a href="tel:${esc(lead.phone)}">${esc(lead.phone)}</a>` : "-";

  el.innerHTML = `
    <div class="boxTitle">Dettagli lead</div>
    <div class="kv"><strong>Nome:</strong> ${esc(lead.name || "-")}</div>
    <div class="kv"><strong>Email:</strong> ${email}</div>
    <div class="kv"><strong>Telefono:</strong> ${phone}</div>
    <div class="kv"><strong>Unita:</strong> ${esc(lead.units || "-")}</div>
    <div class="kv"><strong>Fonte:</strong> ${esc(lead.source || "-")}</div>
    <div class="kv"><strong>Creato:</strong> ${esc(fmtDate(lead.created_at))}</div>
    <div class="kv" style="margin-top:10px;"><strong>Messaggio:</strong><br>${esc(lead.msg || "-")}</div>
  `;
}

function renderNotes(notes) {
  const el = $("#notes");
  if (!el) return;

  if (!Array.isArray(notes) || notes.length === 0) {
    el.innerHTML = `<div class="muted">Nessuna nota presente.</div>`;
    return;
  }

  el.innerHTML = notes.map((n) => `
    <article class="note">
      <div class="noteTop">
        <strong>${esc(noteKindLabel(n.kind))}</strong>
        <span>${esc(fmtDate(n.created_at))}</span>
      </div>
      <div class="noteBody">${esc(n.note || "")}</div>
      <div class="noteBy">${esc(n.user_name || "Sistema")}</div>
    </article>
  `).join("");
}

async function loadLead(id) {
  const json = await apiGet("/api/lead_get.php", { id });
  const lead = json.lead || {};
  renderLeadInfo(lead);
  renderNotes(json.notes || []);

  const stage = $("#stage");
  if (stage && lead.stage) {
    stage.value = String(lead.stage);
  }

  const sub = $("#sub");
  if (sub) {
    sub.textContent = `${lead.name || "Lead"} - ${lead.email || ""}`.trim();
  }
}

async function initLeadPage() {
  const card = $("#leadCard");
  if (!card) return;

  const id = Number(card.getAttribute("data-id") || "0");
  if (!id) return;

  const saveStage = $("#saveStage");
  const addNote = $("#addNote");

  async function reload() {
    try {
      await loadLead(id);
    } catch (err) {
      alert("Errore caricamento lead: " + err.message);
    }
  }

  if (saveStage) {
    saveStage.addEventListener("click", async () => {
      const stage = ($("#stage")?.value || "").trim();
      if (!stage) return;
      try {
        await apiPost("/api/lead_update_stage.php", { id, stage });
        await reload();
      } catch (err) {
        alert("Errore aggiornamento stage: " + err.message);
      }
    });
  }

  if (addNote) {
    addNote.addEventListener("click", async () => {
      const note = ($("#note")?.value || "").trim();
      const kind = ($("#kind")?.value || "note").trim();
      if (!note) {
        alert("Inserisci una nota prima di salvare.");
        return;
      }
      try {
        await apiPost("/api/lead_add_note.php", { id, note, kind });
        if ($("#note")) $("#note").value = "";
        await reload();
      } catch (err) {
        alert("Errore salvataggio nota: " + err.message);
      }
    });
  }

  await reload();
}

window.addEventListener("DOMContentLoaded", () => {
  initLeadPage().catch((err) => {
    alert("Errore inizializzazione pagina lead: " + err.message);
  });
});
