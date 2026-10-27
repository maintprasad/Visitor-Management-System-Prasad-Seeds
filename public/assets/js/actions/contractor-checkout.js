// ══════════════════════════════════════════
//  CONTRACTOR CHECKOUT MODAL
// ══════════════════════════════════════════
function openContractorCheckoutModal(id) {
  const v = DB.visitors().find(x => x.id === id);
  if (!v) return;

  // Reset pilihan checklist setiap kali modal dibuka
  window._contractorCheckoutDecisions = {};

  // Gabungkan equipment dari check-in (v.equipList) dengan equipment di sheet Equipment
  // yang terkait visitor ini — baik via visitorId maupun kecocokan nama/company owner —
  // dan hanya yang masih berstatus Inside, supaya tidak duplikat dengan yang sudah Outside.
  // Setiap item ditautkan ke equipId record aslinya, supaya saat checkout kita tahu
  // pasti record mana yang harus diubah — bukan menyamaratakan semua jadi Outside.
  const linkedEquipAll = getLinkedEquipment(v).filter(e => e.status === 'Inside' || e.status === 'Pending Approval');
  const usedEquipIds = new Set();

  const combinedEquipList = (v.equipList || []).map(eq => {
    const nameLower = (eq.nama || '').trim().toLowerCase();
    const match = linkedEquipAll.find(e => !usedEquipIds.has(e.id) && (e.name || '').trim().toLowerCase() === nameLower);
    if (match) usedEquipIds.add(match.id);
    return { nama: eq.nama, qty: eq.qty, fromMenu: false, equipId: match ? match.id : null };
  });
  linkedEquipAll.filter(e => !usedEquipIds.has(e.id)).forEach(e => {
    combinedEquipList.push({ nama: e.name, qty: e.qty || '1', fromMenu: true, equipId: e.id });
  });
  v.equipList = combinedEquipList;

  // Buat modal jika belum ada
  let modal = document.getElementById('modal-contractor-checkout');
  if (!modal) {
    modal = document.createElement('div');
    modal.className = 'modal-bg';
    modal.id = 'modal-contractor-checkout';
    modal.innerHTML = `
      <div class="modal" style="max-width:520px">
        <div class="modal-title">
          <span><i class="ti ti-package" style="color:var(--amber);margin-right:8px"></i>Pemeriksaan Equipment — Checkout Contractor</span>
          <button class="modal-close" onclick="closeModal('modal-contractor-checkout')"><i class="ti ti-x"></i></button>
        </div>
        <div id="contractor-checkout-content"></div>
        <div class="form-actions" id="contractor-checkout-actions"></div>
      </div>
    `;
    modal.addEventListener('click', function(e) {
      if (e.target === this) this.classList.remove('open');
    });
    document.body.appendChild(modal);
  }

  const content = document.getElementById('contractor-checkout-content');
  const actions = document.getElementById('contractor-checkout-actions');

  content.innerHTML = `
    <div style="padding:12px 14px;background:var(--amber-light);border-radius:var(--radius-sm);margin-bottom:16px;display:flex;gap:10px;align-items:flex-start">
      <i class="ti ti-alert-triangle" style="color:var(--amber);font-size:18px;flex-shrink:0;margin-top:1px"></i>
      <div>
        <div style="font-weight:600;font-size:13px;color:var(--amber-text)">Periksa semua equipment sebelum checkout</div>
        <div style="font-size:12px;color:var(--amber-text);margin-top:2px">Centang setiap item yang sudah diperiksa dan dikonfirmasi dibawa keluar oleh contractor.</div>
      </div>
    </div>

    <div style="margin-bottom:14px">
      <div style="font-weight:600;font-size:14px">${v.name}</div>
      <div style="font-size:12px;color:var(--text3)">${v.company} · Area: ${v.area || '—'} · ${v.personil || '—'} personil</div>
      <div style="font-size:12px;color:var(--text3);margin-top:2px">SPK: ${v.spk || '—'}</div>
    </div>

    <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">
      Daftar Equipment <span id="equip-check-counter" style="color:var(--blue)">0/${v.equipList.length}</span>
    </div>

    <div id="equip-checklist" style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px">
      ${v.equipList.map((eq, i) => `
        <div id="equip-row-${i}" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px solid var(--border);border-radius:var(--radius-sm);transition:all .14s">
          <div style="flex:1">
            <div style="font-weight:600;font-size:13.5px">${eq.nama} ${eq.fromMenu ? '<span class="pill pill-pending" style="margin-left:4px">+ Menu Equipment</span>' : ''}</div>
            <div style="font-size:11px;color:var(--text3);margin-top:2px">Qty: <strong>${eq.qty}</strong></div>
          </div>
          <div style="display:flex;gap:6px;flex-shrink:0">
            <button type="button" class="btn btn-sm" id="equip-btn-out-${i}" onclick="setEquipDecision('${id}', ${i}, 'out', ${v.equipList.length})">
              <i class="ti ti-logout"></i> Ikut Keluar
            </button>
            <button type="button" class="btn btn-sm" id="equip-btn-stay-${i}" onclick="setEquipDecision('${id}', ${i}, 'stay', ${v.equipList.length})">
              <i class="ti ti-door-enter"></i> Tinggal
            </button>
          </div>
        </div>
      `).join('')}
    </div>

    <div style="padding:10px 12px;background:var(--gray-light);border-radius:var(--radius-sm);font-size:12px;color:var(--text3)">
      <i class="ti ti-info-circle" style="margin-right:4px"></i>
      Pilih <strong>Ikut Keluar</strong> kalau alat dibawa keluar bareng orangnya, atau <strong>Tinggal</strong> kalau alat ditinggal di dalam. Tombol Checkout aktif setelah semua alat dipilih.
    </div>
  `;

  actions.innerHTML = `
    <button class="btn" onclick="closeModal('modal-contractor-checkout')">Batal</button>
    <button class="btn btn-success" id="btn-contractor-checkout-confirm" onclick="confirmContractorCheckout('${id}')"
      disabled style="opacity:.4;cursor:not-allowed">
      <i class="ti ti-logout"></i> Checkout Contractor
    </button>
  `;

  openModal('modal-contractor-checkout');
}

