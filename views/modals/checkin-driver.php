<!-- CHECK IN MODAL — DRIVER (4 isian, langsung Inside) -->
<div class="modal-bg" id="modal-checkin-driver">
  <div class="modal">
    <div class="modal-title">
      <span><i class="ti ti-truck" style="color:var(--green);margin-right:8px"></i>Check In — Driver</span>
      <button class="modal-close" onclick="closeModal('modal-checkin-driver')"><i class="ti ti-x"></i></button>
    </div>
    <div class="form-grid">
      <div class="form-group full"><label>Nama Driver *</label><input type="text" id="drv-name" placeholder="Nama lengkap driver"></div>
      <div class="form-group full"><label>Nama Transporter *</label><input type="text" id="drv-company" placeholder="Nama transporter / ekspedisi"></div>
      <div class="form-group"><label>Nomor Polisi Kendaraan *</label><input type="text" id="drv-plate" placeholder="B 1234 XX" style="text-transform:uppercase"></div>
      <div class="form-group"><label>Jenis Kendaraan *</label>
        <select id="drv-vehicle">
          <option value="">— Pilih jenis —</option>
          <option>Wing Box</option>
          <option>Container</option>
          <option>Colt Diesel</option>
          <option>Pick Up</option>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" onclick="closeModal('modal-checkin-driver')">Batal</button>
      <button class="btn btn-primary" onclick="submitCheckinTyped('Driver')"><i class="ti ti-check"></i> Check In</button>
    </div>
  </div>
</div>
