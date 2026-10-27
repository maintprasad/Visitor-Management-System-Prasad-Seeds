function openDetail(id) {
  const v = DB.visitors().find(x => x.id === id);
  if (!v) return;
  const content = document.getElementById('modal-detail-content');
  const actions = document.getElementById('modal-detail-actions');

  // Auto-register equipList ke DB.equipment() kalau belum ada
  // Ini handle kasus data lama atau data yang gagal register saat check-in
  if ((v.equipList || []).length > 0 && (v.status === 'Inside' || v.status === 'Overstay')) {
    const equipment = DB.equipment();
    let changed = false;
    v.equipList.forEach(item => {
      if (!item || !item.nama) return;
      const nameLower = item.nama.trim().toLowerCase();
      const alreadyExists = equipment.find(e =>
        e.visitorId === v.id &&
        (e.name || '').trim().toLowerCase() === nameLower
      );
      if (!alreadyExists) {
        equipment.unshift({
          id: genId('EQ'),
          name: item.nama,
          serial: '',
          owner: v.name,
          ownerCompany: v.company || '',
          visitorId: v.id,
          visitorType: v.visitorType,
          qty: item.qty || '1',
          gatePass: genGatePass(),
          condition: 'Good / Baik',
          status: 'Inside',
          notes: 'Auto-registered dari detail visitor',
          dateIn: v.checkIn,
          dateOut: null,
          source: 'AUTO_REGISTER',
        });
        changed = true;
      }
    });
    if (changed) {
      DB.setEquipment(equipment);
      markLocalWrite();
    }
  }

  // Re-fetch setelah auto-register di atas selesai, supaya linkedEq sudah include item baru
  const linkedEq = getLinkedEquipment(v);
  const formList = []; // semua equipList sudah diregister, tidak ada yang pending sync
  const hasEquip = linkedEq.length > 0;

  let equipHTML = `
    <div style="margin-bottom:16px">
      <label style="font-size:12px;font-weight:700;color:var(--text2);display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em">
        <i class="ti ti-package" style="font-size:13px;margin-right:4px"></i>
        Equipment Dibawa
        <span class="pill pill-pending" style="margin-left:6px">${linkedEq.length + formList.length} item</span>
      </label>`;

  if (!hasEquip) {
    equipHTML += `
      <div style="padding:16px;text-align:center;background:var(--bg);border-radius:var(--radius-sm);border:1.5px dashed var(--border)">
        <i class="ti ti-package-off" style="font-size:24px;color:var(--border);display:block;margin-bottom:6px"></i>
        <span style="font-size:12px;color:var(--text3)">Tidak ada equipment yang dibawa</span>
      </div>`;
  } else {
    equipHTML += `<div style="display:flex;flex-direction:column;gap:6px">`;
    linkedEq.forEach(e => {
      const borderColor = e.status === 'Outside' ? 'var(--border)' : 'var(--green)';
      const bgIcon = e.status === 'Outside' ? 'var(--gray-light)' : 'var(--green-light)';
      const colorIcon = e.status === 'Outside' ? 'var(--gray)' : 'var(--green)';
      const qtyStr = e.qty && e.qty !== '1' ? ' &middot; Qty: <strong>' + e.qty + '</strong>' : '';
      equipHTML += `
        <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg);border-radius:var(--radius-sm);border:1.5px solid ${borderColor}">
          <div style="width:32px;height:32px;border-radius:8px;background:${bgIcon};display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ti ti-tool" style="font-size:15px;color:${colorIcon}"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:13px">${e.name}</div>
            <div style="font-size:11px;color:var(--text3);margin-top:1px">
              Gate Pass: <span class="mono">${e.gatePass || '—'}</span>${qtyStr}
            </div>
            <div style="font-size:11px;color:var(--text3)">
              Pemilik: <strong>${e.owner || v.name}</strong>
              <span class="mono" style="color:var(--blue);margin-left:4px">${v.id}</span>
            </div>
          </div>
          <div style="flex-shrink:0">${statusPill(e.status)}</div>
        </div>`;
    });
    formList.forEach(item => {
      equipHTML += `
        <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--amber-light);border-radius:var(--radius-sm);border:1.5px solid var(--amber)">
          <div style="width:32px;height:32px;border-radius:8px;background:rgba(217,119,6,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ti ti-tool" style="font-size:15px;color:var(--amber)"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:13px">${item.nama}</div>
            <div style="font-size:11px;color:var(--amber-text);margin-top:1px">
              Qty: <strong>${item.qty || '1'}</strong> &middot; Diinput via form (belum terdaftar di Equipment)
            </div>
            <div style="font-size:11px;color:var(--amber-text)">
              Pemilik: <strong>${v.name}</strong>
              <span class="mono" style="color:var(--blue);margin-left:4px">${v.id}</span>
            </div>
          </div>
          <span class="pill pill-waiting">Pending Sync</span>
        </div>`;
    });
    equipHTML += `</div>`;
  }
  equipHTML += `</div>`;

  const initials = v.name.split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase();
  const badgeHTML = `
    <div style="margin-top:8px">
      <label style="font-size:12px;font-weight:500;color:var(--text2);display:block;margin-bottom:8px">Visitor Badge Preview</label>
      <div class="badge-card">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
          <div class="sb-avatar" style="width:44px;height:44px;font-size:16px">${initials}</div>
          <div>
            <div class="bc-name">${v.name}</div>
            <div class="bc-company">${v.company || '—'}</div>
          </div>
        </div>
        <div style="background:rgba(255,255,255,.07);border-radius:6px;padding:10px;font-size:12px;color:rgba(255,255,255,.7)">
          ${v.visitorType === 'Driver'
            ? `<div>🚚 ${v.plate || '—'}${v.vehicle ? ' · ' + v.vehicle : ''}</div>`
            : `<div>🏢 Host: ${v.host} — ${v.dept || '—'}</div>
          <div style="margin-top:4px">📋 ${v.purpose}</div>`}
          <div style="margin-top:4px">🕐 Masuk: ${fmtDateTime(v.checkIn)}</div>
        </div>
        <div class="bc-status" style="margin-top:10px">${v.status}</div>
        <div class="bc-detail mono">ID: ${v.id}</div>
      </div>
    </div>`;

  content.innerHTML = `
    <div class="form-grid" style="margin-bottom:16px">
      <div class="form-group">
        <label>Visitor ID</label>
        <div class="mono" style="padding:8px 10px;background:var(--bg);border-radius:var(--radius-sm);font-size:12px">${v.id}</div>
      </div>
      <div class="form-group">
        <label>Status</label>
        <div>${statusPill(v.status)}</div>
      </div>
      <div class="form-group">
        <label>Full Name</label>
        <div style="font-weight:500">${v.name}</div>
      </div>
      ${v.visitorType === 'Driver' ? `
      <div class="form-group">
        <label>Nama Transporter</label>
        <div>${v.company || '—'}</div>
      </div>
      <div class="form-group">
        <label>Nomor Polisi</label>
        <div class="mono">${v.plate || '—'}</div>
      </div>
      <div class="form-group">
        <label>Jenis Kendaraan</label>
        <div>${v.vehicle || '—'}</div>
      </div>` : `
      <div class="form-group">
        <label>Company</label>
        <div>${v.company || '—'}</div>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <div>${v.phone || '—'}</div>
      </div>
      <div class="form-group">
        <label>Identity Number</label>
        <div class="mono">${v.identity || '—'}</div>
      </div>
      <div class="form-group">
        <label>Purpose of Visit</label>
        <div>${v.purpose}</div>
      </div>
      <div class="form-group">
        <label>Host / PIC</label>
        <div>${v.host}<span style="color:var(--text3)"> · ${v.dept || '—'}</span></div>
      </div>`}
      <div class="form-group">
        <label>Check-in Time</label>
        <div class="mono">${fmtDateTime(v.checkIn)}</div>
      </div>
      <div class="form-group">
        <label>Check-out Time</label>
        <div class="mono">${v.checkOut ? fmtDateTime(v.checkOut) : '—'}</div>
      </div>
      <div class="form-group">
        <label>Duration</label>
        <div class="mono">${fmtDuration(v.checkIn, v.checkOut)}</div>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <div style="white-space:pre-wrap">${v.notes ? escapeHtml(v.notes) : '—'}</div>
      </div>
    </div>
    ${equipHTML}
    ${badgeHTML}
  `;

  actions.innerHTML = `
    <button class="btn" onclick="closeModal('modal-detail')">Tutup</button>
    ${v.status !== 'Checked Out'
      ? `<button class="btn btn-success" onclick="doCheckout('${v.id}');closeModal('modal-detail')"><i class="ti ti-logout"></i> Check Out</button>`
      : ''}
    <button class="btn btn-primary" onclick="printBadge()"><i class="ti ti-printer"></i> Print Badge</button>
  `;

  openModal('modal-detail');
}

function printBadge() { window.print(); }
