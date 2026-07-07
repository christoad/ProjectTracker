<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>KH1 CW Key — Beta Builder Feedback</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --accent: #1a56db;
  --accent-dark: #1240a8;
  --accent-soft: #eef4fd;
  --header-bg: #162038;
  --bg: #e8f0fe;
  --card: #ffffff;
  --border: #c7d9fb;
  --text: #0f1c3f;
  --muted: #6b7280;
  --dim: #9ca3af;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --font: 'Figtree', sans-serif;
  --mono: 'IBM Plex Mono', monospace;
  --radius: 10px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; }

/* ── Header ── */
header {
  background: var(--header-bg);
  color: #eef2f7;
  padding: 14px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: 0 2px 16px rgba(0,0,0,0.35);
}
.header-logo {
  font-family: var(--mono);
  font-size: 0.7rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: #64748b;
}
.header-title { font-size: 1rem; font-weight: 600; color: #f1f5f9; }
.header-sub   { font-size: 0.78rem; color: #64748b; font-family: var(--mono); margin-top: 1px; }
.header-callsign {
  margin-left: auto;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.14);
  border-radius: 6px;
  padding: 6px 12px;
  font-family: var(--mono);
  font-size: 0.8rem;
  color: #94a3b8;
  cursor: pointer;
  white-space: nowrap;
}
.header-callsign strong { color: #e2e8f0; }

/* ── Main layout ── */
.wrap {
  max-width: 560px;
  margin: 0 auto;
  padding: 20px 16px 60px;
}

/* ── Callsign screen ── */
#callsign-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 0 20px;
  gap: 0;
}
.welcome-logo {
  font-family: var(--mono);
  font-size: 0.7rem;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--accent);
  margin-bottom: 12px;
}
.welcome-title {
  font-size: 1.7rem;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -0.02em;
  text-align: center;
  margin-bottom: 6px;
}
.welcome-sub {
  font-size: 0.92rem;
  color: var(--muted);
  text-align: center;
  line-height: 1.6;
  max-width: 380px;
  margin-bottom: 32px;
}
.callsign-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 28px 24px;
  width: 100%;
  max-width: 360px;
  box-shadow: 0 4px 20px rgba(10,30,100,0.08);
}
.callsign-card label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 8px;
}
.callsign-card input[type="text"] {
  width: 100%;
  padding: 14px 16px;
  font-family: var(--mono);
  font-size: 1.4rem;
  font-weight: 500;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  border: 2px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  background: #f8fbff;
  outline: none;
  transition: border-color 0.15s;
  text-align: center;
}
.callsign-card input[type="text"]:focus { border-color: var(--accent); }
.callsign-card .hint {
  font-size: 0.78rem;
  color: var(--dim);
  text-align: center;
  margin-top: 8px;
}
.btn-start {
  margin-top: 18px;
  width: 100%;
  background: var(--accent);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 15px;
  font-family: var(--font);
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
  letter-spacing: 0.01em;
}
.btn-start:hover { background: var(--accent-dark); }

/* ── Form screen ── */
#form-screen { display: none; }

/* ── Progress ── */
.progress-wrap {
  margin-bottom: 20px;
}
.progress-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.progress-label {
  font-size: 0.78rem;
  font-family: var(--mono);
  color: var(--muted);
  letter-spacing: 0.06em;
}
.progress-count {
  font-size: 0.78rem;
  font-family: var(--mono);
  color: var(--accent);
  font-weight: 500;
}
.progress-bar {
  height: 4px;
  background: var(--border);
  border-radius: 4px;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  background: var(--accent);
  border-radius: 4px;
  transition: width 0.4s ease;
}

/* ── Step selector ── */
.step-selector-wrap {
  margin-bottom: 16px;
}
.step-selector-wrap select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--card);
  font-family: var(--font);
  font-size: 0.9rem;
  color: var(--text);
  outline: none;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  padding-right: 36px;
}
.step-selector-wrap select:focus { border-color: var(--accent); }

/* ── Save status ── */
.save-status {
  font-size: 0.75rem;
  font-family: var(--mono);
  color: var(--success);
  text-align: right;
  height: 18px;
  margin-bottom: 6px;
  transition: opacity 0.3s;
}
.save-status.saving { color: var(--muted); }
.save-status.hidden { opacity: 0; }

