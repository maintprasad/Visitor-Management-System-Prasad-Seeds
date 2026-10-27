// ══════════════════════════════════════════
//  EXPORT EXCEL — Multi Sheet (Guest, Contractor, Driver) — Styled
// ══════════════════════════════════════════
function normalizeStr(s) {
  return (s || '').toString().trim().toLowerCase().replace(/\s+/g, ' ').replace(/^pt\.?\s*/, '').replace(/^cv\.?\s*/, '');
}

function getContractorPersonilInfo(v) {
  const safePekerjaCount = (d) => Array.isArray(d.pekerja) ? d.pekerja.length : 0;
  const safePekerjaNames = (d) => Array.isArray(d.pekerja) ? d.pekerja : [];

  // Prioritas 0: pekerjaList langsung di object visitor (paling akurat)
  if (Array.isArray(v.pekerjaList) && v.pekerjaList.length > 0) {
    return { count: v.pekerjaList.length, names: v.pekerjaList.join(', ') };
  }

  // Prioritas 1: match langsung via visitorId
  const directMatches = DB.absen().filter(d => d && d.visitorId === v.id);
  if (directMatches.length > 0) {
    const names = [];
    let count = 0;
    directMatches.forEach(d => { count += safePekerjaCount(d); names.push(...safePekerjaNames(d)); });
    return { count, names: names.join(', ') || '—' };
  }

  // Prioritas 2: fuzzy match via nama PT (Company) atau nama Supervisi/PJ —
  // untuk absen yang diinput manual lewat menu "Absen Kontraktor" terpisah.
  const companyN = normalizeStr(v.company);
  const nameN = normalizeStr(v.name);
  const hostN = normalizeStr(v.host);

  const fuzzyMatch = (a, b) => {
    if (!a || !b) return false;
    return a === b || a.includes(b) || b.includes(a);
  };

  const matches = DB.absen().filter(d => {
    if (!d) return false;
    const ptN = normalizeStr(d.pt);
    const spvN = normalizeStr(d.spv);
    return fuzzyMatch(companyN, ptN) || fuzzyMatch(nameN, spvN) || fuzzyMatch(hostN, spvN);
  });

  if (matches.length > 0) {
    const names = [];
    let count = 0;
    matches.forEach(d => { count += safePekerjaCount(d); names.push(...safePekerjaNames(d)); });
    return { count, names: names.join(', ') || '—' };
  }

  // Fallback: belum ada data Absen Kontraktor yang cocok sama sekali — pakai
  // jumlah personil yang diisi manual saat form check-in.
  return { count: v.personil || '—', names: '—' };
}

function buildEquipSummary(v) {
  // Ambil semua equipment yang terkait visitor ini (form input + menu Equipment)
  const linked = getLinkedEquipment(v);
  const fromForm = (v.equipList || []).filter(item => {
    const nameLower = (item.nama || '').trim().toLowerCase();
    return !linked.find(e => (e.name || '').trim().toLowerCase() === nameLower);
  });

  const allNames = [];
  const insideNames = [];
  const outsideNames = [];

  linked.forEach(e => {
    const label = e.qty && e.qty !== '1' ? `${e.name} (${e.qty})` : e.name;
    allNames.push(label);
    if (e.status === 'Outside') outsideNames.push(label);
    else insideNames.push(label);
  });
  fromForm.forEach(item => {
    const label = item.qty && item.qty !== '1' ? `${item.nama} (${item.qty})` : item.nama;
    allNames.push(label);
    insideNames.push(label); // belum tercatat di Equipment = anggap masih inside/belum checkout
  });

  return {
    all: allNames.join(', ') || '—',
    inside: insideNames.join(', ') || '—',
    outside: outsideNames.join(', ') || '—',
  };
}

// Warna tema per sheet (ARGB, harus 8 digit: AA + RRGGBB)
const EXCEL_THEME = {
  Guest:      { header: 'FF2563EB', headerText: 'FFFFFFFF', accent: 'FFDBEAFE' }, // biru
  Contractor: { header: 'FFD97706', headerText: 'FFFFFFFF', accent: 'FFFEF3C7' }, // amber
  Driver:     { header: 'FF16A34A', headerText: 'FFFFFFFF', accent: 'FFDCFCE7' }, // hijau
};

const STATUS_COLOR = {
  'Inside':           'FFDCFCE7',
  'Overstay':         'FFFEE2E2',
  'Waiting Approval': 'FFFEF3C7',
  'Checked Out':      'FFF3F4F6',
  'Pending Approval': 'FFDBEAFE',
};