function setEquipDecision(visitorId, i, decision, total) {
  if (!window._contractorCheckoutDecisions) window._contractorCheckoutDecisions = {};
  window._contractorCheckoutDecisions[i] = decision;

  const row = document.getElementById('equip-row-' + i);
  const btnOut = document.getElementById('equip-btn-out-' + i);
  const btnStay = document.getElementById('equip-btn-stay-' + i);

  if (btnOut) { btnOut.classList.toggle('btn-success', decision === 'out'); }
  if (btnStay) { btnStay.classList.toggle('btn-danger', decision === 'stay'); }
  if (decision === 'out' && btnStay) btnStay.classList.remove('btn-danger');
  if (decision === 'stay' && btnOut) btnOut.classList.remove('btn-success');

  if (row) {
    row.style.borderColor = decision === 'out' ? 'var(--green)' : 'var(--amber)';
    row.style.background = decision === 'out' ? 'var(--green-light)' : 'var(--amber-light)';
  }

  const decided = Object.keys(window._contractorCheckoutDecisions).length;
  const counter = document.getElementById('equip-check-counter');
  if (counter) counter.textContent = decided + '/' + total;

  const btn = document.getElementById('btn-contractor-checkout-confirm');
  if (btn) {
    if (decided >= total) {
      btn.disabled = false;
      btn.style.opacity = '1';
      btn.style.cursor = 'pointer';
    } else {
      btn.disabled = true;
      btn.style.opacity = '.4';
      btn.style.cursor = 'not-allowed';
    }
  }
}

async function confirmContractorCheckout(id) {
  closeModal('modal-contractor-checkout');
  const visitors = DB.visitors();
  const v = visitors.find(x => x.id === id);
  if (!v) return;
  v.status = 'Checked Out';
  v.checkOut = now();
  DB.setVisitors(visitors);

  // Hanya alat yang dipilih "Ikut Keluar" yang diubah jadi Outside.
  // Alat yang dipilih "Tinggal" tetap berstatus Inside, dan masih bisa
  // di-checkout manual nanti lewat menu Equipment.
  const equipment = DB.equipment();
  const decisions = window._contractorCheckoutDecisions || {};
  const outIds = [];
  let outCount = 0, stayCount = 0;

  (v.equipList || []).forEach((eq, i) => {
    if (!eq.equipId) return;
    const decision = decisions[i] || 'out';
    const target = equipment.find(e => e.id === eq.equipId);
    if (!target) return;
    if (decision === 'out') {
      target.status = 'Outside';
      target.dateOut = v.checkOut;
      outIds.push(target.id);
      outCount++;
    } else {
      stayCount++; // tetap Inside, tidak diubah
    }
    if (!target.visitorId) target.visitorId = v.id;
  });

  DB.setEquipment(equipment);
  markLocalWrite();

  addAudit('CHECK_OUT', 'CONTRACTOR', `${v.name} — ${outCount} alat ikut keluar, ${stayCount} alat tertinggal di dalam — durasi: ${fmtDuration(v.checkIn, v.checkOut)}`);
  toast(v.name + ' berhasil check out' + (stayCount > 0 ? ` (${stayCount} alat tertinggal di dalam)` : ''), 'success');
  checkAlerts();
  renderPage(currentPage);

  sendToGAS('CHECKOUT_VISITOR', {
    id: id,
    checkOutTime: v.checkOut,
    durasi: fmtDuration(v.checkIn, v.checkOut),
    equipVerified: true,
    user: document.getElementById('sb-uname').textContent,
  });
  outIds.forEach(eid => {
    const e = equipment.find(x => x.id === eid);
    if (e) sendToGAS('EQUIPMENT_OUT', { id: e.id, dateOut: e.dateOut, user: document.getElementById('sb-uname').textContent });
  });
  sendLogToGAS('CHECK_OUT', 'CONTRACTOR', `${v.name} — ${outCount} alat keluar, ${stayCount} tertinggal — ${fmtDuration(v.checkIn, v.checkOut)}`);
  window._contractorCheckoutDecisions = {};
}
