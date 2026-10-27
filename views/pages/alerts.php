    <!-- ── ALERTS ── -->
    <div id="page-alerts" style="display:none">
      <div class="card">
        <div class="card-title"><span class="card-title-left"><i class="ti ti-bell"></i> Alert Management</span>
          <button class="btn btn-sm" onclick="checkAlerts(); renderAlerts(); toast('Alerts refreshed','info')"><i class="ti ti-refresh"></i> Refresh</button>
        </div>
        <div id="alerts-list"></div>
      </div>
    </div>
