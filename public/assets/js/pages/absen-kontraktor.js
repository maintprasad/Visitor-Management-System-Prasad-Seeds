// ══════════════════════════════════════════
//  ABSEN KONTRAKTOR
// ══════════════════════════════════════════
function addPekerjaRow() {
  const list = document.getElementById('absen-pekerja-list');
  const row = document.createElement('div');
  row.className = 'equip-row';
  row.innerHTML = `
    <input type="text" class="pekerja-nama" placeholder="Nama pekerja" style="grid-column:1/-2">
    <button type="button" class="btn-remove-equip" onclick="this.closest('.equip-row').remove()">
      <i class="ti ti-x"></i>
    </button>
  `;
  list.appendChild(row);
  row.querySelector('.pekerja-nama').focus();
}

function addAlatRow() {
  const list = document.getElementById('absen-alat-list');
  const row = document.createElement('div');
  row.className = 'equip-row';
  row.innerHTML = `
    <input type="text" class="alat-nama" placeholder="Nama alat">
    <input type="number" class="alat-qty" placeholder="Qty" min="1"
      oninput="this.value=this.value.replace(/[^0-9]/g,'')" style="text-align:center">
    <button type="button" class="btn-remove-equip" onclick="this.closest('.equip-row').remove()">
      <i class="ti ti-x"></i>
    </button>
  `;
  list.appendChild(row);
  row.querySelector('.alat-nama').focus();
}

function submitAbsenKontraktor() {
  const pt   = document.getElementById('absen-pt').value.trim();
  const spv  = document.getElementById('absen-spv').value.trim();
  if (!pt || !spv) { toast('Nama PT dan Supervisi wajib diisi', 'error'); return; }

  const pekerja = [];
  document.querySelectorAll('#absen-pekerja-list .pekerja-nama').forEach(el => {
    const nama = el.value.trim();
    if (nama) pekerja.push(nama);
  });
  if (pekerja.length === 0) { toast('Tambahkan minimal 1 nama pekerja', 'error'); return; }

  const alat = [];
  document.querySelectorAll('#absen-alat-list .equip-row').forEach(row => {
    const nama = row.querySelector('.alat-nama')?.value?.trim();
    const qty  = row.querySelector('.alat-qty')?.value?.trim();
    if (nama) alat.push({ nama, qty: qty || '1' });
  });

  const data = {
    id: genId('ABK'),
    pt, spv, pekerja, alat,
    tanggal: now(),
    user: document.getElementById('sb-uname').textContent,
  };

  const absen = DB.absen();
  absen.unshift(data);
  DB.setAbsen(absen);
  addAudit('CREATE', 'ABSEN_KONTRAKTOR', `${pt} — ${spv} — ${pekerja.length} pekerja`);
  sendToGAS('ADD_ABSEN_KONTRAKTOR', data);
  sendLogToGAS('CREATE', 'ABSEN_KONTRAKTOR', `${pt} — ${spv}`);

  // reset form
  document.getElementById('absen-pt').value = '';
  document.getElementById('absen-spv').value = '';
  document.getElementById('absen-pekerja-list').innerHTML = '';
  document.getElementById('absen-alat-list').innerHTML = '';

  closeModal('modal-absen-kontraktor');
  toast(`Absen ${pt} berhasil disimpan`, 'success');
  renderAbsenTable();
}

