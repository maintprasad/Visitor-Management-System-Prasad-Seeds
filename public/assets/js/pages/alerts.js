// ══════════════════════════════════════════
//  ALERTS
// ══════════════════════════════════════════
function renderAlerts() {
  const alerts = DB.alerts();
  const el = document.getElementById('alerts-list');
  if (!el) return;
  if (alerts.length === 0) {
    el.innerHTML = '<div class="empty"><i class="ti ti-bell-off"></i><p>Tidak ada alert</p></div>';
    return;
  }
  el.innerHTML = alerts.map(a => `
    <div class="alert-row" style="${a.resolved ? 'opacity:.5' : ''}">
      <div class="alert-icon ${a.severity.toLowerCase()}"><i class="ti ti-alert-circle"></i></div>
      <div class="alert-body" style="flex:1">
        <div class="alert-title">${a.message} ${a.resolved ? '<span class="pill pill-out">Resolved</span>' : ''}</div>
        <div class="alert-meta">${a.detail}</div>
        <div class="alert-meta mono">${fmtDateTime(a.ts)}</div>
      </div>
      <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end">
        <span class="pill pill-${a.severity.toLowerCase()}">${a.severity}</span>
        ${!a.resolved ? `<button class="btn btn-sm" onclick="resolveAlert('${a.id}')"><i class="ti ti-check"></i> Resolve</button>` : ''}
      </div>
    </div>
  `).join('');
}

function resolveAlert(id) {
  const alerts = DB.alerts();
  const a = alerts.find(x => x.id === id);
  if (!a) return;
  a.resolved = true;
  DB.setAlerts(alerts);
  renderAlerts();
  checkAlerts();
  toast('Alert resolved', 'success');
  addAudit('RESOLVE_ALERT', 'ALERT', a.message);
  sendLogToGAS('RESOLVE_ALERT', 'ALERT', a.message);
}
