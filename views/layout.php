<?php
/**
 * Layout panel kontrol security.
 * @var array{base:string,apiBase:string,units:list<array{id:string,name:string}>} $config
 */
require_once __DIR__ . '/helpers.php';

$pages  = ['dashboard', 'visitors', 'equipment', 'realtime', 'alerts', 'emergency', 'absen-kontraktor', 'qr', 'reports', 'audit', 'users'];
$modals = ['checkin-guest', 'checkin-contractor', 'checkin-driver', 'checkin-employee', 'checkin-auditor',
           'driver-approval', 'select-type', 'absen-kontraktor', 'absen-detail', 'equipment', 'detail', 'user'];

// Urutan script penting: sama dengan urutan di file Single-Page lama.
$scripts = [
    'core/config', 'core/store', 'core/api', 'core/session', 'core/alerts-engine', 'core/navigation',
    'pages/tabs', 'pages/dashboard', 'pages/visitors', 'pages/equipment', 'pages/realtime', 'pages/alerts',
    'pages/emergency', 'pages/qr', 'pages/reports', 'pages/export-excel', 'pages/audit', 'pages/users',
    'actions/checkin', 'actions/equipment', 'actions/visitor-detail', 'core/ui', 'core/history',
    'pages/absen-kontraktor', 'actions/recheckin', 'actions/contractor-checkout', 'core/sync', 'core/boot',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#0f1829">
<link rel="icon" type="image/png" href="<?= e(asset('img/icon-192.png')) ?>">
<link rel="apple-touch-icon" href="<?= e(asset('img/icon-192.png')) ?>">
<link rel="manifest" href="<?= e($config['base']) ?>/manifest.webmanifest">
<meta name="apple-mobile-web-app-title" content="VSM Pasuruan">
<meta name="application-name" content="VSM Pasuruan">
<title>Visitor System Management — Pasuruan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.6.0/dist/tabler-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
<link rel="stylesheet" href="<?= e(asset('css/panel.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/unit.css')) ?>">
</head>
<body>

<?php require __DIR__ . '/partials/login.php'; ?>

<div class="app">

<!-- TOGGLE BUTTON -->
<button id="sb-toggle" onclick="toggleSidebar()"><i class="ti ti-menu-2" id="sb-toggle-icon"></i></button>
<!-- OVERLAY -->
<div id="sb-overlay" onclick="toggleSidebar()"></div>

<?php require __DIR__ . '/partials/sidebar.php'; ?>

<!-- ════════ MAIN ════════ -->
<div class="main">
<?php require __DIR__ . '/partials/topbar.php'; ?>

  <!-- ══ PAGES ══ -->
  <div class="content">
<?php foreach ($pages as $p) { require __DIR__ . "/pages/{$p}.php"; } ?>
  </div><!-- /content -->
</div><!-- /main -->
</div><!-- /app -->

<?php require __DIR__ . '/partials/bottom-nav.php'; ?>

<!-- ════════ MODALS ════════ -->
<?php foreach ($modals as $m) { require __DIR__ . "/modals/{$m}.php"; } ?>

<!-- TOAST CONTAINER -->
<div id="toast-container"></div>

<script>window.VSM_CONFIG = <?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<?php foreach ($scripts as $s): ?>
<script src="<?= e(asset("js/{$s}.js")) ?>"></script>
<?php endforeach; ?>
<?php require __DIR__ . '/partials/pwa.php'; ?>
</body>
</html>