/* ── Step card ── */
.step-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(10,30,100,0.07);
  margin-bottom: 16px;
}
.step-card-header {
  background: #1f2937;
  padding: 16px 20px;
  display: flex;
  align-items: baseline;
  gap: 14px;
}
.step-card-header.packaging { background: #123a32; }
.step-card-header.pause     { background: #1a2a1a; }
.step-num {
  font-family: var(--mono);
  font-size: 0.68rem;
  letter-spacing: 0.16em;
  color: var(--accent);
  text-transform: uppercase;
  white-space: nowrap;
}
.step-card-header.packaging .step-num { color: #5fd4a8; }
.step-card-header.pause     .step-num { color: #5fd4a8; }
.step-name {
  font-size: 1rem;
  font-weight: 600;
  color: #f1f5f9;
  letter-spacing: -0.01em;
  line-height: 1.3;
}
.step-card-body { padding: 22px 20px; }

/* ── Packaging step ── */
.pkg-question {
  margin-bottom: 20px;
}
.pkg-question:last-child { margin-bottom: 0; }
.pkg-question-label {
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--text);
  margin-bottom: 10px;
  line-height: 1.4;
}
.pkg-radio-group {
  display: flex;
  gap: 10px;
}
.pkg-radio-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 12px 8px;
  border: 2px solid var(--border);
  border-radius: 8px;
  background: #f8fbff;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--muted);
  transition: all 0.15s;
  user-select: none;
}
.pkg-radio-btn .pkg-icon { font-size: 1.4rem; line-height: 1; }
.pkg-radio-btn:hover { border-color: var(--accent); background: var(--accent-soft); }
.pkg-radio-btn.selected-yes { border-color: var(--success); background: #f0fdf6; color: #166534; }
.pkg-radio-btn.selected-no  { border-color: var(--danger);  background: #fef2f2;  color: #991b1b; }

/* ── Rating buttons ── */
.rating-label-text {
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 12px;
}
.rating-group {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 18px;
}
.rating-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 14px 8px;
  border: 2px solid var(--border);
  border-radius: 10px;
  background: #f8fbff;
  cursor: pointer;
  font-family: var(--font);
  transition: all 0.15s;
  user-select: none;
}
.rating-btn .r-icon  { font-size: 1.6rem; line-height: 1; }
.rating-btn .r-label { font-size: 0.78rem; font-weight: 500; color: var(--muted); line-height: 1.2; text-align: center; }
.rating-btn:hover    { border-color: var(--accent); background: var(--accent-soft); }
.rating-btn.sel-1    { border-color: var(--success); background: #f0fdf6; }
.rating-btn.sel-1 .r-label { color: #166534; }
.rating-btn.sel-2    { border-color: var(--warning); background: #fffbeb; }
.rating-btn.sel-2 .r-label { color: #92400e; }
.rating-btn.sel-3    { border-color: var(--danger);  background: #fef2f2; }
.rating-btn.sel-3 .r-label { color: #991b1b; }

/* ── Feedback textarea ── */
.feedback-label {
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.feedback-label .optional {
  font-weight: 400;
  text-transform: none;
  letter-spacing: 0;
  font-size: 0.75rem;
}
textarea.feedback-input {
  width: 100%;
  min-height: 100px;
  padding: 12px 14px;
  border: 1px solid var(--border);
  border-radius: 8px;
  font-family: var(--font);
  font-size: 0.92rem;
  color: var(--text);
  background: #f8fbff;
  resize: vertical;
  outline: none;
  transition: border-color 0.15s;
  line-height: 1.5;
}
textarea.feedback-input:focus { border-color: var(--accent); }

/* ── Notes ── */
textarea.notes-input {
  width: 100%;
  min-height: 80px;
  padding: 12px 14px;
  border: 1px solid var(--border);
  border-radius: 8px;
  font-family: var(--font);
  font-size: 0.92rem;
  color: var(--text);
  background: #f8fbff;
  resize: vertical;
  outline: none;
  transition: border-color 0.15s;
  line-height: 1.5;
  margin-top: 12px;
}
textarea.notes-input:focus { border-color: var(--accent); }

/* ── Navigation ── */
.form-nav {
  display: flex;
  gap: 12px;
  margin-top: 4px;
}
.btn-nav {
  flex: 1;
  padding: 14px;
  border-radius: 8px;
  font-family: var(--font);
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  border: 2px solid transparent;
}
.btn-prev {
  background: var(--card);
  border-color: var(--border);
  color: var(--muted);
}
.btn-prev:hover { border-color: var(--accent); color: var(--accent); }
.btn-prev:disabled { opacity: 0.3; cursor: default; }
.btn-next {
  background: var(--accent);
  color: #fff;
  border-color: var(--accent);
}
.btn-next:hover { background: var(--accent-dark); }
.btn-finish {
  background: var(--success);
  color: #fff;
  border-color: var(--success);
}
.btn-finish:hover { background: #0d9467; }

/* ── Complete screen ── */
#complete-screen {
  display: none;
  text-align: center;
  padding: 40px 0;
}
.complete-icon { font-size: 4rem; margin-bottom: 16px; }
.complete-title { font-size: 1.6rem; font-weight: 700; color: var(--text); margin-bottom: 8px; letter-spacing: -0.02em; }
.complete-sub { font-size: 0.95rem; color: var(--muted); line-height: 1.6; max-width: 340px; margin: 0 auto 28px; }
.btn-restart {
  background: transparent;
  border: 2px solid var(--border);
  border-radius: 8px;
  padding: 12px 24px;
  font-family: var(--font);
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--muted);
  cursor: pointer;
}
.btn-restart:hover { border-color: var(--accent); color: var(--accent); }

/* ── Coming improvements bubble ── */
.updates-bubble {
  background: #f0f6ff;
  border: 1px solid #c7d9fb;
  border-left: 3px solid var(--accent);
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 20px;
  font-size: 0.84rem;
  line-height: 1.5;
}
.updates-bubble-heading {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: var(--accent);
  margin-bottom: 7px;
}
.updates-bubble ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.updates-bubble ul li {
  color: var(--muted);
  padding-left: 14px;
  position: relative;
}
.updates-bubble ul li::before {
  content: '→';
  position: absolute;
  left: 0;
  color: var(--accent);
  font-size: 0.75rem;
  top: 1px;
}

/* ── General step extra ── */
.general-prompts {
  list-style: none;
  margin-bottom: 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.general-prompts li {
  font-size: 0.88rem;
  color: var(--muted);
  padding-left: 14px;
  position: relative;
  line-height: 1.5;
}
.general-prompts li::before {
  content: '·';
  position: absolute;
  left: 0;
  color: var(--accent);
  font-weight: bold;
}
</style>
</head>
<body>

<header>
  <div>
    <div class="header-logo">KI6CR Labs</div>
    <div class="header-title">KH1 CW Key</div>
    <div class="header-sub">Beta Builder Feedback</div>
  </div>
  <div class="header-callsign" id="headerCallsign" onclick="changeCallsign()" style="display:none;">
    <strong id="headerCallsignText"></strong> <span style="font-size:0.65rem; opacity:0.6;">▾ change</span>
  </div>
</header>

<div class="wrap">

  <!-- ── Upcoming improvements notice ── -->
  <div class="updates-bubble">
    <div class="updates-bubble-heading">Currently in development</div>
    <ul>
      <li>Photos of every part in the kit for easy identification</li>
    </ul>
  </div>

  <!-- ── Callsign entry screen ── -->
  <div id="callsign-screen">
    <div class="welcome-logo">KI6CR Labs · Beta Program</div>
    <div class="welcome-title">Build Feedback</div>
    <p class="welcome-sub">Your feedback helps make the KH1 kit better. Enter your callsign to get started — your progress saves automatically as you go.</p>
    <div class="callsign-card">
      <label for="callsignInput">Your Callsign</label>
      <input type="text" id="callsignInput" placeholder="Callsign" autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false" maxlength="20">
      <div class="hint">Letters, numbers, and / only</div>
      <button class="btn-start" onclick="startForm()">Start Feedback →</button>
    </div>
  </div>

  <!-- ── Main form screen ── -->
  <div id="form-screen">

    <div class="progress-wrap">
      <div class="progress-meta">
        <span class="progress-label" id="progressLabel">Step 1 of 19</span>
        <span class="progress-count" id="progressCount">0 completed</span>
      </div>
      <div class="progress-bar"><div class="progress-fill" id="progressFill" style="width:0%"></div></div>
    </div>

    <div class="step-selector-wrap">
      <select id="stepSelector" onchange="jumpToStep(parseInt(this.value))"></select>
    </div>

    <div class="save-status hidden" id="saveStatus">✓ Saved</div>

    <!-- Step content rendered here -->
    <div id="stepContent"></div>

    <div class="form-nav">
      <button class="btn-nav btn-prev" id="btnPrev" onclick="navigate(-1)">← Previous</button>
      <button class="btn-nav btn-next" id="btnNext" onclick="navigate(1)">Save & Next →</button>
    </div>

  </div>

  <!-- ── Complete screen ── -->
  <div id="complete-screen">
    <div class="complete-icon">🎉</div>
    <div class="complete-title">Thank you!</div>
    <p class="complete-sub">Your feedback has been recorded. You can go back to update any step, or come back later by scanning the QR code again — your answers are saved.</p>
    <button class="btn-restart" onclick="goBackToForm()">← Review My Responses</button>
  </div>

</div>

<script>
const STEPS = [
  { key: 'packaging', label: 'Packaging & Shipping Check', type: 'packaging', num: '' },
  { key: 'step01',  label: 'Unpack & Inventory',              type: 'standard', num: 'Step 01' },
  { key: 'step02',  label: 'Magnet Wire Prep',                type: 'standard', num: 'Step 02' },
  { key: 'step03',  label: 'Threading the Paddles',           type: 'standard', num: 'Step 03' },
  { key: 'step04',  label: 'The Wire Loop',                   type: 'standard', num: 'Step 04' },
  { key: 'step05',  label: 'Contact Set Screws',              type: 'standard', num: 'Step 05' },
  { key: 'step06',  label: 'Continuity Check & Wire Marking', type: 'standard', num: 'Step 06' },
  { key: 'step07',  label: 'Secure Bearings & Magnets',       type: 'standard', num: 'Step 07' },
  { key: 'step08',  label: 'Allow Glue to Cure (First)',      type: 'pause',    num: 'Step 08' },
  { key: 'step09',  label: 'Center Lug Installation',         type: 'standard', num: 'Step 09' },
  { key: 'step10',  label: 'Opposing Magnet Polarity',        type: 'standard', num: 'Step 10' },
  { key: 'step11',  label: 'Allow Glue to Cure (Second)',     type: 'pause',    num: 'Step 11' },
  { key: 'step12',  label: 'The Stress-Relief Loop',          type: 'standard', num: 'Step 12' },
  { key: 'step13',  label: 'Install Set Screws',              type: 'standard', num: 'Step 13' },
  { key: 'step14',  label: 'Mechanical Stack',                type: 'standard', num: 'Step 14' },
  { key: 'step15',  label: '3.5mm Jack & PCB Board',         type: 'standard', num: 'Step 15' },
  { key: 'step16',  label: 'Wiring & Soldering',              type: 'standard', num: 'Step 16' },
  { key: 'step17',  label: 'Calibration & Final Assembly',    type: 'standard', num: 'Step 17' },
  { key: 'general', label: 'General Feedback',                type: 'general',  num: 'Final'   },
];

let callsign  = '';
let currentStep = 0;
let responses = {};
let saveTimer = null;

// ── Boot ──────────────────────────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
  document.getElementById('callsignInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') startForm();
  });
  buildStepSelector();
});

function buildStepSelector() {
  const sel = document.getElementById('stepSelector');
  STEPS.forEach((s, i) => {
    const opt = document.createElement('option');
    opt.value = i;
    opt.textContent = s.num ? s.num + ' — ' + s.label : '★ ' + s.label;
    sel.appendChild(opt);
  });
}

// ── Start / callsign ──────────────────────────────────────────────────────────
async function startForm() {
  const raw = document.getElementById('callsignInput').value.trim().toUpperCase().replace(/[^A-Z0-9\/]/g,'');
  if (raw.length < 3) { alert('Please enter a valid callsign.'); return; }
  callsign = raw;
  localStorage.setItem('kh1_callsign', callsign);

  // Register session
  const fd = new FormData();
  fd.append('action', 'save_session');
  fd.append('callsign', callsign);
  await fetch('kh1_feedback_api.php', { method:'POST', body:fd }).catch(()=>{});

  // Load saved responses
  try {
    const r = await fetch('kh1_feedback_api.php?action=get_responses&callsign=' + encodeURIComponent(callsign));
    const d = await r.json();
    if (d.responses) responses = d.responses;
  } catch(e) {}

  document.getElementById('callsign-screen').style.display = 'none';
  document.getElementById('form-screen').style.display = 'block';
  document.getElementById('headerCallsign').style.display = 'flex';
  document.getElementById('headerCallsignText').textContent = callsign;

  renderStep(0);
}

function changeCallsign() {
  if (!confirm('Switch callsign? Your saved responses will stay on the server.')) return;
  document.getElementById('callsign-screen').style.display = 'flex';
  document.getElementById('form-screen').style.display = 'none';
  document.getElementById('complete-screen').style.display = 'none';
  document.getElementById('headerCallsign').style.display = 'none';
}

function goBackToForm() {
  document.getElementById('complete-screen').style.display = 'none';
  document.getElementById('form-screen').style.display = 'block';
  renderStep(currentStep);
}

// ── Navigation ────────────────────────────────────────────────────────────────
function jumpToStep(idx) {
  saveCurrentStep();
  renderStep(idx);
}

function navigate(dir) {
  saveCurrentStep();
  const next = currentStep + dir;
  if (next < 0) return;
  if (next >= STEPS.length) {
    // Show complete screen
    document.getElementById('form-screen').style.display = 'none';
    document.getElementById('complete-screen').style.display = 'block';
    return;
  }
  renderStep(next);
}

// ── Render a step ─────────────────────────────────────────────────────────────
function renderStep(idx) {
  currentStep = idx;
  const step = STEPS[idx];
  const saved = responses[step.key] || {};

  // Update selector
  document.getElementById('stepSelector').value = idx;

  // Progress
  const total    = STEPS.length;
  const done     = Object.keys(responses).filter(k => {
    const r = responses[k];
    return r.rating != null || r.feedback || r.packaging_intact != null;
  }).length;
  const pct = Math.round((idx / (total - 1)) * 100);
  document.getElementById('progressLabel').textContent = 'Step ' + (idx + 1) + ' of ' + total;
  document.getElementById('progressCount').textContent = done + ' saved';
  document.getElementById('progressFill').style.width = pct + '%';

  // Nav buttons
  document.getElementById('btnPrev').disabled = (idx === 0);
  const btnNext = document.getElementById('btnNext');
  if (idx === STEPS.length - 1) {
    btnNext.textContent = 'Submit Feedback ✓';
    btnNext.className = 'btn-nav btn-finish';
  } else {
    btnNext.textContent = 'Save & Next →';
    btnNext.className = 'btn-nav btn-next';
  }

  // Render content
  let html = '';
  const headerClass = step.type === 'packaging' ? 'packaging' : (step.type === 'pause' ? 'pause' : '');

  if (step.type === 'packaging') {
    html = renderPackagingStep(step, saved);
  } else if (step.type === 'general') {
    html = renderGeneralStep(step, saved);
  } else {
    html = renderStandardStep(step, saved);
  }

  document.getElementById('stepContent').innerHTML = html;

  // Wire up rating buttons
  document.querySelectorAll('.rating-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const r = parseInt(btn.dataset.rating);
      document.querySelectorAll('.rating-btn').forEach(b => b.classList.remove('sel-1','sel-2','sel-3'));
      btn.classList.add('sel-' + r);
      autoSave();
    });
  });

  // Wire up pkg radio buttons
  document.querySelectorAll('.pkg-radio-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.dataset.group;
      document.querySelectorAll('.pkg-radio-btn[data-group="' + group + '"]').forEach(b => {
        b.classList.remove('selected-yes','selected-no');
      });
      btn.classList.add('selected-' + btn.dataset.val);
      autoSave();
    });
  });

  // Wire up textarea
  const ta = document.querySelector('textarea');
  if (ta) ta.addEventListener('input', () => scheduleSave());

  hideSaveStatus();
}

