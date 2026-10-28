<?php
/** PWA: banner "Pasang aplikasi" + pendaftaran service worker. @var array{base:string} $config */
?>
<style>
#pwa-install{position:fixed;left:16px;right:16px;bottom:calc(16px + env(safe-area-inset-bottom));z-index:9999;display:none;align-items:center;gap:12px;
  max-width:460px;margin:0 auto;background:#0f1829;color:#fff;border:1px solid rgba(255,255,255,.14);border-radius:14px;padding:12px 14px;
  box-shadow:0 10px 30px rgba(0,0,0,.35);font:500 14px/1.3 'DM Sans',system-ui,sans-serif}
#pwa-install.show{display:flex}
#pwa-install img{width:36px;height:36px;border-radius:8px;background:#fff;object-fit:contain}
#pwa-install .t{flex:1}
#pwa-install .t small{display:block;font-weight:400;opacity:.7;margin-top:2px}
#pwa-install button{border:0;border-radius:10px;padding:9px 14px;font:600 13px 'DM Sans',system-ui,sans-serif;cursor:pointer}
#pwa-install .go{background:#2563eb;color:#fff}
#pwa-install .no{background:transparent;color:#cbd5e1;padding:9px 6px}
</style>
<div id="pwa-install" role="dialog" aria-label="Pasang aplikasi">
  <img src="<?= e(asset('img/icon-192.png')) ?>" alt="">
  <div class="t">Pasang aplikasi VSM Pasuruan<small id="pwa-hint">Buka lebih cepat dari layar utama / desktop.</small></div>
  <button class="go" id="pwa-go" type="button">Pasang</button>
  <button class="no" id="pwa-no" type="button" aria-label="Tutup">Nanti</button>
</div>
<script>
(function () {
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  var box = document.getElementById('pwa-install'), go = document.getElementById('pwa-go'), no = document.getElementById('pwa-no'), hint = document.getElementById('pwa-hint');
  var deferred = null;
  function dismissed() { try { return Date.now() - Number(localStorage.getItem('vsm_panel_pwa_no') || 0) < 7 * 864e5; } catch (e) { return false; } }
  function hide(remember) { box.classList.remove('show'); if (remember) { try { localStorage.setItem('vsm_panel_pwa_no', String(Date.now())); } catch (e) {} } }

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(<?= json_encode($config['base'] . '/sw.js') ?>).catch(function (e) { console.warn('[PWA] SW gagal:', e.message); });
    });
  }
  if (standalone) return;

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault(); deferred = e;
    if (!dismissed()) box.classList.add('show');
  });
  go.addEventListener('click', function () {
    if (!deferred) return;
    deferred.prompt();
    deferred.userChoice.finally(function () { deferred = null; hide(false); });
  });
  no.addEventListener('click', function () { hide(true); });
  window.addEventListener('appinstalled', function () { hide(false); });

  // iOS Safari tidak punya prompt pasang: tampilkan petunjuk manual.
  var ios = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
  if (ios && !dismissed()) {
    hint.textContent = 'Ketuk tombol Bagikan lalu "Tambah ke Layar Utama".';
    go.style.display = 'none'; no.textContent = 'Mengerti';
    box.classList.add('show');
  }
})();
</script>
