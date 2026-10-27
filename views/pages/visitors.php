    <!-- ── VISITOR MANAGEMENT ── -->
    <div id="page-visitors" style="display:none">
      <!-- Type tabs -->
      <div class="type-tabs" id="visitor-type-tabs">
        <div class="type-tab active" onclick="switchVisitorType('all',this)"><i class="ti ti-list"></i> Semua <span class="count" id="tc-all">0</span></div>
        <div class="type-tab" onclick="switchVisitorType('Guest',this)"><i class="ti ti-user"></i> Guest <span class="count" id="tc-guest">0</span></div>
        <div class="type-tab" onclick="switchVisitorType('Contractor',this)"><i class="ti ti-hard-hat"></i> Contractor <span class="count" id="tc-contractor">0</span></div>
        <div class="type-tab" onclick="switchVisitorType('Driver',this)"><i class="ti ti-truck"></i> Driver <span class="count" id="tc-driver">0</span></div>
        <div class="type-tab" onclick="switchVisitorType('Employee Test',this)"><i class="ti ti-id-badge"></i> Employee Test <span class="count" id="tc-employee">0</span></div>
        <div class="type-tab" onclick="switchVisitorType('Auditor',this)"><i class="ti ti-clipboard-check"></i> Auditor <span class="count" id="tc-auditor">0</span></div>
      </div>
      <!-- Status tabs -->
      <div class="tabs">
        <div class="tab active" onclick="switchTab('visitors','all',this)">Semua</div>
        <div class="tab" onclick="switchTab('visitors','inside',this)">Inside</div>
        <div class="tab" onclick="switchTab('visitors','today',this)">Today</div>
        <div class="tab" onclick="switchTab('visitors','out',this)">Checked Out</div>
      </div>
      <div class="search-wrap">
        <div class="search-input-wrap">
          <i class="ti ti-search"></i>
          <input type="text" class="search-input" id="visitor-search" placeholder="Search by name, company, or host..." oninput="debounce(renderVisitorTable,220)()">
        </div>
        <select id="visitor-filter-status" onchange="renderVisitorTable()" style="width:160px">
          <option value="">All Status</option>
          <option value="Inside">Inside</option>
          <option value="Waiting Approval">Waiting Approval</option>
          <option value="Checked Out">Checked Out</option>
          <option value="Overstay">Overstay</option>
        </select>
        <button class="btn btn-primary" onclick="openCheckinByType()"><i class="ti ti-plus"></i> Check In</button>
      </div>
      <div class="card" style="padding:0">
        <div class="table-wrap">
          <table>
            <thead id="visitor-thead"><tr><th>#</th><th>Visitor</th><th>Tipe</th><th>Company</th><th>Host / PIC</th><th>Check-in</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="visitor-table"></tbody>
          </table>
        </div>
      </div>
    </div>