function renderStandardStep(step, saved) {
  const rating = saved.rating ? parseInt(saved.rating) : 0;
  const feedback = saved.feedback || '';
  const pauseNote = step.type === 'pause'
    ? '<div style="font-size:0.9rem;color:#5fd4a8;margin-bottom:16px;">This is a waiting step — let the glue cure before continuing.</div>'
    : '';
  return `
<div class="step-card">
  <div class="step-card-header ${step.type === 'pause' ? 'pause' : ''}">
    <span class="step-num">${step.num}</span>
    <span class="step-name">${escHtml(step.label)}</span>
  </div>
  <div class="step-card-body">
    ${pauseNote}
    <div class="rating-label-text">How did this step go?</div>
    <div class="rating-group">
      <button class="rating-btn ${rating===1?'sel-1':''}" data-rating="1">
        <span class="r-icon">👍</span>
        <span class="r-label">All Good</span>
      </button>
      <button class="rating-btn ${rating===2?'sel-2':''}" data-rating="2">
        <span class="r-icon">💬</span>
        <span class="r-label">Had Questions</span>
      </button>
      <button class="rating-btn ${rating===3?'sel-3':''}" data-rating="3">
        <span class="r-icon">⚠️</span>
        <span class="r-label">Had Trouble</span>
      </button>
    </div>
    <div class="feedback-label">Notes <span class="optional">(optional)</span></div>
    <textarea class="feedback-input" id="feedbackText" placeholder="Anything confusing? Something that could be clearer? Great catches welcome.">${escHtml(feedback)}</textarea>
  </div>
</div>`;
}

