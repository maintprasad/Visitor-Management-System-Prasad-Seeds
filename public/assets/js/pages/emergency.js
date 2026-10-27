// ══════════════════════════════════════════
//  EMERGENCY
// ══════════════════════════════════════════
function renderEmergency() {
  const visitors = DB.visitors().filter(v => v.status === 'Inside' || v.status === 'Overstay' || v.status === 'Waiting Approval');
  document.getElementById('emergency-count').textContent = visitors.length + ' visitor di dalam';
  const tbody = document.getElementById('emergency-table');
  if (!tbody) return;
  if (visitors.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7"><div class="empty"><i class="ti ti-check"></i><p>Pabrik kosong — semua visitor sudah keluar</p></div></td></tr>';
    return;
  }
  tbody.innerHTML = visitors.map((v,i) => `
    <tr>
      <td>${i+1}</td>
      <td><div class="td-main">${v.name}</div><div class="td-sub">${v.company}</div></td>
      <td>${v.company}</td>
      <td>${v.host}<div class="td-sub">${v.dept}</div></td>
      <td class="mono">${fmtDateTime(v.checkIn)}</td>
      <td><span class="timer ${durationSecs(v.checkIn) > 14400 ? 'danger' : ''}" data-checkin="${v.checkIn}" data-checkout="" data-status="${v.status}">${fmtDuration(v.checkIn)}</span></td>
      <td>${statusPill(v.status)}</td>
    </tr>
  `).join('');
}

function exportEmergency() {
  const visitors = DB.visitors().filter(v => v.status === 'Inside' || v.status === 'Overstay' || v.status === 'Waiting Approval');
  let csv = 'No,Nama,Perusahaan,Host,Dept,Check-in,Durasi,Status\n';
  visitors.forEach((v,i) => {
    csv += `${i+1},"${v.name}","${v.company}","${v.host}","${v.dept}","${fmtDateTime(v.checkIn)}","${fmtDuration(v.checkIn)}","${v.status}"\n`;
  });
  downloadCSV(csv, 'emergency-muster-' + new Date().toISOString().slice(0,10) + '.csv');
  addAudit('EXPORT', 'EMERGENCY', 'Emergency muster report exported');
  sendLogToGAS('EXPORT', 'EMERGENCY', 'Emergency muster report exported — ' + visitors.length + ' visitor');
  toast('Emergency muster report downloaded', 'success');
}
