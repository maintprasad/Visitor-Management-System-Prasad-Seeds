// ══════════════════════════════════════════
//  USER MANAGEMENT (khusus admin) — tambah / ubah / reset password / nonaktifkan / hapus
//  Data diambil langsung dari server (bukan cache lokal), karena menyangkut akun.
// ══════════════════════════════════════════

let userList = [];
let userUnits = [];

function isAdmin() { return !!(Auth.user && Auth.user.role === 'admin'); }

async function loadUsers() {
  if (!isAdmin()) return;
  try {
    const res = await fetchGAS({ action: 'LIST_USERS' });
    userList = res.users;
    userUnits = res.units;
    const sel = document.getElementById('user-filter-unit');
    const keep = sel.value;
    sel.innerHTML = '<option value="">Semua Unit</option>' + userUnits.map(u => `<option value="${u.id}">${escapeHtml(u.name)}</option>`).join('');
    sel.value = keep;
    renderUsers();
  } catch (e) {
    toast('Gagal memuat user: ' + e.message, 'error');
  }
}

function escapeHtml(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function renderUsers() {
  const tbody = document.getElementById('user-table');
  if (!tbody) return;
  const q = (document.getElementById('user-search')?.value || '').toLowerCase();
  const unit = document.getElementById('user-filter-unit')?.value || '';
  const rows = userList.filter(u =>
    (!q || u.name.toLowerCase().includes(q) || u.username.toLowerCase().includes(q)) &&
    (!unit || u.unit === unit));

  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="8"><div class="empty"><i class="ti ti-users"></i><p>Belum ada user</p></div></td></tr>';
    return;
  }
  tbody.innerHTML = rows.map((u, i) => {
    const me = u.username === Auth.user.username;
    return `
    <tr>
      <td class="mono">${i + 1}</td>
      <td><div class="td-main">${escapeHtml(u.name)}${me ? ' <span class="td-sub">(Anda)</span>' : ''}</div></td>
      <td class="mono">${escapeHtml(u.username)}</td>
      <td>${u.role === 'admin' ? '<span class="pill pill-overstay">Admin</span>' : '<span class="pill pill-waiting">Security</span>'}</td>
      <td>${u.unitName ? escapeHtml(u.unitName) : '<span class="td-sub">Semua unit</span>'}</td>
      <td>${u.status === 'ACTIVE' ? '<span class="pill pill-inside">Aktif</span>' : '<span class="pill pill-out">Nonaktif</span>'}</td>
      <td class="mono">${u.lastLoginAt ? fmtDateTime(u.lastLoginAt) : '—'}</td>
      <td>
        <div class="flex-gap8">
          <button class="btn btn-sm" title="Ubah / reset password" onclick="openUserModal(${u.id})"><i class="ti ti-pencil"></i></button>
          <button class="btn btn-sm btn-danger" title="Hapus" ${me ? 'disabled' : ''} onclick="deleteUser(${u.id})"><i class="ti ti-trash"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function fillUnitSelect(selected) {
  const sel = document.getElementById('user-unit');
  const isSecurity = document.getElementById('user-role').value === 'security';
  sel.innerHTML = (isSecurity ? '' : '<option value="">Semua unit (bebas berpindah)</option>') +
    userUnits.map(u => `<option value="${u.id}">${escapeHtml(u.name)}</option>`).join('');
  sel.value = selected || (isSecurity && userUnits[0] ? userUnits[0].id : '');
}

function syncUserUnitField() {
  const isSecurity = document.getElementById('user-role').value === 'security';
  document.getElementById('user-unit-label').textContent = isSecurity ? 'Unit *' : 'Unit (opsional)';
  fillUnitSelect(document.getElementById('user-unit').value);
}

function openUserModal(id) {
  const u = id ? userList.find(x => x.id === id) : null;
  document.getElementById('user-modal-title').textContent = u ? 'Ubah User' : 'Tambah User';
  document.getElementById('user-id').value = u ? u.id : '';
  document.getElementById('user-name').value = u ? u.name : '';
  const un = document.getElementById('user-username');
  un.value = u ? u.username : '';
  un.disabled = !!u;                                   // username tidak bisa diganti
  document.getElementById('user-role').value = u ? u.role : 'security';
  fillUnitSelect(u ? u.unit : '');
  syncUserUnitField();
  if (u) document.getElementById('user-unit').value = u.unit || '';
  document.getElementById('user-status').value = u ? u.status : 'ACTIVE';
  document.getElementById('user-status-group').style.display = u ? '' : 'none';
  document.getElementById('user-password').value = '';
  document.getElementById('user-pass-label').innerHTML = u
    ? 'Password baru <span style="font-weight:400;color:var(--text3)">(kosongkan bila tidak diganti, minimal 8 karakter)</span>'
    : 'Password * <span style="font-weight:400;color:var(--text3)">(minimal 8 karakter)</span>';
  // Akun sendiri: role & status dikunci agar admin tidak mengunci dirinya sendiri.
  const self = u && u.username === Auth.user.username;
  document.getElementById('user-role').disabled = !!self;
  document.getElementById('user-status').disabled = !!self;
  openModal('modal-user');
}

async function saveUser() {
  const id = document.getElementById('user-id').value;
  const name = document.getElementById('user-name').value.trim();
  const username = document.getElementById('user-username').value.trim().toLowerCase();
  const role = document.getElementById('user-role').value;
  const unit = document.getElementById('user-unit').value;
  const password = document.getElementById('user-password').value;

  if (!name || (!id && !username)) { toast('Lengkapi nama dan username', 'error'); return; }
  if (role === 'security' && !unit) { toast('Akun security wajib memiliki unit', 'error'); return; }
  if (!id && password.length < 8) { toast('Password minimal 8 karakter', 'error'); return; }
  if (id && password && password.length < 8) { toast('Password baru minimal 8 karakter', 'error'); return; }

  const btn = document.getElementById('user-save-btn');
  btn.disabled = true;
  const data = id
    ? { id: Number(id), name, role, unit, status: document.getElementById('user-status').value, password }
    : { username, name, role, unit, password };
  try {
    const res = await apiRequest(VSM.apiBase, {
      method: 'POST',
      headers: apiHeaders(),
      body: JSON.stringify({ action: id ? 'UPDATE_USER' : 'CREATE_USER', data }),
    });
    if (!res.ok) throw new Error(res.error);
    closeModal('modal-user');
    toast(id ? 'User diperbarui' : 'User "' + username + '" ditambahkan', 'success');
    await loadUsers();
  } catch (e) {
    toast(e.message, 'error');
  } finally {
    btn.disabled = false;
  }
}

async function deleteUser(id) {
  const u = userList.find(x => x.id === id);
  if (!u || !confirm(`Hapus akun "${u.username}" (${u.name})?\nAkun tidak akan bisa login lagi.`)) return;
  try {
    const res = await apiRequest(VSM.apiBase, {
      method: 'POST', headers: apiHeaders(),
      body: JSON.stringify({ action: 'DELETE_USER', data: { id } }),
    });
    if (!res.ok) throw new Error(res.error);
    toast('Akun dihapus', 'info');
    await loadUsers();
  } catch (e) {
    toast(e.message, 'error');
  }
}
