// ══════════════════════════════════════════
//  EQUIPMENT TABLE
// ══════════════════════════════════════════
function renderEquipmentTable() {
  let items = DB.equipment();
  const q = (document.getElementById('equip-search')?.value || '').toLowerCase();
  const tabFilter = tabFilters['equipment'] || 'all';

  if (q) items = items.filter(e => e.name.toLowerCase().includes(q) || e.serial.toLowerCase().includes(q) || e.owner.toLowerCase().includes(q));
  if (tabFilter === 'inside') items = items.filter(e => e.status === 'Inside');
  if (tabFilter === 'outside') items = items.filter(e => e.status === 'Outside');
  if (tabFilter === 'pending') items = items.filter(e => e.status === 'Pending Approval');

  const tbody = document.getElementById('equipment-table');
  if (!tbody) return;
  if (items.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9"><div class="empty"><i class="ti ti-package"></i><p>Tidak ada data equipment</p></div></td></tr>';
    return;
  }
  tbody.innerHTML = items.map((e, i) => {
    let linkedVisitor = e.visitorId ? DB.visitors().find(v => v.id === e.visitorId) : null;
    if (!linkedVisitor) {
      // Fallback: cocokkan lewat nama owner ke nama/company visitor Contractor yang aktif
      const ownerLower = (e.owner || '').trim().toLowerCase();
      if (ownerLower) {
        linkedVisitor = DB.visitors().find(v =>
          v.visitorType === 'Contractor' &&
          (v.status === 'Inside' || v.status === 'Overstay') &&
          ((v.name || '').trim().toLowerCase() === ownerLower || (v.company || '').trim().toLowerCase() === ownerLower)
        );
      }
    }
    return `
    <tr>
      <td class="mono">${i+1}</td>
      <td><div class="td-main">${e.name}</div><div class="td-sub">${e.notes || ''}</div></td>
      <td class="mono">${e.serial || '—'}</td>
      <td>
        <div class="td-main">${e.owner}</div>
        ${linkedVisitor ? `<div class="td-sub"><span class="pill pill-waiting"><i class="ti ti-hard-hat" style="font-size:10px"></i> ${linkedVisitor.name}</span></div>` : ''}
      </td>
      <td>
        ${linkedVisitor
          ? `<div class="mono" style="font-size:11px">${linkedVisitor.id}</div><div class="td-sub">${typePill(linkedVisitor.visitorType)}</div>`
          : `<span style="color:var(--text3);font-size:12px">—</span>`}
      </td>
      <td class="mono">${e.gatePass}</td>
      <td><span class="pill ${e.condition === 'Good / Baik' ? 'pill-good' : 'pill-waiting'}">${e.condition}</span></td>
      <td>${statusPill(e.status)}</td>
      <td class="mono">${fmtDate(e.dateIn)}</td>
      <td>
        <div class="flex-gap8">
          ${e.status === 'Inside' ? `<button class="btn btn-sm btn-success" onclick="equipCheckout('${e.id}')"><i class="ti ti-logout"></i> Out</button>` : ''}
          ${e.status === 'Pending Approval' ? `<button class="btn btn-sm btn-primary" onclick="approveEquip('${e.id}')"><i class="ti ti-check"></i></button>` : ''}
          <button class="btn btn-sm btn-danger" onclick="deleteEquip('${e.id}')"><i class="ti ti-trash"></i></button>
        </div>
      </td>
    </tr>
  `}).join('');
}
