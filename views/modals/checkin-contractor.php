<!-- CHECK IN MODAL — CONTRACTOR -->
<div class="modal-bg" id="modal-checkin-contractor">
  <div class="modal" style="max-width:640px">
    <div class="modal-title">
      <span><i class="ti ti-hard-hat" style="color:var(--amber);margin-right:8px"></i>Check In — Contractor</span>
      <button class="modal-close" onclick="closeModal('modal-checkin-contractor')"><i class="ti ti-x"></i></button>
    </div>
    <div class="form-grid">
      <div class="form-group"><label>Nama Lengkap *</label><input type="text" id="con-name" placeholder="Nama lengkap"></div>
      <div class="form-group"><label>Perusahaan Kontraktor *</label><input type="text" id="con-company" placeholder="Nama perusahaan kontraktor"></div>
      <div class="form-group"><label>No. HP</label><input type="text" id="con-phone" placeholder="08xxxxxxxxxx"></div>
      <div class="form-group"><label>No. KTP</label><input type="text" id="con-identity" placeholder="No. identitas"></div>

      <div class="form-group"><label>Area Kerja *</label>
        <select id="con-area">
          <option value="">— Pilih area —</option>
          <option>CTP-1</option><option>CTP-2</option><option>Cold Storage</option>
          <option>Peanut</option><option>PS Drier</option><option>SPR Drier</option>
          <option>Utility</option><option>HSE</option>
        </select>
      </div>
      <div class="form-group"><label>Jenis Pekerjaan *</label>
        <select id="con-purpose">
          <option value="">— Pilih jenis —</option>
          <option>Civil</option><option>Electric</option><option>Mechanic</option><option>HR</option>
        </select>
      </div>
      <div class="form-group"><label>No. SPK / PO</label><input type="text" id="con-spk" placeholder="Nomor SPK atau PO"></div>
      <div class="form-group full">
        <label>Daftar Nama Pekerja * <span style="font-weight:400;color:var(--text3)">(Jumlah personil otomatis terhitung)</span></label>
        <div id="con-pekerja-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px"></div>
        <button type="button" class="btn btn-sm" onclick="addConPekerjaRow()">
          <i class="ti ti-plus"></i> Tambah Pekerja
        </button>
      </div>

      <div class="form-group"><label>Host / PIC *</label><input type="text" id="con-host" placeholder="Nama PIC internal"></div>
      <div class="form-group"><label>Department</label>
        <select id="con-dept"><option value="">— Pilih dept —</option><option>Operation</option><option>HR / HRD</option><option>Maintenance</option><option>IT</option><option>Management</option><option>HSE</option><option>Security</option></select>
      </div>
      <div class="form-group"><label>Tanggal Mulai</label><input type="date" id="con-start"></div>
      <div class="form-group"><label>Tanggal Selesai</label><input type="date" id="con-end"></div>

      <!-- PERALATAN DIBAWA -->
      <div class="form-group full">
        <label>Peralatan Dibawa</label>
        <div id="con-equipment-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px"></div>
        <button type="button" class="btn btn-sm" onclick="addEquipmentRow()">
          <i class="ti ti-plus"></i> Tambah Peralatan
        </button>
      </div>

      <div class="form-group full"><label>Catatan</label><textarea id="con-notes" placeholder="Catatan tambahan..."></textarea></div>
      <div class="form-group full"><label>Status Awal</label>
        <select id="con-status">
          <option value="Inside">Langsung Check In (Inside)</option>
          <option value="Waiting Approval">Waiting Approval</option>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" onclick="closeModal('modal-checkin-contractor')">Batal</button>
      <button class="btn btn-primary" onclick="submitCheckinTyped('Contractor')"><i class="ti ti-check"></i> Check In</button>
    </div>
  </div>
</div>
