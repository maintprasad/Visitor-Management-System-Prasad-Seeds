// ══════════════════════════════════════════
//  ACTIONS
// ══════════════════════════════════════════
function submitCheckinTyped(type) {
  const prefix = { 'Guest':'g', 'Contractor':'con', 'Driver':'drv', 'Employee Test':'emp', 'Auditor':'aud' }[type];
  const modalId = { 'Guest':'modal-checkin-guest', 'Contractor':'modal-checkin-contractor', 'Driver':'modal-checkin-driver', 'Employee Test':'modal-checkin-employee', 'Auditor':'modal-checkin-auditor' }[type];

  const get = (id) => document.getElementById(prefix+'-'+id)?.value?.trim() || '';

  let namaList = [];
  if (type === 'Guest') {
    namaList = [...document.querySelectorAll('#g-nama-list .g-nama-item')].map(el => el.value.trim()).filter(Boolean);
    if (namaList.length === 0) { toast('Tambahkan minimal 1 nama pengunjung', 'error'); return; }
  } else if (type === 'Auditor') {
    namaList = [...document.querySelectorAll('#aud-nama-list .aud-nama-item')].map(el => el.value.trim()).filter(Boolean);
    if (namaList.length === 0) { toast('Tambahkan minimal 1 nama pengunjung', 'error'); return; }
  }

  const name = (type === 'Guest' || type === 'Auditor') ? namaList.join(', ') : get('name');
  const isDriver = type === 'Driver';
  // Driver hanya 4 isian (nama, no. polisi, jenis kendaraan, catatan) -> sisanya diisi otomatis.
  const host = isDriver ? 'Security' : get('host');
  const purpose = isDriver ? 'Kunjungan Driver' : get('purpose');

  if (!name || !host || !purpose) { toast('Lengkapi field yang wajib diisi (*)', 'error'); return; }
  if (type === 'Contractor') {
    const area = document.getElementById('con-area')?.value;
    if (!area) { toast('Pilih Area Kerja', 'error'); return; }
  }

  if (isDriver && (!get('company') || !get('plate') || !document.getElementById('drv-vehicle').value)) {
    toast('Lengkapi nama driver, nama transporter, nomor polisi, dan jenis kendaraan', 'error'); return;
  }

  const base = {
    id: genId('VIS'),
    visitorType: type,
    name,
    host,
    purpose,
    status: isDriver ? 'Inside' : (get('status') || 'Inside'),
    checkIn: now(), checkOut: null, createdAt: now(),
    user: document.getElementById('sb-uname').textContent,
  };

  // field per tipe
  if (type === 'Guest') {
    Object.assign(base, { company: get('company'), phone: get('phone'), identity: get('identity'), dept: document.getElementById('g-dept').value, notes: get('notes'), namaList });
  } else if (type === 'Contractor') {
    const equipRows = [];
    document.querySelectorAll('#con-equipment-list .equip-row').forEach(row => {
      const nama = row.querySelector('.eq-nama')?.value?.trim();
      const qty  = row.querySelector('.eq-qty')?.value?.trim();
      if (nama) equipRows.push({ nama, qty: qty || '1' });
    });

    // Ambil nama pekerja dari form langsung
    const pekerjaNames = [...document.querySelectorAll('#con-pekerja-list .con-pekerja-nama')]
      .map(el => el.value.trim()).filter(Boolean);

    if (pekerjaNames.length === 0) { toast('Tambahkan minimal 1 nama pekerja', 'error'); return; }

    const conStatus = document.getElementById('con-status').value;
    Object.assign(base, {
      company:    get('company'),
      phone:      get('phone'),
      identity:   get('identity'),
      dept:       document.getElementById('con-dept').value,
      notes:      get('notes'),
      spk:        get('spk'),
      startDate:  get('start'),
      endDate:    get('end'),
      area:       document.getElementById('con-area').value,
      personil:   String(pekerjaNames.length),
      pekerjaList: pekerjaNames,
      equipList:  equipRows,
      slaStart:   conStatus === 'Inside' ? base.checkIn : null,
    });
    base.status = conStatus;
    if (!base.area) { toast('Pilih Area Kerja', 'error'); return; }
  } else if (type === 'Driver') {
    Object.assign(base, { company: get('company'), plate: get('plate').toUpperCase(), vehicle: document.getElementById('drv-vehicle').value });
  } else if (type === 'Employee Test') {
    Object.assign(base, { company: '—', nik: get('nik'), dept: document.getElementById('emp-dept').value, jabatan: get('jabatan'), notes: get('notes') });
  } else if (type === 'Auditor') {
    Object.assign(base, { company: get('company'), phone: get('phone'), identity: get('identity'), auditType: document.getElementById('aud-audittype').value, scope: get('scope'), dept: document.getElementById('aud-dept').value, suratTugas: get('surat'), notes: get('notes'), namaList });
  }

  const visitors = DB.visitors();
  visitors.unshift(base);
  DB.setVisitors(visitors);

  // Auto-register equipment ke menu Equipment untuk SEMUA tipe visitor
  // Pastikan base.equipList sudah final sebelum register
  const equipToRegister = (base.equipList || []).filter(item => item && item.nama && item.nama.trim());
  if (equipToRegister.length > 0) {
    const equipment = DB.equipment();
    equipToRegister.forEach(item => {
      equipment.unshift({
        id: genId('EQ'),
        name: item.nama,
        serial: '',
        owner: base.name,
        ownerCompany: base.company || '',
        visitorId: base.id,
        visitorType: type,
        qty: item.qty || '1',
        gatePass: genGatePass(),
        condition: 'Good / Baik',
        status: 'Inside',
        notes: `Auto-generated dari check-in ${type}`,
        dateIn: base.checkIn,
        dateOut: null,
        source: type.toUpperCase().replace(' ','_') + '_CHECKIN',
      });
    });
    DB.setEquipment(equipment);
  }

  // Auto-create Absen Kontraktor dari pekerjaList yang diinput langsung di form
  if (type === 'Contractor' && (base.pekerjaList || []).length > 0) {
    const absenAlat = (base.equipList || [])
      .filter(item => item && item.nama && item.nama.trim())
      .map(item => ({ nama: item.nama, qty: item.qty || '1' }));

    const absenData = {
      id: genId('ABK'),
      pt: base.company || '',
      spv: base.host || '',
      pekerja: base.pekerjaList,
      alat: absenAlat,
      tanggal: base.checkIn,
      user: base.user,
      visitorId: base.id,
      source: 'AUTO_FROM_CHECKIN',
    };
    const absenList = DB.absen();
    absenList.unshift(absenData);
    DB.setAbsen(absenList);

    addAudit('CREATE', 'ABSEN_KONTRAKTOR', `[Auto dari Check-in] ${absenData.pt} — ${absenData.spv} — ${base.pekerjaList.length} pekerja`);
    sendToGAS('ADD_ABSEN_KONTRAKTOR', absenData);
    sendLogToGAS('CREATE', 'ABSEN_KONTRAKTOR', `[Auto] ${absenData.pt} — ${base.pekerjaList.length} pekerja`);
  }

  markLocalWrite();
  addAudit('CHECK_IN', 'VISITOR', `[${type}] ${name} — ${purpose}`);
  closeModal(modalId);
  toast(`${name} (${type}) berhasil check in!`, 'success');

  // reset field
  document.querySelectorAll(`#${modalId} input, #${modalId} textarea`).forEach(el => el.value = '');
  document.querySelectorAll(`#${modalId} select`).forEach(el => el.selectedIndex = 0);
  // reset dynamic lists
  const pekerjaListEl = document.getElementById('con-pekerja-list');
  if (pekerjaListEl) pekerjaListEl.innerHTML = '';
  const equipListEl = document.getElementById('con-equipment-list');
  if (equipListEl) equipListEl.innerHTML = '';
  const gNamaListEl = document.getElementById('g-nama-list');
  if (gNamaListEl) gNamaListEl.innerHTML = '';
  const audNamaListEl = document.getElementById('aud-nama-list');
  if (audNamaListEl) audNamaListEl.innerHTML = '';

  checkAlerts();
  renderPage(currentPage);
  sendToGAS('CHECKIN_VISITOR', base);
  sendLogToGAS('CHECK_IN', 'VISITOR', `[${type}] ${name} — ${purpose}`);
}


