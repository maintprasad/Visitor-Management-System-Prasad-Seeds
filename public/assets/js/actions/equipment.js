function populateEquipContractorLink() {
  const sel = document.getElementById('eq-link-contractor');
  if (!sel) return;
  const activeContractors = DB.visitors().filter(v => v.visitorType === 'Contractor' && (v.status === 'Inside' || v.status === 'Overstay'));
  sel.innerHTML = '<option value="">— Tidak ditautkan / equipment umum —</option>' +
    activeContractors.map(v => `<option value="${v.id}">${v.name} — ${v.company || ''} (${v.area || '—'})</option>`).join('');
}

function autofillEquipOwner() {
  const id = document.getElementById('eq-link-contractor').value;
  if (!id) return;
  const v = DB.visitors().find(x => x.id === id);
  if (v) document.getElementById('eq-owner').value = v.name;
}

async function submitEquipment() {
  const name = document.getElementById('eq-name').value.trim();
  const owner = document.getElementById('eq-owner').value.trim();
  if (!name || !owner) { toast('Lengkapi field wajib', 'error'); return; }

  const visitorId = document.getElementById('eq-link-contractor')?.value || null;
  const linkedVisitor = visitorId ? DB.visitors().find(x => x.id === visitorId) : null;

  const eq = {
    id: genId('EQ'),
    name, owner,
    serial: document.getElementById('eq-serial').value.trim(),
    gatePass: document.getElementById('eq-gatepass').value.trim() || genGatePass(),
    condition: document.getElementById('eq-condition').value,
    status: document.getElementById('eq-status').value,
    notes: document.getElementById('eq-notes').value.trim(),
    dateIn: now(), dateOut: null,
    visitorId: visitorId || null,
    visitorType: linkedVisitor ? linkedVisitor.visitorType : null,
    source: linkedVisitor ? 'EQUIPMENT_MENU_LINKED' : 'EQUIPMENT_MENU',
    user: document.getElementById('sb-uname').textContent,
  };
 
  const equipment = DB.equipment();
  equipment.unshift(eq);
  DB.setEquipment(equipment);
  markLocalWrite();
  addAudit('CREATE', 'EQUIPMENT', `${name} — ${owner} — ${eq.gatePass}` + (linkedVisitor ? ` — ditautkan ke ${linkedVisitor.name}` : ''));
  closeModal('modal-equipment');
  toast(name + ' berhasil ditambahkan', 'success');
  ['eq-name','eq-serial','eq-owner','eq-gatepass','eq-notes'].forEach(id => document.getElementById(id).value = '');
  const linkSel = document.getElementById('eq-link-contractor');
  if (linkSel) linkSel.selectedIndex = 0;
  renderPage(currentPage);
 
  // Kirim ke Google Sheets
  sendToGAS('ADD_EQUIPMENT', eq);
  sendLogToGAS('CREATE', 'EQUIPMENT', `${name} — ${owner} — ${eq.gatePass}`);
}


async function equipCheckout(id) {
  if (!confirm('Konfirmasi equipment keluar?')) return;
  const equipment = DB.equipment();
  const eq = equipment.find(x => x.id === id);
  if (!eq) return;
  eq.status = 'Outside'; eq.dateOut = now();
  DB.setEquipment(equipment);
  markLocalWrite();
  addAudit('CHECK_OUT', 'EQUIPMENT', eq.name + ' — ' + eq.gatePass);
  toast(eq.name + ' berhasil check out', 'success');
  renderPage(currentPage);
 
  // Kirim ke Google Sheets
  sendToGAS('EQUIPMENT_OUT', {
    id: id,
    dateOut: eq.dateOut,
    user: document.getElementById('sb-uname').textContent,
  });
  sendLogToGAS('EQUIPMENT_OUT', 'EQUIPMENT', `${eq.name} — ${eq.gatePass}`);
}


function approveEquip(id) {
  const equipment = DB.equipment();
  const eq = equipment.find(x => x.id === id);
  if (!eq) return;
  eq.status = 'Inside';
  DB.setEquipment(equipment);
  markLocalWrite();
  addAudit('UPDATE', 'EQUIPMENT', eq.name + ' — disetujui masuk');
  toast(eq.name + ' disetujui', 'success');
  renderPage(currentPage);
  // Kirim ke Google Sheets
  sendToGAS('UPDATE_STATUS', {
    sheet: 'Equipment',
    id: id,
    status: 'Inside',
    user: document.getElementById('sb-uname').textContent,
  });
  sendLogToGAS('APPROVE', 'EQUIPMENT', eq.name + ' — disetujui masuk');
}

function deleteEquip(id) {
  if (!confirm('Hapus data equipment ini?')) return;
  const equipment = DB.equipment();
  const eq = equipment.find(x => x.id === id);
  DB.setEquipment(equipment.filter(x => x.id !== id));
  markDeleted('Equipment', id);
  markLocalWrite();
  addAudit('DELETE', 'EQUIPMENT', (eq?.name || id) + ' — dihapus');
  toast('Equipment dihapus', 'info');
  renderPage(currentPage);
  // Kirim ke Google Sheets
  sendToGAS('DELETE_ROW', {
    sheet: 'Equipment',
    id: id,
    user: document.getElementById('sb-uname').textContent,
  });
}

// DETAIL MODAL
