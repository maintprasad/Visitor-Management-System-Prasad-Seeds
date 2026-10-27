// ══════════════════════════════════════════
//  NAVIGATION
// ══════════════════════════════════════════
const pageTitles = {
  dashboard: ['Dashboard Overview', 'Live monitoring · Real-time'],
  visitors: ['Visitor Management', 'Kelola check-in dan check-out visitor'],
  equipment: ['Equipment Management', 'Rekam pergerakan equipment masuk/keluar'],
  realtime: ['Real-time Monitor', 'Pantau visitor aktif secara langsung'],
  alerts: ['Alert Management', 'Overstay · Equipment not returned · Unauthorized movement'],
  emergency: ['Emergency Dashboard', 'Muster list semua visitor di dalam pabrik'],
  'absen-kontraktor': ['Absen Kontraktor', 'Rekap kehadiran pekerja kontraktor'],
  qr: ['QR Code System', 'Generate dan scan QR visitor'],
  reports: ['Reports & Analytics', 'Laporan harian, mingguan, bulanan'],
  audit: ['Audit Trail', 'Log semua aktivitas sistem'],
  users: ['User Management', 'Kelola akun security & admin (login Main Panel dan Check-Out)'],
};

let currentPage = 'dashboard';
let currentVisitorType = 'all';
let sidebarOpen = false;

function toggleSidebar() {
  sidebarOpen = !sidebarOpen;
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sb-overlay');
  const main = document.querySelector('.main');

  sidebar.classList.toggle('open', sidebarOpen);
  overlay.classList.toggle('show', sidebarOpen);

  const isMobile = window.innerWidth <= 768;
  if (!isMobile) {
    main.classList.toggle('pushed', sidebarOpen);
  } else {
    main.classList.remove('pushed');
    document.body.style.overflow = sidebarOpen ? 'hidden' : '';
  }

  const toggleBtn = document.getElementById('sb-toggle');
  if (toggleBtn) toggleBtn.classList.toggle('open', sidebarOpen);
  const toggleIcon = document.getElementById('sb-toggle-icon');
  if (toggleIcon) toggleIcon.className = sidebarOpen ? 'ti ti-x' : 'ti ti-menu-2';

  if (sidebarOpen) {
    history.pushState({ sidebarOpen: true, page: currentPage }, '', '#sidebar');
  }
}

// nav() didefinisikan ulang di bagian bawah via History API section

function renderPage(page) {
  if (page === 'dashboard') renderDashboard();
  if (page === 'visitors') renderVisitorTable();
  if (page === 'equipment') renderEquipmentTable();
  if (page === 'realtime') renderRealtime();
  if (page === 'alerts') { checkAlerts(); renderAlerts(); }
  if (page === 'emergency') renderEmergency();
  if (page === 'absen-kontraktor') renderAbsenTable();
  if (page === 'qr') renderQR();
  if (page === 'reports') { initReports(); renderReportDaily(); }
  if (page === 'audit') renderAudit();
  if (page === 'users') loadUsers();
}
