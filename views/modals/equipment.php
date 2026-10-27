<!-- EQUIPMENT MODAL -->
<div class="modal-bg" id="modal-equipment">
  <div class="modal">
    <div class="modal-title">
      <span><i class="ti ti-package" style="color:var(--blue);margin-right:8px"></i>Add Equipment</span>
      <button class="modal-close" onclick="closeModal('modal-equipment')"><i class="ti ti-x"></i></button>
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label>Equipment Name *</label>
        <input type="text" id="eq-name" placeholder="Nama equipment">
      </div>
      <div class="form-group">
        <label>Serial Number</label>
        <input type="text" id="eq-serial" placeholder="SN-XXXX">
      </div>
      <div class="form-group full">
        <label>Tautkan ke Contractor (Inside)</label>
        <select id="eq-link-contractor" onchange="autofillEquipOwner()">
          <option value="">— Tidak ditautkan / equipment umum —</option>
        </select>
      </div>
      <div class="form-group">
        <label>Owner / Pembawa *</label>
        <input type="text" id="eq-owner" placeholder="Nama pembawa">
      </div>
      <div class="form-group">
        <label>Gate Pass Number</label>
        <input type="text" id="eq-gatepass" placeholder="GP-YYYYMMDD-XXX">
      </div>
      <div class="form-group">
        <label>Condition / Kondisi</label>
        <select id="eq-condition">
          <option>Good / Baik</option>
          <option>Minor scratch</option>
          <option>Damaged</option>
        </select>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select id="eq-status">
          <option value="Inside">Inside (Dibawa masuk)</option>
          <option value="Pending Approval">Pending Approval</option>
          <option value="Outside">Outside (Dibawa keluar)</option>
        </select>
      </div>
      <div class="form-group full">
        <label>Notes</label>
        <textarea id="eq-notes" placeholder="Catatan equipment..."></textarea>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" onclick="closeModal('modal-equipment')">Batal</button>
      <button class="btn btn-primary" onclick="submitEquipment()"><i class="ti ti-check"></i> Save</button>
    </div>
  </div>
</div>
