    <!-- ── DASHBOARD ── -->
    <div id="page-dashboard">
      <!-- Row 1: summary -->
      <div class="stat-grid" style="grid-template-columns: repeat(4,1fr)">
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-users" style="color:var(--blue)"></i> Total Inside</div>
          <div class="stat-val c-blue" id="stat-inside">0</div>
          <div class="stat-sub">Semua tipe aktif</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-package" style="color:var(--blue)"></i> Active Equipment</div>
          <div class="stat-val c-blue" id="stat-equip">0</div>
          <div class="stat-sub">Inside factory</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-alert-circle" style="color:var(--red)"></i> Overstay Alerts</div>
          <div class="stat-val c-red" id="stat-overstay">0</div>
          <div class="stat-sub">Needs attention</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-calendar" style="color:var(--amber)"></i> Today's Visitors</div>
          <div class="stat-val c-amber" id="stat-today">0</div>
          <div class="stat-sub">Total check-ins today</div>
        </div>
      </div>
      <!-- Row 2: per type -->
      <div class="stat-grid" style="grid-template-columns: repeat(5,1fr); margin-top:-4px">
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-user" style="color:var(--blue)"></i> Guest</div>
          <div class="stat-val c-blue" id="stat-guest">0</div>
          <div class="stat-sub">Inside</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-hard-hat" style="color:var(--amber)"></i> Contractor</div>
          <div class="stat-val c-amber" id="stat-contractor">0</div>
          <div class="stat-sub">Inside</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-truck" style="color:var(--green)"></i> Driver</div>
          <div class="stat-val c-green" id="stat-driver">0</div>
          <div class="stat-sub">Inside</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-id-badge" style="color:var(--navy3)"></i> Employee Test</div>
          <div class="stat-val" id="stat-employee" style="color:var(--navy3)">0</div>
          <div class="stat-sub">Inside</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="ti ti-clipboard-check" style="color:var(--red)"></i> Auditor</div>
          <div class="stat-val c-red" id="stat-auditor">0</div>
          <div class="stat-sub">Inside</div>
        </div>
      </div>

      <div class="row2">
        <div class="card">
          <div class="card-title"><span class="card-title-left"><i class="ti ti-chart-bar"></i> Visitor Trend (7 days)</span></div>
          <div class="chart-bars" id="dash-chart-visitors"></div>
        </div>
        <div class="card">
          <div class="card-title"><span class="card-title-left"><i class="ti ti-bell"></i> Recent Alerts</span></div>
          <div id="dash-alerts"></div>
        </div>
      </div>

      <div class="card mt12">
        <div class="card-title">
          <span class="card-title-left"><i class="ti ti-users"></i> Active Visitors</span>
          <button class="btn btn-sm" onclick="nav('visitors', document.querySelector('[onclick*=visitors]'))"><i class="ti ti-external-link"></i> View All</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Visitor</th><th>Company</th><th>Host / PIC</th><th>Purpose</th><th>Check-in</th><th>Duration</th><th>Status</th><th>Action</th></tr></thead>
            <tbody id="dash-visitor-table"></tbody>
          </table>
        </div>
      </div>
    </div>
