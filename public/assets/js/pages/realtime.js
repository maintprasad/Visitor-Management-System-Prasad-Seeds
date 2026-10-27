// ══════════════════════════════════════════
//  REALTIME
// ══════════════════════════════════════════
function renderRealtime() {
  const visitors = DB.visitors().filter(v => v.status === 'Inside' || v.status === 'Overstay');
  const equipment = DB.equipment().filter(e => e.status === 'Inside');

  document.getElementById('rt-inside').textContent = visitors.length;
  document.getElementById('rt-equip').textContent = equipment.length;
  document.getElementById('rt-overstay').textContent = visitors.filter(v => v.status === 'Overstay').length;

  // longest
  let longest = null;
  visitors.forEach(v => {
    if (!longest || durationSecs(v.checkIn) > durationSecs(longest.checkIn)) longest = v;
  });
  document.getElementById('rt-longest').textContent = longest ? fmtDuration(longest.checkIn) : '00:00:00';
  document.getElementById('rt-longest-name').textContent = longest ? longest.name : '—';

  const tbody = document.getElementById('rt-table');
  if (!tbody) return;
  if (visitors.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7"><div class="empty"><i class="ti ti-users"></i><p>Tidak ada visitor di dalam pabrik</p></div></td></tr>';
    return;
  }
  tbody.innerHTML = visitors.map(v => `
    <tr>
      <td><div class="td-main">${v.name}</div><div class="td-sub">${v.identity || ''}</div></td>
      <td>${v.company}</td>
      <td>${v.host}<div class="td-sub">${v.dept}</div></td>
      <td class="mono">${fmtTime(v.checkIn)}</td>
      <td><span class="timer ${durationSecs(v.checkIn) > 14400 ? 'danger' : durationSecs(v.checkIn) > 10800 ? 'warn' : ''}" data-checkin="${v.checkIn}" data-checkout="" data-status="${v.status}">${fmtDuration(v.checkIn)}</span></td>
      <td>${statusPill(v.status)}</td>
      <td><button class="btn btn-sm btn-success" onclick="doCheckout('${v.id}')"><i class="ti ti-logout"></i> Check Out</button></td>
    </tr>
  `).join('');
}
