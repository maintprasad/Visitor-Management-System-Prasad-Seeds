<!-- CHECK IN MODAL — GUEST -->
<div class="modal-bg" id="modal-checkin-guest">
  <div class="modal">
    <div class="modal-title">
      <span><i class="ti ti-user" style="color:var(--blue);margin-right:8px"></i>Check In — Guest</span>
      <button class="modal-close" onclick="closeModal('modal-checkin-guest')"><i class="ti ti-x"></i></button>
    </div>
    <div class="form-grid">
      <div class="form-group full">
        <label>Nama Pengunjung * <span style="font-weight:400;color:var(--text3)">(bisa lebih dari 1 orang, tetap 1 ID)</span></label>
        <div id="g-nama-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px"></div>
        <button type="button" class="btn btn-sm" onclick="addGuestNamaRow()">
          <i class="ti ti-plus"></i> Tambah Nama
        </button>
      </div>
      <div class="form-group"><label>Perusahaan / Instansi *</label><input type="text" id="g-company" placeholder="PT / CV / Instansi"></div>
      <div class="form-group"><label>No. HP</label><input type="text" id="g-phone" placeholder="08xxxxxxxxxx"></div>
      <div class="form-group"><label>No. KTP / Passport</label><input type="text" id="g-identity" placeholder="No. identitas"></div>
      <div class="form-group full"><label>Tujuan Kunjungan *</label>
        <select id="g-purpose">
          <option value="">— Pilih tujuan —</option>
          <option>Meeting / Rapat</option><option>Presentasi</option><option>Kunjungan Bisnis</option><option>Lainnya</option>
        </select>
      </div>
      <div class="form-group"><label>Host / PIC *</label><input type="text" id="g-host" placeholder="Nama pegawai yang dikunjungi"></div>
      <div class="form-group"><label>Department</label>
        <select id="g-dept"><option value="">— Pilih dept —</option><option>Operation</option><option>HR / HRD</option><option>Maintenance</option><option>IT</option><option>Management</option><option>HSE</option><option>Security</option></select>
      </div>
      <div class="form-group full"><label>Catatan</label><textarea id="g-notes" placeholder="Catatan tambahan..."></textarea></div>
      <div class="form-group full"><label>Status Awal</label>
        <select id="g-status"><option value="Inside">Langsung Check In (Inside)</option><option value="Waiting Approval">Waiting Approval</option></select>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" onclick="closeModal('modal-checkin-guest')">Batal</button>
      <button class="btn btn-primary" onclick="submitCheckinTyped('Guest')"><i class="ti ti-check"></i> Check In</button>
    </div>
  </div>
</div>