function renderPackagingStep(step, saved) {
  function pkgBtn(group, val, icon, label, savedVal) {
    const cls = savedVal === val ? ('selected-' + val) : '';
    return `<button class="pkg-radio-btn ${cls}" data-group="${group}" data-val="${val}">
      <span class="pkg-icon">${icon}</span>${label}</button>`;
  }
  const pi = saved.packaging_intact != null ? (parseInt(saved.packaging_intact) === 1 ? 'yes' : 'no') : null;
  const ti = saved.tools_in_box != null ? (parseInt(saved.tools_in_box) === 1 ? 'yes' : 'no') : null;
  const pu = saved.parts_undamaged != null ? (parseInt(saved.parts_undamaged) === 1 ? 'yes' : 'no') : null;
  const notes = saved.feedback || '';

  return `
<div class="step-card">
  <div class="step-card-header packaging">
    <span class="step-num">First</span>
    <span class="step-name">${escHtml(step.label)}</span>
  </div>
  <div class="step-card-body">
    <div class="pkg-question">
      <div class="pkg-question-label">Was the outer packaging intact when it arrived?</div>
      <div class="pkg-radio-group">
        ${pkgBtn('pkg_intact','yes','📦','Yes, intact', pi)}
        ${pkgBtn('pkg_intact','no','📦','No, damaged', pi)}
      </div>
    </div>
    <div class="pkg-question">
      <div class="pkg-question-label">Were all tools still secured in their shipping container?</div>
      <div class="pkg-radio-group">
        ${pkgBtn('tools_box','yes','✅','Yes, all there', ti)}
        ${pkgBtn('tools_box','no','❌','Some missing', ti)}
      </div>
    </div>
    <div class="pkg-question">
      <div class="pkg-question-label">Are all parts present and undamaged?</div>
      <div class="pkg-radio-group">
        ${pkgBtn('parts_ok','yes','✅','All good', pu)}
        ${pkgBtn('parts_ok','no','❌','Issues found', pu)}
      </div>
    </div>
    <div class="feedback-label" style="margin-top:18px;">Notes about packaging <span class="optional">(optional)</span></div>
    <textarea class="notes-input" id="feedbackText" placeholder="Describe any damage or issues found.">${escHtml(notes)}</textarea>
  </div>
</div>`;
}

