    <!-- ── AUDIT TRAIL ── -->
    <div id="page-audit" style="display:none">
      <div class="search-wrap">
        <div class="search-input-wrap">
          <i class="ti ti-search"></i>
          <input type="text" class="search-input" id="audit-search" placeholder="Search audit trail..." oninput="renderAudit()">
        </div>
        <select id="audit-filter" onchange="renderAudit()" style="width:160px">
          <option value="">All Actions</option>
          <option value="CHECK_IN">Check In</option>
          <option value="CHECK_OUT">Check Out</option>
          <option value="CREATE">Create</option>
          <option value="UPDATE">Update</option>
          <option value="DELETE">Delete</option>
        </select>
        <button class="btn btn-sm btn-danger" onclick="clearAudit()"><i class="ti ti-trash"></i> Clear All</button>
      </div>
      <div class="card" style="padding:0">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border)">
          <span style="font-size:13px;font-weight:500">Audit Log</span>
          <span id="audit-count" style="font-size:12px;color:var(--text3);margin-left:8px">0 records</span>
        </div>
        <div style="padding:0 16px" id="audit-list"></div>
      </div>
    </div>