async function doCheckout(id) {
  const visitors = DB.visitors();
  const v = visitors.find(x => x.id === id);

  // Jika contractor dan punya equipList, tampilkan popup checklist
  if (v && v.visitorType === 'Contractor' && v.equipList && v.equipList.length > 0) {
    openContractorCheckoutModal(id);
    return;
  }

  if (!confirm('Konfirmasi check out visitor ini?')) return;
  if (!v) return;
  v.status = 'Checked Out';
  v.checkOut = now();
  DB.setVisitors(visitors);
  addAudit('CHECK_OUT', 'VISITOR', `${v.name} — durasi: ${fmtDuration(v.checkIn, v.checkOut)}`);
  toast(v.name + ' berhasil check out', 'success');
  checkAlerts();
  renderPage(currentPage);
 
  // Kirim ke Google Sheets
  sendToGAS('CHECKOUT_VISITOR', {
    id: id,
    checkOutTime: v.checkOut,
    durasi: fmtDuration(v.checkIn, v.checkOut),
    user: document.getElementById('sb-uname').textContent,
  });
  sendLogToGAS('CHECK_OUT', 'VISITOR', `${v.name} — ${fmtDuration(v.checkIn, v.checkOut)}`);
}


function approveVisitor(id) {
  const visitors = DB.visitors();
  const v = visitors.find(x => x.id === id);
  if (!v) return;
  v.status = 'Inside';
  // SLA Contractor mulai saat approval
  if (v.visitorType === 'Contractor' && !v.slaStart) {
    v.slaStart = now();
  }
  DB.setVisitors(visitors);
  addAudit('UPDATE', 'VISITOR', v.name + ' — status diubah ke Inside');
  toast(v.name + ' disetujui masuk', 'success');
  renderPage(currentPage);
  // Kirim ke Google Sheets
  sendToGAS('UPDATE_STATUS', {
    sheet: 'Visitors',
    id: id,
    status: 'Inside',
    user: document.getElementById('sb-uname').textContent,
  });
  sendLogToGAS('APPROVE', 'VISITOR', v.name + ' — disetujui masuk');
}

function deleteVisitor(id) {
  if (!confirm('Hapus data visitor ini?')) return;
  const visitors = DB.visitors();
  const v = visitors.find(x => x.id === id);
  DB.setVisitors(visitors.filter(x => x.id !== id));
  markDeleted('Visitors', id);
  addAudit('DELETE', 'VISITOR', (v?.name || id) + ' — data dihapus');
  toast('Data visitor dihapus', 'info');
  renderPage(currentPage);
  // Kirim ke Google Sheets
  sendToGAS('DELETE_ROW', {
    sheet: 'Visitors',
    id: id,
    user: document.getElementById('sb-uname').textContent,
  });
}