function renderGeneralStep(step, saved) {
  const feedback = saved.feedback || '';
  const rating   = saved.rating ? parseInt(saved.rating) : 0;
  return `
<div class="step-card">
  <div class="step-card-header">
    <span class="step-num">${step.num}</span>
    <span class="step-name">${escHtml(step.label)}</span>
  </div>
  <div class="step-card-body">
    <div class="rating-label-text">Overall build experience?</div>
    <div class="rating-group">
      <button class="rating-btn ${rating===1?'sel-1':''}" data-rating="1">
        <span class="r-icon">👍</span><span class="r-label">Loved it</span>
      </button>
      <button class="rating-btn ${rating===2?'sel-2':''}" data-rating="2">
        <span class="r-icon">😐</span><span class="r-label">It was OK</span>
      </button>
      <button class="rating-btn ${rating===3?'sel-3':''}" data-rating="3">
        <span class="r-icon">😤</span><span class="r-label">Frustrated</span>
      </button>
    </div>
    <div class="feedback-label">Overall feedback <span class="optional">(optional — but very helpful!)</span></div>
    <ul class="general-prompts">
      <li>What was the hardest part of the build?</li>
      <li>What instruction or image would have helped most?</li>
      <li>Any parts that felt low quality or surprising?</li>
      <li>What did you like most?</li>
    </ul>
    <textarea class="feedback-input" id="feedbackText" placeholder="Any and all thoughts welcome…" style="min-height:130px;">${escHtml(feedback)}</textarea>
  </div>
</div>`;
}

