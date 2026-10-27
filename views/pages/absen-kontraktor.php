    <!-- ── CONTRACTOR ATTENDANCE ── -->
    <div id="page-absen-kontraktor" style="display:none">
      <div class="search-wrap">
        <div class="search-input-wrap">
          <i class="ti ti-search"></i>
          <input type="text" class="search-input" id="absen-search" placeholder="Cari nama PT atau supervisi..." oninput="debounce(renderAbsenTable,220)()">
        </div>
        <button class="btn btn-primary" onclick="openModal('modal-absen-kontraktor')"><i class="ti ti-plus"></i> Tambah Absen</button>
      </div>
      <div class="card" style="padding:0">
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Nama PT</th><th>Supervisi / PJ</th><th>Jumlah Pekerja</th><th>Jumlah Alat</th><th>Tanggal</th><th>Actions</th></tr></thead>
            <tbody id="absen-table"></tbody>
          </table>
        </div>
      </div>
    </div>
