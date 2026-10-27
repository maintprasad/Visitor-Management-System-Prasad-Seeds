// ══════════════════════════════════════════
//  SYNC — tarik data dari server (terfilter unit oleh server) + smart polling
//  Baris dibaca sebagai array berindeks, urutan kolom = header database_vsm.xlsx.
// ══════════════════════════════════════════

// Tandai kapan terakhir kali ada perubahan lokal agar polling tidak menimpa data yang baru diubah.
let lastLocalWriteAt = 0;
function markLocalWrite() { lastLocalWriteAt = Date.now(); }

function getDeletedIds(sheet) {
  try { return JSON.parse(Store.get('vsm_deleted_' + sheet) || '[]'); } catch (e) { return []; }
}
function markDeleted(sheet, id) {
  const ids = getDeletedIds(sheet);
  if (!ids.includes(id)) ids.push(id);
  Store.set('vsm_deleted_' + sheet, JSON.stringify(ids));
}

/* ── pemetaan baris -> objek frontend ── */
function parseEquipList(s) {
  if (!s) return [];
  return s.split(';').map(x => {
    const p = x.trim().match(/^(.+)\((\d+)\)$/);
    return p ? { nama: p[1].trim(), qty: p[2] } : { nama: x.trim(), qty: '1' };
  });
}

function mapVisitorRow(r) {
  return {
    id: r[0], visitorType: r[1] || 'Guest', name: r[2], company: r[3], phone: r[4], identity: r[5],
    purpose: r[6], host: r[7], dept: r[8], notes: r[9], status: (r[10] || '').trim(),
    checkIn: r[11], checkOut: r[12] || null,
    area: r[14], personil: r[16], spk: r[17], startDate: r[18], endDate: r[19],
    equipList: parseEquipList(r[20]), slaStart: r[21] || null,
    plate: r[23], vehicle: r[24], cargo: r[25],
    driverApproved: r[26] === 'TRUE', approvedAt: r[27] || null, gatePassOut: r[28] || null,
    nik: r[29], jabatan: r[30], auditType: r[31], scope: r[32], suratTugas: r[33],
    createdAt: r[34] || r[11],
  };
}

function mapEquipmentRow(r) {
  return {
    id: r[0], name: r[1], serial: r[2], owner: r[3], ownerCompany: r[4],
    visitorId: r[5] || null, visitorType: r[6] || null, gatePass: r[7], condition: r[8],
    qty: r[9] || '1', status: (r[10] || '').trim(), notes: r[11],
    dateIn: r[12], dateOut: r[13] || null, source: r[14],
  };
}

function mapAbsenRow(r) {
  return {
    id: r[0], tanggal: r[1], pt: r[2], spv: r[3], visitorId: r[4] || null, source: r[5] || null,
    pekerja: r[7] ? String(r[7]).split('\n').map(s => s.trim()).filter(Boolean) : [],
    alat: r[9] ? String(r[9]).split('\n').map(s => {
      const m = s.trim().match(/^(.+?)\s*\(Qty:\s*(\d+)\)$/i);
      return m ? { nama: m[1].trim(), qty: m[2] } : { nama: s.trim(), qty: '1' };
    }).filter(a => a.nama) : [],
    user: r[10],
  };
}

function mapLogRow(r, i) {
  return { id: 'LOG-' + i, ts: r[0], action: r[1], entity: r[2], detail: r[4], user: r[5], oldVal: '', newVal: '' };
}

// Server = sumber kebenaran. Bila masih ada antrian belum terkirim, jangan buang data lokal yang lebih banyak.
function acceptServerList(serverCount, localCount) {
  return getPendingSync().length === 0 || serverCount >= localCount - 2;
}

async function refreshAll() {
  const [dV, dE, dA, dL] = await Promise.all([
    fetchGAS({ action: 'GET_SHEET_DATA', sheet: 'Visitors' }),
    fetchGAS({ action: 'GET_SHEET_DATA', sheet: 'Equipment' }),
    fetchGAS({ action: 'GET_SHEET_DATA', sheet: 'AbsenKontraktor' }),
    fetchGAS({ action: 'GET_SHEET_DATA', sheet: 'Logs', limit: 500 }),
  ]);

  const delV = getDeletedIds('Visitors'), delE = getDeletedIds('Equipment');
  const visitors  = dV.rows.map(mapVisitorRow).filter(v => v.id && !delV.includes(v.id));
  const equipment = dE.rows.map(mapEquipmentRow).filter(e => e.id && !delE.includes(e.id));
  const absen     = dA.rows.map(mapAbsenRow).filter(a => a.id);

  if (acceptServerList(visitors.length, DB.visitors().length))   DB.setVisitors(visitors);
  if (acceptServerList(equipment.length, DB.equipment().length)) DB.setEquipment(equipment);
  if (acceptServerList(absen.length, DB.absen().length))         DB.setAbsen(absen);
  DB.setAudit(dL.rows.map(mapLogRow));
}

async function snapshotFingerprints() {
  const sheets = ['Visitors', 'Equipment', 'AbsenKontraktor', 'Logs'];
  const res = await Promise.all(sheets.map(s => fetchGAS({ action: 'GET_SHEET_VERSION', sheet: s })));
  const fp = {};
  sheets.forEach((s, i) => fp[s] = res[i].fingerprint);
  return fp;
}

let lastFingerprints = {};

async function loadFromServer() {
  toast('Memuat data ' + (Auth.user.unitName || Auth.user.unit) + '...', 'info');
  try {
    lastFingerprints = await snapshotFingerprints();
    await refreshAll();
    toast('Data berhasil dimuat', 'success');
  } catch (err) {
    console.warn('Gagal load data:', err.message);
    if (Auth.token) toast('Gagal memuat data dari server: ' + err.message, 'error');
  }
  renderDashboard();
  checkAlerts();
  renderPage(currentPage);
}

let _refreshTimer = null;
function scheduleRefresh() {
  clearTimeout(_refreshTimer);
  _refreshTimer = setTimeout(async () => {
    try { lastFingerprints = await snapshotFingerprints(); await refreshAll(); renderPage(currentPage); checkAlerts(); } catch (e) {}
  }, 700);
}

async function smartPoll() {
  if (!Auth.token) return;
  if (Date.now() - lastLocalWriteAt < 3000) return;   // beri jeda singkat setelah perubahan lokal
  try {
    const fp = await snapshotFingerprints();
    if (JSON.stringify(fp) === JSON.stringify(lastFingerprints)) return;
    lastFingerprints = fp;
    await refreshAll();
    renderPage(currentPage);
    checkAlerts();
  } catch (e) {
    console.warn('Auto-refresh gagal:', e.message);
  }
}

// Stagger acak 0-4 detik agar banyak perangkat tidak menembak server bersamaan.
function startPolling() {
  setTimeout(() => { smartPoll(); setInterval(smartPoll, 10000); }, Math.floor(Math.random() * 4000));
}
