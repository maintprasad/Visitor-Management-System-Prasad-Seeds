// ══════════════════════════════════════════
//  REPORTS
// ══════════════════════════════════════════
function initReports() {
  const dateEl = document.getElementById('report-date');
  if (dateEl && !dateEl.value) dateEl.value = new Date().toISOString().slice(0,10);
  const monthEl = document.getElementById('report-month');
  if (monthEl) {
    const currentMonth = new Date().toISOString().slice(0,7);
    // Refresh setiap buka Reports supaya bulan selalu up-to-date
    const firstOpt = monthEl.children[0];
    const needsRefresh = !firstOpt || firstOpt.value !== currentMonth;
    if (needsRefresh) {
      monthEl.innerHTML = '';
      for (let i=0; i<6; i++) {
        const d = new Date(); d.setMonth(d.getMonth() - i);
        const val = d.toISOString().slice(0,7);
        const opt = document.createElement('option'); opt.value = val;
        opt.textContent = d.toLocaleDateString('id-ID', {month:'long', year:'numeric'});
        monthEl.appendChild(opt);
      }
    }
  }
}

function renderReportDaily() {
  const dateEl = document.getElementById('report-date');
  if (!dateEl) return;
  const d = new Date(dateEl.value);
  const visitors = DB.visitors().filter(v => new Date(v.checkIn).toDateString() === d.toDateString());
  const tbody = document.getElementById('report-daily-table');
  if (!tbody) return;
  if (visitors.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9"><div class="empty"><i class="ti ti-calendar"></i><p>Tidak ada data untuk tanggal ini</p></div></td></tr>';
    return;
  }
  tbody.innerHTML = visitors.map((v,i) => `
    <tr>
      <td>${i+1}</td>
      <td>${v.name}</td>
      <td>${v.company}</td>
      <td>${v.host}</td>
      <td>${v.purpose}</td>
      <td class="mono">${fmtTime(v.checkIn)}</td>
      <td class="mono">${v.checkOut ? fmtTime(v.checkOut) : '—'}</td>
      <td class="mono">${fmtDuration(v.checkIn, v.checkOut)}</td>
      <td>${statusPill(v.status)}</td>
    </tr>
  `).join('');
}

function renderReportWeekly() {
  const days = [];
  for (let i=6; i>=0; i--) {
    const d = new Date(); d.setDate(d.getDate() - i);
    const ds = d.toDateString();
    const all = DB.visitors().filter(v => new Date(v.checkIn).toDateString() === ds);
    days.push({ label: d.toLocaleDateString('id-ID', {weekday:'long', day:'2-digit', month:'short'}), total: all.length, out: all.filter(v=>v.status==='Checked Out').length, inside: all.filter(v=>v.status==='Inside'||v.status==='Overstay').length });
  }
  const max = Math.max(...days.map(d=>d.total), 1);
  const chart = document.getElementById('weekly-chart');
  if (chart) chart.innerHTML = days.map(d => `<div class="bar-wrap"><div class="bar-val">${d.total}</div><div class="bar" style="height:${Math.max((d.total/max)*90,4)}px"></div><div class="bar-label">${d.label.split(',')[0]}</div></div>`).join('');
  const tbody = document.getElementById('weekly-table');
  if (!tbody) return;
  tbody.innerHTML = days.map(d => `<tr><td>${d.label}</td><td>${d.total}</td><td>${d.out}</td><td>${d.inside}</td></tr>`).join('');
}

function renderReportMonthly() {
  const monthEl = document.getElementById('report-month');
  if (!monthEl || !monthEl.value) return;
  const [year, month] = monthEl.value.split('-').map(Number);
  const daysInMonth = new Date(year, month, 0).getDate();
  const rows = [];
  for (let i=1; i<=daysInMonth; i++) {
    const d = new Date(year, month-1, i).toDateString();
    const all = DB.visitors().filter(v => new Date(v.checkIn).toDateString() === d);
    if (all.length > 0) rows.push({ date: `${String(i).padStart(2,'0')}/${String(month).padStart(2,'0')}/${year}`, total: all.length, out: all.filter(v=>v.status==='Checked Out').length, inside: all.filter(v=>v.status==='Inside'||v.status==='Overstay').length, overstay: all.filter(v=>v.status==='Overstay').length });
  }
  const tbody = document.getElementById('monthly-table');
  if (!tbody) return;
  if (rows.length === 0) { tbody.innerHTML = '<tr><td colspan="5"><div class="empty"><p>Tidak ada data untuk bulan ini</p></div></td></tr>'; return; }
  tbody.innerHTML = rows.map(r => `<tr><td class="mono">${r.date}</td><td>${r.total}</td><td>${r.out}</td><td>${r.inside}</td><td>${r.overstay > 0 ? `<span class="pill pill-overstay">${r.overstay}</span>` : '0'}</td></tr>`).join('');
}

function renderFreqVisitors() {
  const visitors = DB.visitors();
  const freq = {};
  visitors.forEach(v => {
    const key = v.name + '___' + v.company;
    if (!freq[key]) freq[key] = { name: v.name, company: v.company, count: 0, last: null };
    freq[key].count++;
    if (!freq[key].last || new Date(v.checkIn) > new Date(freq[key].last)) freq[key].last = v.checkIn;
  });
  const sorted = Object.values(freq).sort((a,b) => b.count - a.count).slice(0,10);
  const tbody = document.getElementById('freq-table');
  if (!tbody) return;
  if (sorted.length === 0) { tbody.innerHTML = '<tr><td colspan="5"><div class="empty"><p>Belum ada data</p></div></td></tr>'; return; }
  tbody.innerHTML = sorted.map((r,i) => `<tr><td>${i+1}</td><td>${r.name}</td><td>${r.company}</td><td><strong>${r.count}</strong> kunjungan</td><td class="mono">${fmtDate(r.last)}</td></tr>`).join('');
}

function exportReport(type) {
  const dateEl = document.getElementById('report-date');
  const d = new Date(dateEl?.value || new Date());
  const visitors = DB.visitors().filter(v => new Date(v.checkIn).toDateString() === d.toDateString());
  let csv = 'No,Nama,Perusahaan,Host,Tujuan,Check-in,Check-out,Durasi,Status\n';
  visitors.forEach((v,i) => {
    csv += `${i+1},"${v.name}","${v.company}","${v.host}","${v.purpose}","${fmtDateTime(v.checkIn)}","${v.checkOut ? fmtDateTime(v.checkOut) : ''}","${fmtDuration(v.checkIn, v.checkOut)}","${v.status}"\n`;
  });
  downloadCSV(csv, 'visitor-report-' + d.toISOString().slice(0,10) + '.csv');
  addAudit('EXPORT', 'REPORT', 'Daily report ' + d.toISOString().slice(0,10));
  toast('Report exported', 'success');
}