// ── Save logic ────────────────────────────────────────────────────────────────
function collectCurrentData() {
  const step = STEPS[currentStep];
  const data = { step_key: step.key, callsign };

  if (step.type === 'packaging') {
    const pi = document.querySelector('.pkg-radio-btn[data-group="pkg_intact"].selected-yes')  ? 1
             : document.querySelector('.pkg-radio-btn[data-group="pkg_intact"].selected-no')   ? 0 : null;
    const ti = document.querySelector('.pkg-radio-btn[data-group="tools_box"].selected-yes')   ? 1
             : document.querySelector('.pkg-radio-btn[data-group="tools_box"].selected-no')    ? 0 : null;
    const pu = document.querySelector('.pkg-radio-btn[data-group="parts_ok"].selected-yes')    ? 1
             : document.querySelector('.pkg-radio-btn[data-group="parts_ok"].selected-no')     ? 0 : null;
    data.packaging_intact = pi;
    data.tools_in_box     = ti;
    data.parts_undamaged  = pu;
    data.feedback         = (document.getElementById('feedbackText')?.value || '').trim();
  } else {
    const selBtn = document.querySelector('.rating-btn.sel-1, .rating-btn.sel-2, .rating-btn.sel-3');
    data.rating   = selBtn ? parseInt(selBtn.dataset.rating) : null;
    data.feedback = (document.getElementById('feedbackText')?.value || '').trim();
  }
  return data;
}

