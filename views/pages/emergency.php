    <!-- ── EMERGENCY ── -->
    <div id="page-emergency" style="display:none">
      <div class="emergency-banner">
        <i class="ti ti-alert-triangle"></i>
        EMERGENCY DASHBOARD — All visitors currently inside factory
        <button class="btn btn-sm" onclick="exportEmergency()" style="margin-left:auto;background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3);color:#fff"><i class="ti ti-download"></i> Export PDF</button>
      </div>
      <div class="card">
        <div class="card-title"><span class="card-title-left"><i class="ti ti-users"></i> Muster List</span>
          <span id="emergency-count" style="font-size:13px;color:var(--text3)">0 visitors inside</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Name</th><th>Company</th><th>Host / PIC</th><th>Check-in Time</th><th>Current Duration</th><th>Status</th></tr></thead>
            <tbody id="emergency-table"></tbody>
          </table>
        </div>
      </div>
    </div>
