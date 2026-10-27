// ══════════════════════════════════════════
//  TABS
// ══════════════════════════════════════════
const tabFilters = {};

function switchTab(group, val, el) {
  tabFilters[group] = val;
  el.closest('.tabs').querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  if (group === 'visitors') renderVisitorTable();
  if (group === 'equipment') renderEquipmentTable();
  if (group === 'reports') {
    document.querySelectorAll('#page-reports .tab-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('report-' + val).classList.add('active');
    if (val === 'daily') renderReportDaily();
    if (val === 'weekly') renderReportWeekly();
    if (val === 'monthly') renderReportMonthly();
    if (val === 'freq') renderFreqVisitors();
  }
}
