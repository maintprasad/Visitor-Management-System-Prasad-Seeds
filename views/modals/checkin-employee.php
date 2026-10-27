<!-- CHECK IN MODAL — EMPLOYEE TEST -->
<div class="modal-bg" id="modal-checkin-employee">
  <div class="modal">
    <div class="modal-title">
      <span><i class="ti ti-id-badge" style="color:var(--navy3);margin-right:8px"></i>Check In — Employee Test</span>
      <button class="modal-close" onclick="closeModal('modal-checkin-employee')"><i class="ti ti-x"></i></button>
    </div>
    <div class="form-grid">
      <div class="form-group"><label>Nama Lengkap *</label><input type="text" id="emp-name" placeholder="Nama lengkap"></div>
      <div class="form-group"><label>Dept / Divisi *</label>
        <select id="emp-dept"><option value="">— Pilih dept —</option><option>Operation</option><option>HR / HRD</option><option>Maintenance</option><option>IT</option><option>Management</option><option>HSE</option><option>Security</option></select>
      </div>
      <div class="form-group"><label>Jabatan</label><input type="text" id="emp-jabatan" placeholder="Jabatan / posisi"></div>
      <div class="form-group full"><label>Keperluan *</label>
        <select id="emp-purpose">
          <option value="">— Pilih keperluan —</option>
          <option>Tes Kompetensi</option><option>Tes Kesehatan</option><option>Orientasi Karyawan Baru</option><option>Pelatihan Internal</option><option>Lainnya</option>
        </select>
      </div>
      <div class="form-group"><label>PIC / Supervisor *</label><input type="text" id="emp-host" placeholder="Nama supervisor / PIC"></div>
      <div class="form-group full"><label>Catatan</label><textarea id="emp-notes" placeholder="Catatan tambahan..."></textarea></div>
      <div class="form-group full"><label>Status Awal</label>
        <select id="emp-status"><option value="Inside">Langsung Check In (Inside)</option><option value="Waiting Approval">Waiting Approval</option></select>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" onclick="closeModal('modal-checkin-employee')">Batal</button>
      <button class="btn btn-primary" onclick="submitCheckinTyped('Employee Test')"><i class="ti ti-check"></i> Check In</button>
    </div>
  </div>
</div>
