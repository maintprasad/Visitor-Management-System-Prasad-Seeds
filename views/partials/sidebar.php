<!-- ════════ SIDEBAR ════════ -->
<div class="sidebar" id="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-wrap">
      <div class="sb-logo-icon"><i class="ti ti-shield-check"></i></div>
      <div>
        <div class="sb-logo-title">Visitor System</div>
        <div class="sb-logo-sub">Security Management</div>
      </div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Main</div>
    <div class="sb-item active" onclick="nav('dashboard',this)"><i class="ti ti-layout-dashboard"></i> Dashboard</div>
    <div class="sb-item" onclick="nav('visitors',this)"><i class="ti ti-users"></i> Visitor Management <span class="sb-badge" id="badge-visitors">0</span></div>
    <div class="sb-item" onclick="nav('equipment',this)"><i class="ti ti-package"></i> Equipment</div>
    <div class="sb-section">Monitoring</div>
    <div class="sb-item" onclick="nav('realtime',this)"><i class="ti ti-activity"></i> Real-time Monitor</div>
    <div class="sb-item" onclick="nav('alerts',this)"><i class="ti ti-bell"></i> Alerts <span class="sb-badge" id="badge-alerts">0</span></div>
    <div class="sb-item" onclick="nav('emergency',this)"><i class="ti ti-alert-triangle"></i> Emergency</div>
    <div class="sb-section">Records</div>
    <div class="sb-item" onclick="nav('absen-kontraktor',this)"><i class="ti ti-clipboard-list"></i> Contractor Attendance</div>
    <div class="sb-item" onclick="nav('qr',this)"><i class="ti ti-qrcode"></i> QR System</div>
    <div class="sb-item" onclick="nav('reports',this)"><i class="ti ti-chart-bar"></i> Reports</div>
    <div class="sb-item" onclick="nav('audit',this)"><i class="ti ti-history"></i> Audit Trail</div>
  </nav>
  <div class="sb-user">
    <div class="sb-avatar" id="sb-avatar-text">AR</div>
    <div>
      <div class="sb-uname" id="sb-uname">—</div>
      <div class="sb-urole" id="sb-urole">—</div>
    </div>
  </div>
  <div class="sb-session">
    <select id="unit-switch" class="sb-unit-switch" style="display:none" onchange="Session.switchUnit(this.value)" title="Pindah unit (admin)"></select>
    <button type="button" class="sb-logout" onclick="Session.logout()"><i class="ti ti-logout-2"></i> Keluar</button>
  </div>
</div>
