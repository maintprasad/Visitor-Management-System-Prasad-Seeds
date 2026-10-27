// ══════════════════════════════════════════
//  AUDIT TRAIL
// ══════════════════════════════════════════
function renderAudit() {
  let logs = DB.audit();
  const q = (document.getElementById('audit-search')?.value || '').toLowerCase();
  const filter = document.getElementById('audit-filter')?.value || '';
  if (q) logs = logs.filter(l => l.detail.toLowerCase().includes(q) || l.user.toLowerCase().includes(q) || l.action.toLowerCase().includes(q));
  if (filter) logs = logs.filter(l => l.action === filter);
  document.getElementById('audit-count').textContent = logs.length + ' records';
  const el = document.getElementById('audit-list');
  if (!el) return;
  if (logs.length === 0) { el.innerHTML = '<div class="empty"><i class="ti ti-history"></i><p>Tidak ada data audit</p></div>'; return; }
  el.innerHTML = logs.map(l => `
    <div class="audit-row">
      <div class="audit-time">${fmtDateTime(l.ts)}</div>
      <div class="audit-action">${l.action}</div>
      <div class="audit-user">${l.user}</div>
      <div class="audit-detail">${l.entity} — ${l.detail}</div>
    </div>
  `).join('');
}

function clearAudit() {
  if (!confirm('Hapus semua audit log? Tindakan ini tidak bisa dibatalkan.')) return;
  DB.setAudit([]);
  renderAudit();
  toast('Audit log cleared', 'info');
  sendLogToGAS('CLEAR_AUDIT', 'AUDIT', 'Semua audit log dihapus');
}
