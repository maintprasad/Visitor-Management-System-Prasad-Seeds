  <!-- TOPBAR -->
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:10px">
      <img id="company-logo" src="<?= e(asset('img/logo.png')) ?>" 
           alt="Logo" style="height:36px;width:auto;object-fit:contain;flex-shrink:0">
      <div>
        <div class="topbar-title" id="page-title">Dashboard Overview</div>
        <div class="topbar-sub" id="page-sub">Live monitoring · Real-time</div>
      </div>
      <span id="unit-badge" class="unit-badge" style="display:none"></span>
    </div>
    <div class="topbar-actions" id="topbar-actions">
      <span id="sync-badge" class="pill pill-overstay" style="display:none;cursor:pointer" onclick="retryPendingSync()" title="Klik untuk coba sync ulang sekarang"></span>
      <button class="btn btn-primary" onclick="openModal('modal-select-type')"><i class="ti ti-user-plus"></i> Check In Visitor</button>
    </div>
</div>
