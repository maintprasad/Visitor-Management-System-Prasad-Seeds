<!-- CHECK IN MODAL — AUDITOR -->
<div class="modal-bg" id="modal-checkin-auditor">
  <div class="modal">
    <div class="modal-title">
      <span><i class="ti ti-clipboard-check" style="color:var(--red);margin-right:8px"></i>Check In — Auditor</span>
      <button class="modal-close" onclick="closeModal('modal-checkin-auditor')"><i class="ti ti-x"></i></button>
    </div>
    <div class="form-grid">
      <div class="form-group full">
        <label>Nama Pengunjung * <span style="font-weight:400;color:var(--text3)">(bisa lebih dari 1 orang, tetap 1 ID)</span></label>
        <div id="aud-nama-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px"></div>
        <button type="button" class="btn btn-sm" onclick="addAuditorNamaRow()">
          <i class="ti ti-plus"></i> Tambah Nama
        </button>
      </div>
      <div class="form-group"><label>Instansi / Lembaga *</label><input type="text" id="aud-company" placeholder="Nama instansi / lembaga audit"></div>
      <div class="form-group"><label>No. HP</label><input type="text" id="aud-phone" placeholder="08xxxxxxxxxx"></div>
      <div class="form-group"><label>No. KTP</label><input type="text" id="aud-identity" placeholder="No. identitas"></div>
      <div class="form-group"><label>Jenis Audit *</label>
        <select id="aud-audittype">
          <option value="">— Pilih jenis audit —</option>
          <option>Audit Internal</option><option>Audit Eksternal</option><option>Audit ISO</option><option>Audit Pemerintah</option><option>Audit Pelanggan</option><option>Lainnya</option>
        </select>
      </div>
      <div class="form-group"><label>Scope Audit</label><input type="text" id="aud-scope" placeholder="Ruang lingkup audit"></div>
      <div class="form-group"><label>Host / PIC *</label><input type="text" id="aud-host" placeholder="Nama PIC internal"></div>
      <div class="form-group"><label>Department</label>
        <select id="aud-dept"><option value="">— Pilih dept —</option><option>Operation</option><option>HR / HRD</option><option>Maintenance</option><option>IT</option><option>Management</option><option>HSE</option><option>Security</option></select>
      </div>
      <div class="form-group full"><label>No. Surat Tugas</label><input type="text" id="aud-surat" placeholder="Nomor surat tugas / assignment letter"></div>
      <div class="form-group full"><label>Catatan</label><textarea id="aud-notes" placeholder="Catatan tambahan..."></textarea></div>
      <div class="form-group full"><label>Status Awal</label>
        <select id="aud-status"><option value="Inside">Langsung Check In (Inside)</option><option value="Waiting Approval">Waiting Approval</option></select>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" onclick="closeModal('modal-checkin-auditor')">Batal</button>
      <button class="btn btn-primary" onclick="submitCheckinTyped('Auditor')"><i class="ti ti-check"></i> Check In</button>
    </div>
  </div>
</div>
