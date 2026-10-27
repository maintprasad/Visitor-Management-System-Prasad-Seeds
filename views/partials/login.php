<!-- ════════ LOGIN (per unit) ════════ -->
<div id="login" class="login" data-unit="U1">
  <form id="login-form" class="login-card" autocomplete="on">
    <img class="login-logo" src="<?= e(asset('img/logo.png')) ?>" alt="Logo">
    <div class="login-sub">Visitor System Management · Pasuruan</div>
    <h1 id="login-title">Panel Security</h1>

    <div class="login-label">Pilih unit</div>
    <div class="login-units" id="login-units"></div>

    <label class="login-label" for="login-user">Username</label>
    <input id="login-user" type="text" autocomplete="username" required>

    <label class="login-label" for="login-pass">Password</label>
    <input id="login-pass" type="password" autocomplete="current-password" required>

    <div class="login-error" id="login-error" role="alert"></div>
    <button type="submit" id="login-btn" class="login-btn"><i class="ti ti-login"></i> Masuk</button>
    <div class="login-hint">Akun Unit 1 hanya bisa membuka data Unit 1, begitu juga Unit 2.</div>
  </form>
</div>
