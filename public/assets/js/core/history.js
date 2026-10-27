// ══════════════════════════════════════════
//  BOTTOM NAV SYNC
// ══════════════════════════════════════════
const bottomNavPages = ['dashboard','visitors','realtime'];

function setBottomNav(page) {
  document.querySelectorAll('.bn-item').forEach(el => el.classList.remove('active'));
  const target = document.getElementById('bn-' + page);
  if (target) target.classList.add('active');
  // update visitor badge on bottom nav
  const inside = DB.visitors().filter(v => v.status === 'Inside' || v.status === 'Overstay').length;
  const bnBadge = document.getElementById('bn-badge-visitors');
  if (bnBadge) {
    bnBadge.textContent = inside;
    bnBadge.style.display = inside > 0 ? '' : 'none';
  }
}

// ══════════════════════════════════════════
//  HISTORY API — Back swipe support
// ══════════════════════════════════════════
// ══════════════════════════════════════════
//  HISTORY API — Back swipe support
// ══════════════════════════════════════════

// Override nav langsung — tidak pakai _origNav untuk menghindari infinite loop
const _navCore = function(page, el) {
  if (sidebarOpen) toggleSidebar();
  document.querySelectorAll('[id^="page-"]').forEach(p => p.style.display = 'none');
  document.getElementById('page-' + page).style.display = '';
  document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active'));
  if (el) el.classList.add('active');
  const [title, sub] = pageTitles[page] || [page, ''];
  document.getElementById('page-title').textContent = title;
  document.getElementById('page-sub').textContent = sub;
  currentPage = page;
  const ta = document.getElementById('topbar-actions');
  const syncBadgeHTML = '<span id="sync-badge" class="pill pill-overstay" style="display:none;cursor:pointer" onclick="retryPendingSync()" title="Klik untuk coba sync ulang sekarang"></span>';
  if (page === 'emergency') {
    ta.innerHTML = syncBadgeHTML + '<button class="btn btn-danger" onclick="exportEmergency()"><i class="ti ti-download"></i> Export Muster Report</button>';
  } else if (page === 'dashboard') {
    ta.innerHTML = syncBadgeHTML + '<button class="btn btn-primary" onclick="openModal(\'modal-select-type\')"><i class="ti ti-user-plus"></i> Check In Visitor</button>';
  } else {
    ta.innerHTML = syncBadgeHTML;
  }
  updateSyncIndicator();
  renderPage(page);
  setBottomNav(page);
};

// Ganti fungsi nav global
nav = function(page, el) {
  if (page !== currentPage) {
    history.pushState({ page }, '', '#' + page);
  }
  _navCore(page, el);
};

// Handle back gesture browser/OS
window.addEventListener('popstate', function(e) {
  if (sidebarOpen) {
    sidebarOpen = false;
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sb-overlay').classList.remove('show');
    document.querySelector('.main').classList.remove('pushed');
    document.body.style.overflow = '';
    return;
  }
  const page = (e.state && e.state.page) ? e.state.page : 'dashboard';
  const sbItem = document.querySelector(`.sb-item[onclick*="'${page}'"]`);
  _navCore(page, sbItem);
});

// Set initial history state
history.replaceState({ page: 'dashboard' }, '', '#dashboard');
