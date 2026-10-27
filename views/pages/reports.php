    <!-- ── REPORTS ── -->
    <div id="page-reports" style="display:none">
      <div class="tabs">
        <div class="tab active" onclick="switchTab('reports','daily',this)">Daily</div>
        <div class="tab" onclick="switchTab('reports','weekly',this)">Weekly</div>
        <div class="tab" onclick="switchTab('reports','monthly',this)">Monthly</div>
        <div class="tab" onclick="switchTab('reports','freq',this)">Frequent Visitors</div>
      </div>

      <div id="report-daily" class="tab-pane active">
        <div class="card">
          <div class="card-title">
            <span class="card-title-left"><i class="ti ti-calendar-day"></i> Daily Visitor Report</span>
            <div class="flex-gap8">
              <input type="date" id="report-date" style="width:160px" onchange="renderReportDaily()">
              <button class="btn btn-sm" onclick="exportReport('daily')"><i class="ti ti-download"></i> Export CSV</button>
              <button class="btn btn-sm btn-primary" onclick="exportExcelAllTypes()"><i class="ti ti-file-spreadsheet"></i> Export Excel</button>
            </div>
          </div>
          <div class="table-wrap"><table>
            <thead><tr><th>#</th><th>Name</th><th>Company</th><th>Host</th><th>Purpose</th><th>Check-in</th><th>Check-out</th><th>Duration</th><th>Status</th></tr></thead>
            <tbody id="report-daily-table"></tbody>
          </table></div>
        </div>
      </div>

      <div id="report-weekly" class="tab-pane">
        <div class="card">
          <div class="card-title"><span class="card-title-left"><i class="ti ti-calendar-week"></i> Weekly Summary</span></div>
          <div id="weekly-chart" class="chart-bars"></div>
          <div class="table-wrap mt12"><table>
            <thead><tr><th>Day</th><th>Total Visitors</th><th>Checked Out</th><th>Still Inside</th></tr></thead>
            <tbody id="weekly-table"></tbody>
          </table></div>
        </div>
      </div>

      <div id="report-monthly" class="tab-pane">
        <div class="card">
          <div class="card-title"><span class="card-title-left"><i class="ti ti-calendar-month"></i> Monthly Summary</span>
            <select id="report-month" onchange="renderReportMonthly()" style="width:140px">
            </select>
          </div>
          <div class="table-wrap"><table>
            <thead><tr><th>Date</th><th>Total</th><th>Checked Out</th><th>Inside</th><th>Overstay</th></tr></thead>
            <tbody id="monthly-table"></tbody>
          </table></div>
        </div>
      </div>

      <div id="report-freq" class="tab-pane">
        <div class="card">
          <div class="card-title"><span class="card-title-left"><i class="ti ti-repeat"></i> Most Frequent Visitors</span></div>
          <div class="table-wrap"><table>
            <thead><tr><th>Rank</th><th>Name</th><th>Company</th><th>Total Visits</th><th>Last Visit</th></tr></thead>
            <tbody id="freq-table"></tbody>
          </table></div>
        </div>
      </div>
    </div>
