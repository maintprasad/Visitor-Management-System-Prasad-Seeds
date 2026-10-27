// ══════════════════════════════════════════
//  DASHBOARD
// ══════════════════════════════════════════
function renderDashboard() {
  const visitors = DB.visitors();
  const equipment = DB.equipment();
  checkAlerts();

  const inside = visitors.filter(v => v.status === 'Inside' || v.status === 'Overstay').length;
  const overstay = visitors.filter(v => v.status === 'Overstay').length;
  const todayV = visitors.filter(v => isToday(v.checkIn)).length;
  const equipInside = equipment.filter(e => e.status === 'Inside').length;

  document.getElementById('stat-inside').textContent = inside;
  document.getElementById('stat-equip').textContent = equipInside;
  document.getElementById('stat-overstay').textContent = overstay;
  document.getElementById('stat-today').textContent = todayV;

  // per type stats
  const activeV = visitors.filter(v => v.status === 'Inside' || v.status === 'Overstay');
  document.getElementById('stat-guest').textContent = activeV.filter(v => v.visitorType === 'Guest').length;
  document.getElementById('stat-contractor').textContent = activeV.filter(v => v.visitorType === 'Contractor').length;
  document.getElementById('stat-driver').textContent = activeV.filter(v => v.visitorType === 'Driver').length;
  document.getElementById('stat-employee').textContent = activeV.filter(v => v.visitorType === 'Employee Test').length;
  document.getElementById('stat-auditor').textContent = activeV.filter(v => v.visitorType === 'Auditor').length;

  // update sidebar badge
  document.getElementById('badge-visitors').textContent = inside;
  document.getElementById('badge-visitors').style.display = inside > 0 ? '' : 'none';

  // chart — 7 days
  const days = [];
  for (let i=6; i>=0; i--) {
    const d = new Date(); d.setDate(d.getDate() - i);
    const dateStr = d.toDateString();
    const count = visitors.filter(v => new Date(v.checkIn).toDateString() === dateStr).length;
    days.push({ label: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][d.getDay()], count });
  }
  const maxCount = Math.max(...days.map(d => d.count), 1);
  const chartEl = document.getElementById('dash-chart-visitors');
  chartEl.innerHTML = days.map(d => `
    <div class="bar-wrap">
      <div class="bar-val">${d.count}</div>
      <div class="bar" style="height:${Math.max((d.count/maxCount)*90, 4)}px"></div>
      <div class="bar-label">${d.label}</div>
    </div>
  `).join('');

  // alerts
  const alerts = DB.alerts().filter(a => !a.resolved).slice(0,4);
  const alertsEl = document.getElementById('dash-alerts');
  if (alerts.length === 0) {
    alertsEl.innerHTML = '<div class="empty"><i class="ti ti-check"></i><p>Tidak ada alert aktif</p></div>';
  } else {
    alertsEl.innerHTML = alerts.map(a => `
      <div class="alert-row">
        <div class="alert-icon ${a.severity.toLowerCase()}"><i class="ti ti-alert-circle"></i></div>
        <div class="alert-body">
          <div class="alert-title">${a.message}</div>
          <div class="alert-meta">${a.detail} · <span class="pill pill-${a.severity.toLowerCase()}">${a.severity}</span></div>
        </div>
      </div>
    `).join('');
  }

  // active visitors table
  const active = visitors.filter(v => v.status === 'Inside' || v.status === 'Overstay' || v.status === 'Waiting Approval');
  const tbody = document.getElementById('dash-visitor-table');
  if (active.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8"><div class="empty"><i class="ti ti-users"></i><p>Tidak ada visitor aktif</p></div></td></tr>';
  } else {
    tbody.innerHTML = active.map(v => `
      <tr>
        <td><div class="td-main">${v.name}</div><div class="td-sub">${v.identity || '—'}</div></td>
        <td>${v.company}</td>
        <td>${v.host}<div class="td-sub">${v.dept}</div></td>
        <td>${v.purpose}</td>
        <td><div class="mono">${fmtTime(v.checkIn)}</div></td>
        <td><span class="timer ${durationSecs(v.checkIn, v.checkOut) > 14400 ? 'danger' : durationSecs(v.checkIn, v.checkOut) > 10800 ? 'warn' : ''}" data-checkin="${v.checkIn}" data-checkout="${v.checkOut || ''}" data-status="${v.status}">${fmtDuration(v.checkIn, v.checkOut)}</span></td>
        <td>${statusPill(v.status)}</td>
        <td>
          <div class="flex-gap8">
            <button class="btn btn-sm" onclick="openDetail('${v.id}')"><i class="ti ti-eye"></i></button>
            ${v.status !== 'Checked Out' ? `<button class="btn btn-sm btn-success" onclick="doCheckout('${v.id}')"><i class="ti ti-logout"></i> Out</button>` : `<button class="btn btn-sm btn-primary" onclick="reCheckIn('${v.id}')" title="Re-check In"><i class="ti ti-rotate-clockwise"></i></button>`}
          </div>
        </td>
      </tr>
    `).join('');
  }
}

function statusPill(status) {
  const map = { 'Inside': 'inside', 'Waiting Approval': 'waiting', 'Checked Out': 'out', 'Overstay': 'overstay', 'Pending Approval': 'pending' };
  return `<span class="pill pill-${map[status] || 'out'}">${status}</span>`;
}
