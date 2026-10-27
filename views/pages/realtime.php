    <!-- ── REAL-TIME MONITOR ── -->
    <div id="page-realtime" style="display:none">
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-user" style="color:var(--green)"></i> Visitors Inside</div>
          <div class="stat-val c-green" id="rt-inside">0</div>
          <div class="stat-sub">Live count</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-clock" style="color:var(--amber)"></i> Longest Duration</div>
          <div class="timer" id="rt-longest" style="font-size:22px;font-weight:600">00:00:00</div>
          <div class="stat-sub" id="rt-longest-name">—</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-alert-triangle" style="color:var(--red)"></i> Overstay</div>
          <div class="stat-val c-red" id="rt-overstay">0</div>
          <div class="stat-sub">Over 4 hours</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-package" style="color:var(--blue)"></i> Equipment Inside</div>
          <div class="stat-val c-blue" id="rt-equip">0</div>
          <div class="stat-sub">Active</div>
        </div>
      </div>
      <div class="card">
        <div class="card-title"><span class="card-title-left"><i class="ti ti-activity"></i> Live Visitor Monitor</span>
          <span style="font-size:11px;color:var(--green);display:flex;align-items:center;gap:4px">
            <span style="width:7px;height:7px;background:var(--green);border-radius:50%;animation:pulse 1.5s infinite"></span> Live
          </span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Visitor</th><th>Company</th><th>Host / PIC</th><th>Check-in Time</th><th>Live Duration</th><th>Status</th><th>Action</th></tr></thead>
            <tbody id="rt-table"></tbody>
          </table>
        </div>
      </div>
    </div>
