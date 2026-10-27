// ══════════════════════════════════════════
//  VISITOR TABLE
// ══════════════════════════════════════════
function typePill(type) {
  const map = {
    'Guest': ['pill-pending', 'ti-user'],
    'Contractor': ['pill-waiting', 'ti-hard-hat'],
    'Driver': ['pill-good', 'ti-truck'],
    'Employee Test': ['pill-out', 'ti-id-badge'],
    'Auditor': ['pill-overstay', 'ti-clipboard-check'],
  };
  const [cls, icon] = map[type] || ['pill-out', 'ti-user'];
  return `<span class="pill ${cls}"><i class="ti ${icon}" style="font-size:10px"></i> ${type || '—'}</span>`;
}

function switchVisitorType(type, el) {
  currentVisitorType = type;
  document.querySelectorAll('#visitor-type-tabs .type-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  renderVisitorTable();
}

function updateTypeCounts() {
  const visitors = DB.visitors();
  const types = ['guest','contractor','driver','employee','auditor'];
  const typeNames = ['Guest','Contractor','Driver','Employee Test','Auditor'];
  document.getElementById('tc-all').textContent = visitors.length;
  types.forEach((t,i) => {
    const el = document.getElementById('tc-'+t);
    if (el) el.textContent = visitors.filter(v => v.visitorType === typeNames[i]).length;
  });
}

const VISITOR_HEAD_DEFAULT = '<tr><th>#</th><th>Visitor</th><th>Tipe</th><th>Company</th><th>Host / PIC</th><th>Check-in</th><th>Duration</th><th>Status</th><th>Actions</th></tr>';
// Menu Driver: kolom sesuai isian check-in (tanpa Company & Host / PIC).
const VISITOR_HEAD_DRIVER = '<tr><th>#</th><th>Driver</th><th>Tipe</th><th>Transporter</th><th>No. Polisi</th><th>Jenis Kendaraan</th><th>Check-in</th><th>Duration</th><th>Status</th><th>Actions</th></tr>';

function renderVisitorTable() {
  let visitors = DB.visitors();
  const q = (document.getElementById('visitor-search')?.value || '').toLowerCase();
  const statusFilter = document.getElementById('visitor-filter-status')?.value || '';
  const tabFilter = tabFilters['visitors'] || 'all';

  if (currentVisitorType !== 'all') visitors = visitors.filter(v => v.visitorType === currentVisitorType);
  if (q) visitors = visitors.filter(v => (v.name||'').toLowerCase().includes(q) || (v.company||'').toLowerCase().includes(q) || (v.host||'').toLowerCase().includes(q) || (v.plate||'').toLowerCase().includes(q));
  if (statusFilter) visitors = visitors.filter(v => v.status === statusFilter);
  if (tabFilter === 'inside') visitors = visitors.filter(v => v.status === 'Inside' || v.status === 'Overstay');
  if (tabFilter === 'today') visitors = visitors.filter(v => isToday(v.checkIn));
  if (tabFilter === 'out') visitors = visitors.filter(v => v.status === 'Checked Out');

  visitors.sort((a,b) => {
    const order = { 'Overstay':0,'Inside':1,'Waiting Approval':2,'Checked Out':3 };
    return (order[a.status] ?? 99) - (order[b.status] ?? 99);
  });

  updateTypeCounts();

  const tbody = document.getElementById('visitor-table');
  if (!tbody) return;
  const driverView = currentVisitorType === 'Driver';
  const thead = document.getElementById('visitor-thead');
  if (thead) thead.innerHTML = driverView ? VISITOR_HEAD_DRIVER : VISITOR_HEAD_DEFAULT;
  if (visitors.length === 0) {
    tbody.innerHTML = '<tr><td colspan="' + (driverView ? 10 : 9) + '"><div class="empty"><i class="ti ti-users"></i><p>Tidak ada data visitor</p></div></td></tr>';
    return;
  }
  tbody.innerHTML = visitors.map((v, i) => `
    <tr>
      <td class="mono">${i+1}</td>
      <td>
        <div class="td-main">${v.name}</div>
        <div class="td-sub mono">${v.id}</div>
      </td>
      <td>${typePill(v.visitorType)}</td>
      ${driverView ? `
      <td>${v.company && v.company !== '—' ? v.company : '—'}</td>
      <td class="mono">${v.plate || '—'}</td>
      <td>${v.vehicle || '—'}</td>` : `
      <td>${v.company || '—'}<div class="td-sub">${v.phone || ''}</div></td>
      <td>${v.host}<div class="td-sub">${v.dept || ''}</div></td>`}
      <td class="mono">${fmtDateTime(v.checkIn)}</td>
      <td>
        ${v.status === 'Waiting Approval' && !v.slaStart
          ? '<span style="font-size:12px;color:var(--text3)">— Menunggu —</span>'
          : `<span class="timer ${durationSecs(v.visitorType === 'Contractor' ? (v.slaStart || v.checkIn) : v.checkIn, v.checkOut) > 14400 && v.status !== 'Checked Out' ? 'danger' : durationSecs(v.visitorType === 'Contractor' ? (v.slaStart || v.checkIn) : v.checkIn, v.checkOut) > 10800 ? 'warn' : ''}"
              data-checkin="${v.visitorType === 'Contractor' ? (v.slaStart || v.checkIn) : v.checkIn}"
              data-checkout="${v.checkOut || ''}" data-status="${v.status}">
              ${fmtDuration(v.visitorType === 'Contractor' ? (v.slaStart || v.checkIn) : v.checkIn, v.checkOut)}</span>`
        }
      </td>
      <td>${statusPill(v.status)}</td>
      <td>
        <div class="flex-gap8">
          <button class="btn btn-sm" onclick="openDetail('${v.id}')"><i class="ti ti-eye"></i></button>
          ${v.visitorType === 'Driver' && v.status !== 'Checked Out'
            ? `<button class="btn btn-sm btn-primary" onclick="openDriverApproval('${v.id}')"><i class="ti ti-truck"></i> Approval</button>`
            : `${(v.status === 'Waiting Approval')
                ? `<button class="btn btn-sm btn-primary" onclick="approveVisitor('${v.id}')"><i class="ti ti-check"></i></button>`
                : ''}
               ${v.status !== 'Checked Out' ? `<button class="btn btn-sm btn-success" onclick="doCheckout('${v.id}')"><i class="ti ti-logout"></i></button>` : ''}`
          }
          ${v.status === 'Checked Out' ? `<button class="btn btn-sm btn-primary" onclick="reCheckIn('${v.id}')" title="Re-check In (lanjutkan timer)"><i class="ti ti-rotate-clockwise"></i></button>` : ''}
          <button class="btn btn-sm btn-danger" onclick="deleteVisitor('${v.id}')"><i class="ti ti-trash"></i></button>
        </div>
      </td>
    </tr>
  `).join('');
}

function openCheckinByType() {
  const typeMap = {
    'Guest': 'modal-checkin-guest',
    'Contractor': 'modal-checkin-contractor',
    'Driver': 'modal-checkin-driver',
    'Employee Test': 'modal-checkin-employee',
    'Auditor': 'modal-checkin-auditor',
  };
  if (currentVisitorType !== 'all' && typeMap[currentVisitorType]) {
    openModal(typeMap[currentVisitorType]);
  } else {
    openModal('modal-select-type');
  }
}

// ── NAMA PENGUNJUNG ROW (Guest / Auditor — multi nama, 1 ID) ──
function addGuestNamaRow() {
  const list = document.getElementById('g-nama-list');
  const row = document.createElement('div');
  row.className = 'equip-row';
  row.innerHTML = `
    <input type="text" class="g-nama-item" placeholder="Nama pengunjung" style="grid-column:1/-2">
    <button type="button" class="btn-remove-equip" onclick="this.closest('.equip-row').remove()">
      <i class="ti ti-x"></i>
    </button>
  `;
  list.appendChild(row);
  row.querySelector('.g-nama-item').focus();
}

function addAuditorNamaRow() {
  const list = document.getElementById('aud-nama-list');
  const row = document.createElement('div');
  row.className = 'equip-row';
  row.innerHTML = `
    <input type="text" class="aud-nama-item" placeholder="Nama pengunjung" style="grid-column:1/-2">
    <button type="button" class="btn-remove-equip" onclick="this.closest('.equip-row').remove()">
      <i class="ti ti-x"></i>
    </button>
  `;
  list.appendChild(row);
  row.querySelector('.aud-nama-item').focus();
}

// ── EQUIPMENT ROW (Contractor) ────────────────────────────
function addConPekerjaRow() {
  const list = document.getElementById('con-pekerja-list');
  const row = document.createElement('div');
  row.className = 'equip-row';
  row.innerHTML = `
    <input type="text" class="con-pekerja-nama" placeholder="Nama pekerja" style="grid-column:1/-2">
    <button type="button" class="btn-remove-equip" onclick="this.closest('.equip-row').remove();updateConPersonilCount()">
      <i class="ti ti-x"></i>
    </button>
  `;
  list.appendChild(row);
  row.querySelector('.con-pekerja-nama').addEventListener('input', updateConPersonilCount);
  row.querySelector('.con-pekerja-nama').focus();
}

function updateConPersonilCount() {
  const names = [...document.querySelectorAll('#con-pekerja-list .con-pekerja-nama')]
    .map(el => el.value.trim()).filter(Boolean);
  const el = document.getElementById('con-personil-display');
  if (el) el.textContent = names.length + ' orang';
}

function addEquipmentRow() {
  const list = document.getElementById('con-equipment-list');
  const row = document.createElement('div');
  row.className = 'equip-row';
  row.innerHTML = `
    <input type="text" class="eq-nama" placeholder="Nama peralatan">
    <input type="number" class="eq-qty" placeholder="Qty" min="1"
      oninput="this.value=this.value.replace(/[^0-9]/g,'')" style="text-align:center">
    <button type="button" class="btn-remove-equip" onclick="this.closest('.equip-row').remove()">
      <i class="ti ti-x"></i>
    </button>
  `;
  list.appendChild(row);
  row.querySelector('.eq-nama').focus();
}

// ── SLA HELPERS ───────────────────────────────────────────
function getSLADisplay(slaStart) {
  if (!slaStart) return '<span class="sla-badge sla-warn"><i class="ti ti-clock"></i> Menunggu Approval</span>';
  const secs = durationSecs(slaStart);
  const label = fmtDuration(slaStart);
  // note: caller harus render elemen dengan class timer + data-checkin agar live tick berjalan
  if (secs < 8 * 3600)  return `<span class="sla-badge sla-ok"><i class="ti ti-clock"></i> ${label}</span>`;
  if (secs < 12 * 3600) return `<span class="sla-badge sla-warn"><i class="ti ti-alert-triangle"></i> ${label}</span>`;
  return `<span class="sla-badge sla-over"><i class="ti ti-alert-circle"></i> ${label}</span>`;
}

// ── DRIVER APPROVAL SYSTEM ────────────────────────────────
function openDriverApproval(id) {
  const v = DB.visitors().find(x => x.id === id);
  if (!v) return;

  const gatePassExists = !!v.gatePassOut;
  const isApproved     = v.driverApproved;

  const content = document.getElementById('driver-approval-content');
  content.innerHTML = `
    <div style="margin-bottom:16px">
      <div style="font-weight:600;font-size:15px">${v.name}</div>
      <div style="font-size:12px;color:var(--text3)">${v.company || '—'} · ${v.plate || '—'} · ${v.vehicle || '—'}</div>
      <div style="margin-top:6px;font-size:12px;color:var(--text3)">Muatan: ${v.cargo || '—'}</div>
      <div style="margin-top:4px">${statusPill(v.status)}</div>
    </div>

    <div class="approval-step">
      <div class="approval-step-icon step-done"><i class="ti ti-login"></i></div>
      <div>
        <div style="font-weight:500;font-size:13px">Check In</div>
        <div style="font-size:12px;color:var(--text3)">Masuk: ${fmtDateTime(v.checkIn)}</div>
      </div>
    </div>

    <div class="approval-step">
      <div class="approval-step-icon ${isApproved ? 'step-done' : 'step-pend'}">
        <i class="ti ${isApproved ? 'ti-check' : 'ti-clock'}"></i>
      </div>
      <div>
        <div style="font-weight:500;font-size:13px">Approval Keluar</div>
        <div style="font-size:12px;color:var(--text3)">
          ${isApproved ? `Disetujui: ${fmtDateTime(v.approvedAt)}` : 'Menunggu persetujuan security/admin'}
        </div>
      </div>
    </div>

    <div class="approval-step">
      <div class="approval-step-icon ${gatePassExists ? 'step-done' : 'step-lock'}">
        <i class="ti ${gatePassExists ? 'ti-file-check' : 'ti-lock'}"></i>
      </div>
      <div>
        <div style="font-weight:500;font-size:13px">Gate Pass Keluar</div>
        <div style="font-size:12px;color:var(--text3)">
          ${gatePassExists ? `No: <strong>${v.gatePassOut}</strong>` : 'Terkunci — butuh approval terlebih dahulu'}
        </div>
      </div>
    </div>

    <div class="approval-step">
      <div class="approval-step-icon ${v.status === 'Checked Out' ? 'step-done' : 'step-lock'}">
        <i class="ti ${v.status === 'Checked Out' ? 'ti-logout' : 'ti-lock'}"></i>
      </div>
      <div>
        <div style="font-weight:500;font-size:13px">Check Out</div>
        <div style="font-size:12px;color:var(--text3)">
          ${v.status === 'Checked Out' ? fmtDateTime(v.checkOut) : 'Terkunci — selesaikan step di atas'}
        </div>
      </div>
    </div>
  `;

  const actions = document.getElementById('driver-approval-actions');
  actions.innerHTML = `<button class="btn" onclick="closeModal('modal-driver-approval')">Tutup</button>`;

  if (!isApproved && v.status !== 'Checked Out') {
    actions.innerHTML += `
      <button class="btn btn-primary" onclick="approveDriverOut('${v.id}')">
        <i class="ti ti-check"></i> Approve Keluar
      </button>`;
  }
  if (isApproved && !gatePassExists && v.status !== 'Checked Out') {
    actions.innerHTML += `
      <button class="btn btn-success" onclick="generateDriverGatePass('${v.id}')">
        <i class="ti ti-file-plus"></i> Generate Gate Pass
      </button>`;
  }
  if (gatePassExists && v.status !== 'Checked Out') {
    actions.innerHTML += `
      <button class="btn btn-success" onclick="doCheckout('${v.id}'); closeModal('modal-driver-approval')">
        <i class="ti ti-logout"></i> Check Out
      </button>`;
  }

  openModal('modal-driver-approval');
}

function approveDriverOut(id) {
  const visitors = DB.visitors();
  const v = visitors.find(x => x.id === id);
  if (!v) return;
  v.driverApproved = true;
  v.approvedAt = now();
  DB.setVisitors(visitors);
  markLocalWrite();
  sendToGAS('APPROVE_DRIVER_OUT', { id, approvedAt: v.approvedAt });
  addAudit('APPROVE_OUT', 'DRIVER', v.name + ' — disetujui keluar');
  sendLogToGAS('APPROVE_OUT', 'DRIVER', v.name + ' — disetujui keluar');
  toast(v.name + ' disetujui keluar — silakan generate Gate Pass', 'success');
  openDriverApproval(id); // refresh modal
}

function generateDriverGatePass(id) {
  const visitors = DB.visitors();
  const v = visitors.find(x => x.id === id);
  if (!v) return;
  if (!v.driverApproved) { toast('Driver belum mendapat approval keluar', 'error'); return; }

  // Generate gate pass number
  const d = new Date();
  const ds = d.getFullYear().toString() + String(d.getMonth()+1).padStart(2,'0') + String(d.getDate()).padStart(2,'0');
  const existing = DB.visitors().filter(x => x.gatePassOut && x.gatePassOut.startsWith('GPD-'+ds));
  const num = String(existing.length + 1).padStart(3,'0');
  v.gatePassOut = 'GPD-' + ds + '-' + num;

  DB.setVisitors(visitors);
  markLocalWrite();
  sendToGAS('GENERATE_DRIVER_GATEPASS', { id, gatePassOut: v.gatePassOut });
  addAudit('GATE_PASS', 'DRIVER', v.name + ' — ' + v.gatePassOut);
  sendLogToGAS('GATE_PASS', 'DRIVER', v.name + ' — ' + v.gatePassOut);
  toast('Gate Pass ' + v.gatePassOut + ' berhasil dibuat', 'success');
  openDriverApproval(id); // refresh modal
}
