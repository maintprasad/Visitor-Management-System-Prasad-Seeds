<!-- USER MODAL (tambah / ubah) -->
<div class="modal-bg" id="modal-user">
  <div class="modal">
    <div class="modal-title">
      <span><i class="ti ti-user-cog" style="color:var(--blue);margin-right:8px"></i><span id="user-modal-title">Tambah User</span></span>
      <button class="modal-close" onclick="closeModal('modal-user')"><i class="ti ti-x"></i></button>
    </div>
    <input type="hidden" id="user-id">
    <div class="form-grid">
      <div class="form-group full"><label>Nama Lengkap *</label><input type="text" id="user-name" placeholder="Nama petugas"></div>
      <div class="form-group"><label>Username *</label><input type="text" id="user-username" placeholder="mis. security.u1" autocomplete="off" autocapitalize="none"></div>
      <div class="form-group"><label>Role *</label>
        <select id="user-role" onchange="syncUserUnitField()">
          <option value="security">Security</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="form-group" id="user-unit-group"><label id="user-unit-label">Unit *</label>
        <select id="user-unit"></select>
      </div>
      <div class="form-group" id="user-status-group"><label>Status</label>
        <select id="user-status">
          <option value="ACTIVE">Aktif</option>
          <option value="DISABLED">Nonaktif</option>
        </select>
      </div>
      <div class="form-group full"><label id="user-pass-label">Password * <span style="font-weight:400;color:var(--text3)">(minimal 8 karakter)</span></label>
        <input type="password" id="user-password" autocomplete="new-password" placeholder="Password">
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" onclick="closeModal('modal-user')">Batal</button>
      <button class="btn btn-primary" id="user-save-btn" onclick="saveUser()"><i class="ti ti-check"></i> Simpan</button>
    </div>
  </div>
</div>
