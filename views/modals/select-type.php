<!-- MODAL PILIH TIPE CHECK IN -->
<div class="modal-bg" id="modal-select-type">
  <div class="modal" style="max-width:420px">
    <div class="modal-title">
      <span><i class="ti ti-user-plus" style="color:var(--blue);margin-right:8px"></i>Pilih Tipe Pengunjung</span>
      <button class="modal-close" onclick="closeModal('modal-select-type')"><i class="ti ti-x"></i></button>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding-bottom:8px">
      <button class="btn" style="flex-direction:column;padding:16px;gap:6px;height:auto" onclick="closeModal('modal-select-type');openModal('modal-checkin-guest')">
        <i class="ti ti-user" style="font-size:24px;color:var(--blue)"></i>
        <span style="font-size:13px;font-weight:600">Guest</span>
      </button>
      <button class="btn" style="flex-direction:column;padding:16px;gap:6px;height:auto" onclick="closeModal('modal-select-type');openModal('modal-checkin-contractor')">
        <i class="ti ti-hard-hat" style="font-size:24px;color:var(--amber)"></i>
        <span style="font-size:13px;font-weight:600">Contractor</span>
      </button>
      <button class="btn" style="flex-direction:column;padding:16px;gap:6px;height:auto" onclick="closeModal('modal-select-type');openModal('modal-checkin-driver')">
        <i class="ti ti-truck" style="font-size:24px;color:var(--green)"></i>
        <span style="font-size:13px;font-weight:600">Driver</span>
      </button>
      <button class="btn" style="flex-direction:column;padding:16px;gap:6px;height:auto" onclick="closeModal('modal-select-type');openModal('modal-checkin-employee')">
        <i class="ti ti-id-badge" style="font-size:24px;color:var(--navy3)"></i>
        <span style="font-size:13px;font-weight:600">Employee Test</span>
      </button>
      <button class="btn" style="grid-column:1/-1;flex-direction:column;padding:16px;gap:6px;height:auto" onclick="closeModal('modal-select-type');openModal('modal-checkin-auditor')">
        <i class="ti ti-clipboard-check" style="font-size:24px;color:var(--red)"></i>
        <span style="font-size:13px;font-weight:600">Auditor</span>
      </button>
    </div>
  </div>
</div>
