// Titik mulai aplikasi — dimuat paling akhir.
document.getElementById('login-form').addEventListener('submit', (e) => Session.submit(e));
Session.boot();
