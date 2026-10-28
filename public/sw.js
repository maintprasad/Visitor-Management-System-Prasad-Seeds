// Service worker Main Panel VSM.
// - Tampilan panel (halaman, CSS, JS, ikon) di-cache agar terbuka cepat dan tidak blank saat sinyal buruk.
// - API (/api) TIDAK PERNAH di-cache atau dicegat: data visitor, login, check-in/out selalu langsung ke
//   server, jadi data tidak pernah basi dan tidak ada kiriman tertunda yang bisa terkirim ganda.
const VERSION = 'vsm-panel-v1';
const ROOT = new URL('./', self.location).pathname;           // mis. "/" di Vercel, ".../public/" di XAMPP
const SHELL = ['./', 'manifest.webmanifest', 'assets/img/icon-192.png', 'assets/img/icon-512.png', 'assets/img/logo.png'];
const CDN_HOSTS = ['cdn.jsdelivr.net', 'fonts.googleapis.com', 'fonts.gstatic.com'];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(VERSION)
      .then((c) => Promise.all(SHELL.map((u) => c.add(u).catch(() => null))))   // satu file gagal jangan batalkan install
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

function isApi(url) {
  return url.pathname.startsWith(ROOT + 'api');
}

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);

  if (url.origin === self.location.origin) {
    if (isApi(url)) return;                       // API: biarkan langsung ke jaringan
    e.respondWith(
      fetch(req)
        .then((res) => {
          if (res.ok) { const copy = res.clone(); caches.open(VERSION).then((c) => c.put(req, copy)); }
          return res;
        })
        .catch(() => caches.match(req).then((hit) => hit || (req.mode === 'navigate' ? caches.match('./') : Response.error())))
    );
    return;
  }

  // Pustaka & font CDN (ExcelJS, ikon, font): cache dulu, perbarui di belakang layar.
  if (CDN_HOSTS.includes(url.hostname)) {
    e.respondWith(
      caches.match(req).then((hit) => {
        const refresh = fetch(req).then((res) => {
          if (res.ok || res.type === 'opaque') { const copy = res.clone(); caches.open(VERSION).then((c) => c.put(req, copy)); }
          return res;
        }).catch(() => hit);
        return hit || refresh;
      })
    );
  }
});
