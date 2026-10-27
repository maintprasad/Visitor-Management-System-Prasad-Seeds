<!-- ════════ BOTTOM NAV (mobile) ════════ -->
<nav class="bottom-nav" id="bottom-nav">
  <div class="bn-item active" id="bn-dashboard" onclick="nav('dashboard',null);setBottomNav('dashboard')">
    <i class="ti ti-layout-dashboard"></i>
    <span>Dashboard</span>
  </div>
  <div class="bn-item" id="bn-visitors" onclick="nav('visitors',null);setBottomNav('visitors')">
    <i class="ti ti-users"></i>
    <span>Visitor</span>
    <span class="bn-badge" id="bn-badge-visitors" style="display:none">0</span>
  </div>
  <div class="bn-item" id="bn-checkin" onclick="openModal('modal-select-type')">
    <div style="width:46px;height:46px;background:var(--blue);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:-4px;box-shadow:0 4px 14px rgba(37,99,235,.4)">
      <i class="ti ti-plus" style="font-size:24px;color:#fff"></i>
    </div>
    <span>Check In</span>
  </div>
  <div class="bn-item" id="bn-realtime" onclick="nav('realtime',null);setBottomNav('realtime')">
    <i class="ti ti-activity"></i>
    <span>Live</span>
  </div>
  <div class="bn-item" id="bn-more" onclick="toggleSidebar()">
    <i class="ti ti-menu-2"></i>
    <span>Menu</span>
  </div>
</nav>