function styleHeaderRow(row, theme) {
  row.eachCell((cell) => {
    cell.font = { bold: true, color: { argb: theme.headerText }, size: 11 };
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: theme.header } };
    cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
    cell.border = {
      top: { style: 'thin', color: { argb: 'FFFFFFFF' } },
      bottom: { style: 'thin', color: { argb: 'FFFFFFFF' } },
      left: { style: 'thin', color: { argb: 'FFFFFFFF' } },
      right: { style: 'thin', color: { argb: 'FFFFFFFF' } },
    };
  });
  row.height = 28;
}

function styleDataRow(row, isEven, statusValue, rowHeight) {
  row.eachCell((cell) => {
    cell.alignment = { vertical: 'top', horizontal: 'left', wrapText: true };
    cell.border = {
      top: { style: 'thin', color: { argb: 'FFE5E7EB' } },
      bottom: { style: 'thin', color: { argb: 'FFE5E7EB' } },
      left: { style: 'thin', color: { argb: 'FFE5E7EB' } },
      right: { style: 'thin', color: { argb: 'FFE5E7EB' } },
    };
    if (isEven) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF9FAFB' } };
  });
  // Warnai cell Status sesuai nilainya (selalu kolom terakhir)
  const statusCell = row.getCell(row.cellCount);
  const color = STATUS_COLOR[statusValue];
  if (color) {
    statusCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: color } };
    statusCell.font = { bold: true, size: 10 };
    statusCell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
  }
  row.height = rowHeight;
}

// Hitung perkiraan tinggi row berdasarkan teks terpanjang di tiap kolom.
// Excel ~ 1 baris teks butuh tinggi sekitar 15pt, dan tiap kolom punya lebar (wch)
// yang menampung sekitar (width - 2) karakter per baris sebelum wrap.
function estimateRowHeight(rowData, columns) {
  const LINE_HEIGHT = 15;     // tinggi per baris teks (pt)
  const MIN_HEIGHT = 22;
  const MAX_HEIGHT = 250;     // batas atas biar gak kebablasan kalau ada teks super panjang
  let maxLines = 1;

  columns.forEach(col => {
    const raw = rowData[col.key];
    if (raw === undefined || raw === null) return;
    const text = String(raw);
    if (!text || text === '—') return;

    const usableWidth = Math.max(col.width - 2, 6); // karakter efektif per baris
    // Hitung baris dari newline manual + wrap otomatis per segmen
    const segments = text.split('\n');
    let lines = 0;
    segments.forEach(seg => {
      lines += Math.max(1, Math.ceil(seg.length / usableWidth));
    });
    if (lines > maxLines) maxLines = lines;
  });

  return Math.min(Math.max(maxLines * LINE_HEIGHT, MIN_HEIGHT), MAX_HEIGHT);
}

function addStyledSheet(wb, sheetName, columns, rows) {
  const theme = EXCEL_THEME[sheetName] || EXCEL_THEME.Guest;
  const ws = wb.addWorksheet(sheetName, {
    views: [{ state: 'frozen', ySplit: 1 }],
  });

  ws.columns = columns.map(c => ({ header: c.header, key: c.key, width: c.width }));

  const headerRow = ws.getRow(1);
  styleHeaderRow(headerRow, theme);

  if (rows.length === 0) {
    const emptyRow = ws.addRow({ [columns[0].key]: 'Tidak ada data' });
    ws.mergeCells(2, 1, 2, columns.length);
    emptyRow.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    emptyRow.getCell(1).font = { italic: true, color: { argb: 'FF9CA3AF' } };
    emptyRow.height = 26;
  } else {
    rows.forEach((r, i) => {
      const row = ws.addRow(r);
      const rowHeight = estimateRowHeight(r, columns);
      styleDataRow(row, i % 2 === 1, r.Status, rowHeight);
    });
  }

  // Auto-filter di header
  ws.autoFilter = { from: { row: 1, column: 1 }, to: { row: 1, column: columns.length } };

  return ws;
}

