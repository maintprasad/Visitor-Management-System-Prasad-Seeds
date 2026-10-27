// ══════════════════════════════════════════
//  DATA LAYER — localStorage
// ══════════════════════════════════════════
 
const DB = {
  get: (k) => JSON.parse(localStorage.getItem('vsm_'+k) || '[]'),
  set: (k, v) => localStorage.setItem('vsm_'+k, JSON.stringify(v)),
  visitors: () => DB.get('visitors'),
  setVisitors: (v) => DB.set('visitors', v),
  equipment: () => DB.get('equipment'),
  setEquipment: (v) => DB.set('equipment', v),
  alerts: () => DB.get('alerts'),
  setAlerts: (v) => DB.set('alerts', v),
  audit: () => DB.get('audit'),
  setAudit: (v) => DB.set('audit', v),
  absen: () => DB.get('absen'),
  setAbsen: (v) => DB.set('absen', v),
};

// Antrian item yang gagal terkirim ke GAS, supaya bisa diretry otomatis.
// Disimpan terpisah di key sendiri (bukan lewat DB.get/set generik) supaya
// tidak ikut terhapus oleh proses "bersihkan localStorage lama" saat reload.
function getPendingSync() {
  try {
    return JSON.parse(localStorage.getItem('vsm_pending_sync') || '[]');
  } catch (e) {
    return [];
  }
}
function setPendingSync(items) {
  localStorage.setItem('vsm_pending_sync', JSON.stringify(items));
}

// Antrian item yang gagal terkirim ke GAS, supaya bisa diretry otomatis
// (disimpan terpisah dari 'pending sync key' biasa supaya tidak ikut ke-flush oleh DB.get/set generik)
function getPendingSync() {
  try {
    return JSON.parse(localStorage.getItem('vsm_pending_sync') || '[]');
  } catch (e) {
    return [];
  }
}
function setPendingSync(items) {
  localStorage.setItem('vsm_pending_sync', JSON.stringify(items));
}

const _debounceTimers = {};
function debounce(fn, delay) {
  return function(...args) {
    clearTimeout(_debounceTimers[fn.name]);
    _debounceTimers[fn.name] = setTimeout(() => fn.apply(this, args), delay);
  };
}

function genId(prefix) {
  return prefix + '-' + Math.random().toString(36).substr(2,8).toUpperCase();
}

// Cari semua equipment (dari sheet Equipment) yang terkait dengan seorang visitor,
// baik karena sudah ditautkan via visitorId, ATAU karena owner-nya cocok dengan
// nama / company visitor tersebut (untuk data lama yang diinput manual).
function getLinkedEquipment(visitor) {
  if (!visitor) return [];
  const equipment = DB.equipment();
  const nameLower = (visitor.name || '').trim().toLowerCase();
  const companyLower = (visitor.company || '').trim().toLowerCase();

  return equipment.filter(e => {
    if (e.visitorId === visitor.id) return true;
    if (!e.visitorId) {
      const ownerLower = (e.owner || '').trim().toLowerCase();
      if (!ownerLower) return false;
      if (nameLower && ownerLower === nameLower) return true;
      if (companyLower && ownerLower === companyLower) return true;
    }
    return false;
  });
}

function genGatePass() {
  const d = new Date();
  const ds = d.getFullYear().toString() + String(d.getMonth()+1).padStart(2,'0') + String(d.getDate()).padStart(2,'0');
  const existing = DB.equipment().filter(e => e.gatePass && e.gatePass.startsWith('GP-'+ds));
  const num = String(existing.length + 1).padStart(3,'0');
  return 'GP-' + ds + '-' + num;
}

function now() { return new Date().toISOString(); }

function fmtDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'});
}

function fmtTime(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
}

function fmtDateTime(iso) {
  if (!iso) return '—';
  return fmtDate(iso) + ' ' + fmtTime(iso);
}

function fmtDuration(startIso, endIso) {
  const start = new Date(startIso);
  const end = endIso ? new Date(endIso) : new Date();
  const diff = Math.floor((end - start) / 1000);
  if (diff < 0) return '00:00:00';
  const h = Math.floor(diff / 3600);
  const m = Math.floor((diff % 3600) / 60);
  const s = diff % 60;
  return [h,m,s].map(v=>String(v).padStart(2,'0')).join(':');
}

function durationSecs(startIso, endIso) {
  const start = new Date(startIso);
  const end = endIso ? new Date(endIso) : new Date();
  return Math.floor((end - start) / 1000);
}

function isToday(iso) {
  if (!iso) return false;
  const d = new Date(iso);
  const t = new Date();
  return d.getDate() === t.getDate() && d.getMonth() === t.getMonth() && d.getFullYear() === t.getFullYear();
}

// AUDIT LOG
function addAudit(action, entity, detail, oldVal, newVal) {
  const logs = DB.audit();
  logs.unshift({
    id: genId('AUD'),
    action,
    entity,
    detail,
    user: document.getElementById('sb-uname').textContent,
    ts: now(),
    oldVal: oldVal || '',
    newVal: newVal || '',
  });
  // keep last 500
  DB.setAudit(logs.slice(0, 500));
}
