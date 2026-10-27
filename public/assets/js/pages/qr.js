// ══════════════════════════════════════════
//  QR SYSTEM
// ══════════════════════════════════════════
function renderQR() {
  const visitors = DB.visitors();
  const sel = document.getElementById('qr-visitor-select');
  if (!sel) return;
  sel.innerHTML = '<option value="">— Pilih visitor —</option>' + visitors.map(v => `<option value="${v.id}">${v.name} (${v.company})</option>`).join('');
}

function showQR() {
  const id = document.getElementById('qr-visitor-select').value;
  if (!id) { document.getElementById('qr-visitor-name').textContent = '—'; document.getElementById('qr-visitor-id').textContent = '—'; return; }
  const v = DB.visitors().find(x => x.id === id);
  if (!v) return;
  document.getElementById('qr-visitor-name').textContent = v.name;
  document.getElementById('qr-visitor-id').textContent = v.id;
  // generate QR using simple API
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(v.id)}`;
  document.getElementById('qr-img-wrap').innerHTML = `<img src="${qrUrl}" alt="QR Code" style="width:150px;height:150px;border-radius:6px">`;
}

function qrLookup() {
  const id = document.getElementById('qr-scan-input').value.trim();
  if (!id) { toast('Masukkan Visitor ID', 'error'); return; }
  const v = DB.visitors().find(x => x.id === id);
  const el = document.getElementById('qr-result');
  if (!v) { el.innerHTML = `<div style="color:var(--red);font-size:13px"><i class="ti ti-x"></i> Visitor ID tidak ditemukan</div>`; return; }
  el.innerHTML = `
    <div class="card" style="margin-top:0">
      <div class="td-main">${v.name}</div>
      <div class="td-sub">${v.company} · ${v.host}</div>
      <div class="mt8">${statusPill(v.status)}</div>
      <div class="td-sub mono mt8">Check-in: ${fmtDateTime(v.checkIn)}</div>
    </div>
  `;
}

function qrCheckin() {
  const id = document.getElementById('qr-scan-input').value.trim();
  if (!id) { toast('Masukkan Visitor ID', 'error'); return; }
  const visitors = DB.visitors();
  const v = visitors.find(x => x.id === id);
  if (!v) { toast('Visitor tidak ditemukan', 'error'); return; }
  if (v.status === 'Inside') { toast('Visitor sudah di dalam', 'info'); return; }
  v.status = 'Inside'; v.checkIn = now();
  DB.setVisitors(visitors);
  addAudit('CHECK_IN', 'VISITOR', v.name + ' — QR scan');
  toast(v.name + ' berhasil check in via QR', 'success');
  qrLookup();
  // Kirim ke Google Sheets
  sendToGAS('CHECKIN_VISITOR', {
    ...v,
    source: 'QR_SCAN',
    user: document.getElementById('sb-uname').textContent,
  });
  sendToGAS('SCAN_QR', { refId: id, action: 'Check In' });
  sendLogToGAS('CHECK_IN', 'VISITOR', v.name + ' — via QR scan');
}

function qrCheckout() {
  const id = document.getElementById('qr-scan-input').value.trim();
  if (!id) { toast('Masukkan Visitor ID', 'error'); return; }
  const visitors = DB.visitors();
  const v = visitors.find(x => x.id === id);
  if (!v) { toast('Visitor tidak ditemukan', 'error'); return; }
  if (v.status === 'Checked Out') { toast('Visitor sudah check out', 'info'); return; }
  v.status = 'Checked Out'; v.checkOut = now();
  DB.setVisitors(visitors);
  addAudit('CHECK_OUT', 'VISITOR', v.name + ' — QR scan');
  toast(v.name + ' berhasil check out via QR', 'success');
  qrLookup();
  // Kirim ke Google Sheets
  sendToGAS('CHECKOUT_VISITOR', {
    id: id,
    checkOutTime: v.checkOut,
    durasi: fmtDuration(v.checkIn, v.checkOut),
    source: 'QR_SCAN',
    user: document.getElementById('sb-uname').textContent,
  });
  sendToGAS('SCAN_QR', { refId: id, action: 'Check Out' });
  sendLogToGAS('CHECK_OUT', 'VISITOR', v.name + ' — via QR scan');
}

function printQR() { window.print(); }
function downloadQR() {
  const id = document.getElementById('qr-visitor-select').value;
  if (!id) { toast('Pilih visitor terlebih dahulu', 'error'); return; }
  const v = DB.visitors().find(x => x.id === id);
  if (!v) return;
  const url = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(v.id)}`;
  const a = document.createElement('a'); a.href = url; a.download = 'QR-' + v.id + '.png';
  document.body.appendChild(a); a.click(); document.body.removeChild(a);
  toast('QR Code downloaded', 'success');
}
