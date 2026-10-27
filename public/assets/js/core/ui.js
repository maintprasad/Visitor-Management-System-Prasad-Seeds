// ══════════════════════════════════════════
//  MODAL HELPERS
// ══════════════════════════════════════════
function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}
// Close modal on background click
document.querySelectorAll('.modal-bg').forEach(bg => {
  bg.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });
});

// ══════════════════════════════════════════
//  TOAST
// ══════════════════════════════════════════
function toast(msg, type = 'info') {
  const icons = { success: 'ti-check', error: 'ti-x', info: 'ti-info-circle' };
  const el = document.createElement('div');
  el.className = 'toast ' + type;
  el.innerHTML = `<i class="ti ${icons[type] || 'ti-info-circle'}" style="font-size:16px"></i> ${msg}`;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(() => el.remove(), 3000);
}

// ══════════════════════════════════════════
//  CSV EXPORT
// ══════════════════════════════════════════
function downloadCSV(csv, filename) {
  const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href = url; a.download = filename;
  document.body.appendChild(a); a.click(); document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

// ══════════════════════════════════════════
//  LIVE TIMERS (tick every second)
// ══════════════════════════════════════════
setInterval(() => {
  // Hanya tick timer yang ada di halaman aktif
  const pageEl = document.getElementById('page-' + currentPage);
  if (!pageEl) return;
  pageEl.querySelectorAll('.timer[data-checkin]').forEach(el => {
    const status = el.dataset.status;
    if (status === 'Checked Out') return;
    const checkout = el.dataset.checkout;
    if (checkout) return;
    const secs = durationSecs(el.dataset.checkin);
    el.textContent = fmtDuration(el.dataset.checkin);
    el.className = 'timer ' + (secs > 14400 ? 'danger' : secs > 10800 ? 'warn' : '');
  });

  // Update realtime page stats
  if (currentPage === 'realtime') {
    const inside = DB.visitors().filter(v => v.status === 'Inside' || v.status === 'Overstay');
    if (inside.length > 0) {
      let longest = inside.reduce((a,b) => durationSecs(a.checkIn) > durationSecs(b.checkIn) ? a : b);
      const longestEl = document.getElementById('rt-longest');
      if (longestEl) longestEl.textContent = fmtDuration(longest.checkIn);
    }
  }

  // check overstay every 30s
  if (Math.floor(Date.now()/1000) % 30 === 0) checkAlerts();
}, 1000);

// ══════════════════════════════════════════
//  UPDATE TOPBAR TIME
// ══════════════════════════════════════════
function updateTime() {
  const el = document.getElementById('page-sub');
  if (currentPage === 'dashboard') {
    const now2 = new Date();
    el.textContent = now2.toLocaleDateString('id-ID', {weekday:'long', day:'2-digit', month:'long', year:'numeric'}) + ' · ' + now2.toLocaleTimeString('id-ID');
  }
}
setInterval(updateTime, 1000);
