// ══════════════════════════════════════════
//  SESSION — login per unit (Unit 1 / Unit 2), logout, ganti unit (admin)
// ══════════════════════════════════════════

const Session = {
  loginUnit: Store.get('vsm_login_unit') || (VSM.units[0] ? VSM.units[0].id : 'U1'),
  started: false,

  async boot() {
    this.renderLogin();
    if (Auth.token) {
      try {
        const res = await fetchGAS({ action: 'ME' });
        Auth.user = res.user;
        return this.enter();
      } catch (e) { Auth.clear(); }
    }
    this.showLogin();
  },

  /* ── tampilan login ── */
  renderLogin() {
    const tabs = document.getElementById('login-units');
    tabs.innerHTML = VSM.units.map(u =>
      `<button type="button" class="login-unit-tab" data-unit="${u.id}" onclick="Session.pickUnit('${u.id}')">${u.name}</button>`
    ).join('');
    this.pickUnit(this.loginUnit);
  },

  pickUnit(id) {
    this.loginUnit = id;
    Store.set('vsm_login_unit', id);
    document.getElementById('login').dataset.unit = id;
    document.querySelectorAll('.login-unit-tab').forEach(b => b.classList.toggle('active', b.dataset.unit === id));
    const u = VSM.units.find(x => x.id === id);
    document.getElementById('login-title').textContent = 'Panel Security — ' + (u ? u.name : id);
  },

  showLogin(msg) {
    document.getElementById('login').classList.add('show');
    document.getElementById('login-error').textContent = msg || '';
    document.getElementById('login-pass').value = '';
  },

  hideLogin() { document.getElementById('login').classList.remove('show'); },

  async submit(ev) {
    ev.preventDefault();
    const btn = document.getElementById('login-btn');
    const err = document.getElementById('login-error');
    err.textContent = '';
    btn.disabled = true;
    try {
      const json = await apiRequest(VSM.apiBase, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain;charset=utf-8' },
        body: JSON.stringify({ action: 'LOGIN', data: {
          username: document.getElementById('login-user').value.trim(),
          password: document.getElementById('login-pass').value,
          unit: this.loginUnit,
        } }),
      });
      if (!json.ok) throw new Error(json.error);
      Auth.save(json.token, json.user);
      this.enter();
    } catch (e) {
      err.textContent = e.message;
    } finally {
      btn.disabled = false;
    }
  },

  /* ── masuk aplikasi ── */
  enter() {
    this.hideLogin();
    this.clearLocalData();
    this.applyUser();
    if (!this.started) { this.started = true; startPolling(); }
    loadFromServer();
    updateSyncIndicator();
    nav('dashboard', document.querySelector('.sb-item'));
  },

  applyUser() {
    const u = Auth.user;
    document.getElementById('sb-uname').textContent = u.name;
    document.getElementById('sb-urole').textContent = (u.role === 'admin' ? 'Admin' : 'Security') + ' · ' + (u.unitName || u.unit);
    document.getElementById('sb-avatar-text').textContent = u.name.split(/\s+/).map(w => w[0]).join('').slice(0, 2).toUpperCase();
    document.documentElement.dataset.unit = u.unit;

    const badge = document.getElementById('unit-badge');
    badge.textContent = u.unitName || u.unit;
    badge.style.display = '';

    const sw = document.getElementById('unit-switch');
    if (u.role === 'admin' && u.units.length > 1) {
      sw.style.display = '';
      sw.innerHTML = u.units.map(x => `<option value="${x.id}" ${x.id === u.unit ? 'selected' : ''}>${x.name}</option>`).join('');
    } else {
      sw.style.display = 'none';
    }
  },

  // Admin berpindah unit: buang seluruh cache lokal supaya data kedua unit tidak tercampur.
  switchUnit(id) {
    Auth.user.unit = id;
    Auth.user.unitName = (Auth.user.units.find(x => x.id === id) || {}).name || id;
    this.enter();
  },

  logout() {
    if (!confirm('Keluar dari panel?')) return;
    Auth.clear();
    this.clearLocalData();
    this.showLogin();
  },

  expired() {
    Auth.clear();
    this.showLogin('Sesi berakhir. Silakan login kembali.');
  },

  clearLocalData() {
    ['visitors', 'equipment', 'alerts', 'absen', 'audit'].forEach(k => Store.del('vsm_' + k));
    ['Visitors', 'Equipment'].forEach(s => Store.del('vsm_deleted_' + s));
    lastFingerprints = {};
  },
};
