    <!-- ── EQUIPMENT ── -->
    <div id="page-equipment" style="display:none">
      <div class="tabs">
        <div class="tab active" onclick="switchTab('equipment','all',this)">All Equipment</div>
        <div class="tab" onclick="switchTab('equipment','inside',this)">Inside</div>
        <div class="tab" onclick="switchTab('equipment','outside',this)">Outside</div>
        <div class="tab" onclick="switchTab('equipment','pending',this)">Pending</div>
      </div>
      <div class="search-wrap">
        <div class="search-input-wrap">
          <i class="ti ti-search"></i>
          <input type="text" class="search-input" id="equip-search" placeholder="Search equipment..." oninput="debounce(renderEquipmentTable,220)()">
        </div>
        <button class="btn btn-primary" onclick="populateEquipContractorLink();openModal('modal-equipment')"><i class="ti ti-plus"></i> Add Equipment</button>
      </div>
      <div class="card" style="padding:0">
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Equipment</th><th>Serial No.</th><th>Owner</th><th>Visitor ID</th><th>Gate Pass</th><th>Condition</th><th>Status</th><th>Date In</th><th>Actions</th></tr></thead>
            <tbody id="equipment-table"></tbody>
          </table>
        </div>
      </div>
    </div>
