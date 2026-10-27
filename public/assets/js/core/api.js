// ══════════════════════════════════════════
//  API CLIENT — pengganti GAS integration
//  Protokol sama dengan versi Apps Script: POST {action,data} -> {ok,...}
//  Token login + unit aktif dikirim di setiap request.
// ══════════════════════════════════════════

const Auth = {
  token: Store.get('vsm_token') || null,
  user: null,   // { username, name, role, unit, unitName, units }
  save(token, user) { this.token = token; this.user = user; Store.set('vsm_token', token); },
  clear() { this.token = null; this.user = null; Store.del('vsm_token'); },
  get unit() { return this.user ? this.user.unit : null; },
};

function apiHeaders(unit) {
  const h = { 'Content-Type': 'text/plain;charset=utf-8' };
  if (Auth.token) h['Authorization'] = 'Bearer ' + Auth.token;
  // Petugas security dikunci ke unitnya oleh server; header ini hanya dipakai admin.
  if (unit || Auth.unit) h['X-Unit'] = unit || Auth.unit;
  return h;
}

async function apiRequest(url, opts) {
  const res = await fetch(url, opts);
  let json;
  try { json = await res.json(); } catch (e) { throw new Error('Server mengirim respons tidak valid (HTTP ' + res.status + ')'); }
  if (res.status === 401) { Session.expired(); }
  return json;
}

// GET (dulu JSONP ke Apps Script). Melempar Error bila ok:false.
async function fetchGAS(params) {
  const json = await apiRequest(VSM.apiBase + '?' + new URLSearchParams(params), { headers: apiHeaders() });
  if (!json.ok) throw new Error(json.error || 'Request gagal');
  return json;
}

// POST dengan antrian retry otomatis bila jaringan putus.
async function sendToGAS(action, data, _isRetry, unit) {
  const queueUnit = unit || Auth.unit;
  try {
    const json = await apiRequest(VSM.apiBase, {
      method: 'POST',
      headers: apiHeaders(queueUnit),
      body: JSON.stringify({ action, data }),
    });
    if (!json.ok) {
      console.warn('[API] Server menolak:', action, json.error);
      if (!_isRetry) queuePendingSync(action, data);
      return { ok: false, error: json.error };
    }
    if (action === 'CHECKIN_VISITOR') scheduleRefresh();   // server membuat alat otomatis -> ambil ID aslinya
    return { ok: true, result: json, data: json };
  } catch (err) {
    console.warn('[API] Gagal kirim (network):', action, err.message);
    if (!_isRetry) queuePendingSync(action, data);
    return { ok: false, error: err.message };
  }
}

function updateSyncIndicator() {
  const badge = document.getElementById('sync-badge');
  if (!badge) return;
  const pending = getPendingSync();
  badge.style.display = pending.length ? '' : 'none';
  if (pending.length) badge.textContent = pending.length + ' belum tersinkron';
}

function queuePendingSync(action, data) {
  const pending = getPendingSync();
  const dataId = data && data.id ? data.id : null;
  const dup = dataId && pending.some(p => p.action === action && p.data && p.data.id === dataId);
  if (!dup) {
    pending.push({ action, data, unit: Auth.unit, queuedAt: Date.now(), attempts: 0 });
    setPendingSync(pending);
  }
  updateSyncIndicator();
  toast('Gagal sync ke server, akan dicoba ulang otomatis', 'error');
}

// Antrian dikirim ulang tiap 15 detik; hanya item milik unit yang sedang login.
async function retryPendingSync() {
  if (!Auth.token) return;
  const pending = getPendingSync();
  if (!pending.length) return;
  const still = [];
  for (const item of pending) {
    if (item.unit && item.unit !== Auth.unit && Auth.user.role !== 'admin') { still.push(item); continue; }
    if (item.attempts >= 5) { still.push(item); continue; }
    const res = await sendToGAS(item.action, item.data, true, item.unit);
    if (!res.ok) { item.attempts++; still.push(item); }
  }
  setPendingSync(still);
  updateSyncIndicator();
  if (!still.length) toast('Semua data berhasil tersinkron ke server', 'success');
}
setInterval(retryPendingSync, 15000);

function sendLogToGAS(action, entity, detail) {
  sendToGAS('ADD_LOG', {
    action, entity, detail,
    user: document.getElementById('sb-uname')?.textContent || 'System',
    ts: now(),
  });
}