async function exportExcelAllTypes() {
  if (typeof ExcelJS === 'undefined') { toast('Library Excel gagal dimuat, cek koneksi internet', 'error'); return; }
  toast('Membuat file Excel...', 'info');

  try {
    const visitors = DB.visitors();
    const wb = new ExcelJS.Workbook();
    wb.creator = 'Visitor System Management';
    wb.created = new Date();

    // ── SHEET: Guest ──
    const guestRows = visitors.filter(v => v.visitorType === 'Guest').map(v => ({
      ID: v.id,
      Nama: v.name,
      Company: v.company || '—',
      Host: v.host || '—',
      CheckIn: fmtDateTime(v.checkIn),
      CheckOut: v.checkOut ? fmtDateTime(v.checkOut) : '—',
      Duration: fmtDuration(v.checkIn, v.checkOut),
      Status: v.status,
    }));
    addStyledSheet(wb, 'Guest', [
      { header: 'ID', key: 'ID', width: 16 },
      { header: 'Nama Visitor', key: 'Nama', width: 24 },
      { header: 'Company', key: 'Company', width: 22 },
      { header: 'Host', key: 'Host', width: 18 },
      { header: 'Check In', key: 'CheckIn', width: 20 },
      { header: 'Check Out', key: 'CheckOut', width: 20 },
      { header: 'Duration', key: 'Duration', width: 13 },
      { header: 'Status', key: 'Status', width: 17 },
    ], guestRows);

    // ── SHEET: Contractor ──
    const contractorRows = visitors.filter(v => v.visitorType === 'Contractor').map(v => {
      const equip = buildEquipSummary(v);
      const personilInfo = getContractorPersonilInfo(v);
      const slaBase = v.slaStart || v.checkIn;
      return {
        ID: v.id,
        Nama: v.name,
        Company: v.company || '—',
        Host: v.host || '—',
        JumlahPersonil: personilInfo.count,
        NamaPekerja: personilInfo.names,
        Equipment: equip.all,
        EquipInside: equip.inside,
        EquipOutside: equip.outside,
        CheckIn: fmtDateTime(v.checkIn),
        CheckOut: v.checkOut ? fmtDateTime(v.checkOut) : '—',
        Duration: fmtDuration(slaBase, v.checkOut),
        Status: v.status,
      };
    });
    addStyledSheet(wb, 'Contractor', [
      { header: 'ID', key: 'ID', width: 16 },
      { header: 'Nama Contractor', key: 'Nama', width: 24 },
      { header: 'Company', key: 'Company', width: 22 },
      { header: 'Host', key: 'Host', width: 18 },
      { header: 'Jumlah Personil', key: 'JumlahPersonil', width: 15 },
      { header: 'Nama Pekerja\n(dari Absen Kontraktor)', key: 'NamaPekerja', width: 32 },
      { header: 'Equipment / Peralatan', key: 'Equipment', width: 32 },
      { header: 'Peralatan Inside\n(Belum Checkout)', key: 'EquipInside', width: 28 },
      { header: 'Peralatan Outside\n(Sudah Checkout)', key: 'EquipOutside', width: 28 },
      { header: 'Check In', key: 'CheckIn', width: 20 },
      { header: 'Check Out', key: 'CheckOut', width: 20 },
      { header: 'Duration', key: 'Duration', width: 13 },
      { header: 'Status', key: 'Status', width: 17 },
    ], contractorRows);

    // ── SHEET: Driver ──
    const driverRows = visitors.filter(v => v.visitorType === 'Driver').map(v => ({
      ID: v.id,
      Nama: v.name,
      Company: v.company || '—',
      Plat: v.plate || '—',
      Vehicle: v.vehicle || '—',
      Notes: v.notes || '—',
      CheckIn: fmtDateTime(v.checkIn),
      CheckOut: v.checkOut ? fmtDateTime(v.checkOut) : '—',
      Duration: fmtDuration(v.checkIn, v.checkOut),
      Status: v.status,
    }));
    addStyledSheet(wb, 'Driver', [
      { header: 'ID', key: 'ID', width: 16 },
      { header: 'Nama Driver', key: 'Nama', width: 24 },
      { header: 'Nama Transporter', key: 'Company', width: 24 },
      { header: 'No Polisi Kendaraan', key: 'Plat', width: 18 },
      { header: 'Jenis Kendaraan', key: 'Vehicle', width: 17 },
      { header: 'Catatan', key: 'Notes', width: 30 },
      { header: 'Check In', key: 'CheckIn', width: 20 },
      { header: 'Check Out', key: 'CheckOut', width: 20 },
      { header: 'Duration', key: 'Duration', width: 13 },
      { header: 'Status', key: 'Status', width: 17 },
    ], driverRows);

    const filename = 'visitor-report-' + new Date().toISOString().slice(0,10) + '.xlsx';
    const buffer = await wb.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/octet-stream' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = filename;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);

    addAudit('EXPORT', 'REPORT', `Excel report (styled) — Guest:${guestRows.length}, Contractor:${contractorRows.length}, Driver:${driverRows.length}`);
    sendLogToGAS('EXPORT', 'REPORT', `Excel report (3 sheet, styled) — Guest:${guestRows.length}, Contractor:${contractorRows.length}, Driver:${driverRows.length}`);
    toast('Excel report berhasil di-export (3 sheet, dengan styling)', 'success');

  } catch (err) {
    console.error('Export Excel error:', err);
    toast('Gagal export Excel: ' + err.message, 'error');
  }
}
