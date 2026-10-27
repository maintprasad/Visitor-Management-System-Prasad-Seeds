<!-- ABSEN KONTRAKTOR MODAL -->
<div class="modal-bg" id="modal-absen-kontraktor">
  <div class="modal" style="max-width:640px">
    <div class="modal-title">
      <span><i class="ti ti-clipboard-list" style="color:var(--blue);margin-right:8px"></i>Absen Kontraktor</span>
      <button class="modal-close" onclick="closeModal('modal-absen-kontraktor')"><i class="ti ti-x"></i></button>
    </div>
    <div class="form-grid">
      <div class="form-group full">
        <label>Nama PT *</label>
        <input type="text" id="absen-pt" placeholder="Nama perusahaan kontraktor">
      </div>
      <div class="form-group full">
        <label>Supervisi / Penanggung Jawab *</label>
        <input type="text" id="absen-spv" placeholder="Nama supervisi / penanggung jawab">
      </div>

      <!-- DAFTAR PEKERJA -->
      <div class="form-group full">
        <label>Daftar Nama Pekerja *</label>
        <div id="absen-pekerja-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px"></div>
        <button type="button" class="btn btn-sm" onclick="addPekerjaRow()">
          <i class="ti ti-plus"></i> Tambah Pekerja
        </button>
      </div>

      <!-- DAFTAR ALAT -->
      <div class="form-group full">
        <label>Daftar Alat Dibawa</label>
        <div id="absen-alat-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px"></div>
        <button type="button" class="btn btn-sm" onclick="addAlatRow()">
          <i class="ti ti-plus"></i> Tambah Alat
        </button>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" onclick="closeModal('modal-absen-kontraktor')">Batal</button>
      <button class="btn btn-primary" onclick="submitAbsenKontraktor()"><i class="ti ti-check"></i> Simpan</button>
    </div>
  </div>
</div>