function saveCurrentStep() {
  const data = collectCurrentData();
  // Update local cache
  responses[data.step_key] = {
    rating: data.rating,
    feedback: data.feedback,
    packaging_intact: data.packaging_intact ?? null,
    tools_in_box: data.tools_in_box ?? null,
    parts_undamaged: data.parts_undamaged ?? null,
  };
  // Fire and forget to server
  sendSave(data);
}

async function sendSave(data) {
  showSaveStatus('saving');
  const fd = new FormData();
  fd.append('action', 'save_response');
  Object.entries(data).forEach(([k,v]) => { if (v !== null && v !== undefined) fd.append(k, v); });
  try {
    await fetch('kh1_feedback_api.php', { method:'POST', body:fd });
    showSaveStatus('saved');
  } catch(e) {
    showSaveStatus('error');
  }
}

function autoSave() {
  const data = collectCurrentData();
  responses[data.step_key] = {
    rating: data.rating,
    feedback: data.feedback,
    packaging_intact: data.packaging_intact ?? null,
    tools_in_box: data.tools_in_box ?? null,
    parts_undamaged: data.parts_undamaged ?? null,
  };
  sendSave(data);
}

function scheduleSave() {
  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => autoSave(), 1500);
}

function showSaveStatus(state) {
  const el = document.getElementById('saveStatus');
  el.classList.remove('hidden','saving');
  if (state === 'saving') {
    el.textContent = '↑ Saving…';
    el.classList.add('saving');
  } else if (state === 'saved') {
    el.textContent = '✓ Saved';
    setTimeout(() => el.classList.add('hidden'), 2500);
  } else {
    el.textContent = '⚠ Could not save';
  }
}

function hideSaveStatus() {
  document.getElementById('saveStatus').classList.add('hidden');
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function escHtml(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