function renderAbsenTable() {
  let data = DB.absen();
  const q = (document.getElementById('absen-search')?.value || '').toLowerCase();
  if (q) data = data.filter(d => d.pt.toLowerCase().includes(q) || d.spv.toLowerCase().includes(q));

  const tbody = document.getElementById('absen-table');
  if (!tbody) return;
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7"><div class="empty"><i class="ti ti-clipboard-list"></i><p>Belum ada data absen kontraktor</p></div></td></tr>';
    return;
  }
  tbody.innerHTML = data.map((d, i) => `
    <tr>
      <td class="mono">${i+1}</td>
      <td><div class="td-main">${d.pt}</div><div class="td-sub mono">${d.id}</div></td>
      <td>${d.spv}</td>
      <td><span class="pill pill-pending">${d.pekerja.length} orang</span></td>
      <td><span class="pill pill-good">${d.alat.length} item</span></td>
      <td class="mono">${fmtDateTime(d.tanggal)}</td>
      <td>
        <div class="flex-gap8">
          <button class="btn btn-sm" onclick="openAbsenDetail('${d.id}')"><i class="ti ti-eye"></i></button>
          <button class="btn btn-sm btn-danger" onclick="deleteAbsen('${d.id}')"><i class="ti ti-trash"></i></button>
        </div>
      </td>
    </tr>
  `).join('');
}

function openAbsenDetail(id) {
  const d = DB.absen().find(x => x.id === id);
  if (!d) return;
  const content = document.getElementById('absen-detail-content');
  content.innerHTML = `
    <div style="margin-bottom:16px">
      <div style="font-size:13px;color:var(--text3);margin-bottom:4px">ID: <span class="mono">${d.id}</span></div>
      <div style="font-size:18px;font-weight:700">${d.pt}</div>
      <div style="font-size:13px;color:var(--text3);margin-top:2px">Supervisi: <strong>${d.spv}</strong></div>
      <div style="font-size:12px;color:var(--text3);margin-top:4px mono">${fmtDateTime(d.tanggal)}</div>
    </div>

    <div class="card" style="margin-bottom:12px;background:var(--bg)">
      <div class="card-title" style="margin-bottom:10px">
        <span class="card-title-left"><i class="ti ti-users"></i> Daftar Pekerja</span>
        <span class="pill pill-pending">${d.pekerja.length} orang</span>
      </div>
      <div style="display:flex;flex-direction:column;gap:6px">
        ${d.pekerja.map((p, i) => `
          <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:var(--white);border-radius:var(--radius-sm);border:1px solid var(--border)">
            <div style="width:24px;height:24px;background:var(--blue);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0">${i+1}</div>
            <div style="font-weight:500;font-size:13px">${p}</div>
          </div>
        `).join('')}
      </div>
    </div>

    <div class="card" style="background:var(--bg)">
      <div class="card-title" style="margin-bottom:10px">
        <span class="card-title-left"><i class="ti ti-tools"></i> Daftar Alat</span>
        <span class="pill pill-good">${d.alat.length} item</span>
      </div>
      ${d.alat.length === 0
        ? '<div style="font-size:13px;color:var(--text3);text-align:center;padding:12px">Tidak ada alat dibawa</div>'
        : `<div style="display:flex;flex-direction:column;gap:6px">
            ${d.alat.map((a, i) => `
              <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:var(--white);border-radius:var(--radius-sm);border:1px solid var(--border)">
                <div style="display:flex;align-items:center;gap:10px">
                  <div style="width:24px;height:24px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0">${i+1}</div>
                  <div style="font-weight:500;font-size:13px">${a.nama}</div>
                </div>
                <span class="pill pill-good">Qty: ${a.qty}</span>
              </div>
            `).join('')}
          </div>`
      }
    </div>
  `;
  openModal('modal-absen-detail');
}

function deleteAbsen(id) {
  if (!confirm('Hapus data absen ini?')) return;
  const absen = DB.absen();
  const d = absen.find(x => x.id === id);
  DB.setAbsen(absen.filter(x => x.id !== id));
  addAudit('DELETE', 'ABSEN_KONTRAKTOR', (d?.pt || id) + ' — dihapus');
  toast('Data absen dihapus', 'info');
  renderAbsenTable();
  sendToGAS('ADD_LOG', {
    action: 'DELETE', entity: 'ABSEN_KONTRAKTOR',
    refId: id,
    detail: (d?.pt || id) + ' — dihapus',
    user: document.getElementById('sb-uname').textContent,
    ts: now(),
  });
}
