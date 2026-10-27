// ══════════════════════════════════════════
//  ALERTS ENGINE
// ══════════════════════════════════════════
function checkAlerts() {
  const visitors = DB.visitors();
  const equipment = DB.equipment();
  const existAlerts = DB.alerts();
  const newAlerts = [];
  const visitorIds = new Set(visitors.map(v => v.id));

  visitors.filter(v => v.status === 'Inside' || v.status === 'Overstay').forEach(v => {
    const secs = durationSecs(v.checkIn);
    if (secs > 4 * 3600) {
      v.status = 'Overstay';
      const exists = existAlerts.find(a => a.ref === v.id && a.type === 'OVERSTAY');
      if (!exists) {
        newAlerts.push({ id: genId('ALT'), type: 'OVERSTAY', severity: 'High', ref: v.id, message: `Visitor overstay: ${v.name}`, detail: `${v.company} — sudah ${fmtDuration(v.checkIn)} di dalam pabrik`, ts: now(), resolved: false });
      }
    }
  });
  DB.setVisitors(visitors);

  // Hapus alert yang visitornya sudah tidak ada
  const filteredExisting = existAlerts.filter(a => {
    if (a.type === 'OVERSTAY' && a.ref && !visitorIds.has(a.ref)) return false;
    return true;
  });

  // Keep old + new unique
  const merged = [...filteredExisting];
  newAlerts.forEach(a => {
    if (!merged.find(x => x.ref === a.ref && x.type === a.type)) merged.unshift(a);
  });
  DB.setAlerts(merged.slice(0, 100));

  // Update badge
  const unresolvedCount = merged.filter(a => !a.resolved).length;
  document.getElementById('badge-alerts').textContent = unresolvedCount;
  document.getElementById('badge-alerts').style.display = unresolvedCount > 0 ? '' : 'none';
}
