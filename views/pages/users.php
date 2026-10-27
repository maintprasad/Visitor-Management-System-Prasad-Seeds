    <!-- ── USER MANAGEMENT (admin) ── -->
    <div id="page-users" style="display:none">
      <div class="info-banner-lite" style="margin-bottom:14px;font-size:13px;color:var(--text2)">
        Akun di sini dipakai login <b>Main Panel</b> dan aplikasi <b>Visitor Check-Out</b>.
        Akun <b>Security</b> terkunci ke satu unit; akun <b>Admin</b> bisa berpindah unit dan mengelola user.
      </div>
      <div class="search-wrap">
        <div class="search-input-wrap">
          <i class="ti ti-search"></i>
          <input type="text" class="search-input" id="user-search" placeholder="Cari nama atau username..." oninput="renderUsers()">
        </div>
        <select id="user-filter-unit" onchange="renderUsers()" style="width:150px">
          <option value="">Semua Unit</option>
        </select>
        <button class="btn btn-primary" onclick="openUserModal()"><i class="ti ti-user-plus"></i> Tambah User</button>
      </div>
      <div class="card" style="padding:0">
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Nama</th><th>Username</th><th>Role</th><th>Unit</th><th>Status</th><th>Login Terakhir</th><th>Actions</th></tr></thead>
            <tbody id="user-table"></tbody>
          </table>
        </div>
      </div>
    </div>
