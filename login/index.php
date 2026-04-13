<?php
require_once '../includes/auth.php';
if (isLoggedIn()) { header('Location: /dashboard'); exit; }
$csrf      = generateCSRFToken();
$activeTab = ($_GET['tab'] ?? 'login') === 'register' ? 'register' : 'login';
$errorMap  = [
    'empty'       => 'Semua field wajib diisi.',
    'csrf'        => 'Request tidak valid. Coba lagi.',
    'invalid'     => 'Email atau password salah.',
    'ratelimit'   => 'Terlalu banyak percobaan. Coba lagi dalam 15 menit.',
    'locked'      => 'Akun terkunci sementara. Coba lagi dalam 15 menit.',
    'bademail'    => 'Format email tidak valid.',
    'badusername' => 'Username hanya boleh huruf, angka, underscore (3-20 karakter).',
    'weakpass'    => 'Password terlalu lemah. Min 8 karakter, huruf besar/kecil, angka, simbol.',
    'passmatch'   => 'Konfirmasi password tidak cocok.',
    'exists'      => 'Email atau username sudah terdaftar.',
];
$errorMsg   = $errorMap[$_GET['error'] ?? ''] ?? '';
$successMsg = ($_GET['success'] ?? '') === 'registered' ? 'Akun berhasil dibuat! Silakan login.' : '';
$timeoutMsg = ($_GET['msg'] ?? '') === 'timeout' ? 'Sesi kamu sudah berakhir. Silakan login kembali.' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Seismograph</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: #0a0a0a;
  color: #e0e0e0;
  font-family: 'Segoe UI', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}
.wrap { width: 100%; max-width: 420px; }
.brand { text-align: center; margin-bottom: 24px; }
.brand h1 { font-size: 1.4rem; color: #fff; font-weight: 700; }
.brand p { font-size: .8rem; color: #555; margin-top: 4px; }
.card { background: #111; border: 1px solid #222; border-radius: 10px; overflow: hidden; }
.tabs { display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #222; }
.tab {
  padding: 14px;
  text-align: center;
  font-size: .85rem;
  color: #666;
  cursor: pointer;
  border: none;
  background: transparent;
  transition: all .2s;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
}
.tab.active { color: #4da6ff; border-bottom-color: #4da6ff; background: rgba(77,166,255,.04); }
.tab-body { display: none; padding: 24px; }
.tab-body.active { display: block; }
.form-group { margin-bottom: 16px; }
label { display: block; font-size: .78rem; color: #888; margin-bottom: 6px; }
input[type=text], input[type=email], input[type=password] {
  width: 100%;
  background: #0d0d0d;
  border: 1px solid #2a2a2a;
  border-radius: 5px;
  padding: 10px 12px;
  color: #e0e0e0;
  font-size: .95rem;
  outline: none;
  transition: border-color .2s;
}
input:focus { border-color: #4da6ff; }
input::placeholder { color: #444; }
.btn-submit {
  width: 100%;
  padding: 11px;
  background: #4da6ff;
  color: #000;
  font-weight: 700;
  font-size: .95rem;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  margin-top: 6px;
  transition: background .2s;
}
.btn-submit:hover { background: #70b8ff; }
.alert { border-radius: 5px; padding: 10px 12px; margin-bottom: 16px; font-size: .8rem; }
.alert-error { background: rgba(255,80,80,.1); border: 1px solid rgba(255,80,80,.3); color: #ff9090; }
.alert-success { background: rgba(80,200,80,.1); border: 1px solid rgba(80,200,80,.3); color: #90e090; }
.alert-warning { background: rgba(255,200,80,.1); border: 1px solid rgba(255,200,80,.3); color: #ffe090; }
.hint { font-size: .72rem; color: #555; margin-top: 5px; }
.back { text-align: center; margin-top: 16px; }
.back a { font-size: .78rem; color: #555; text-decoration: none; }
.back a:hover { color: #aaa; }
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">
    <h1>Seismograph</h1>
    <p>Earthquake Monitoring System</p>
  </div>
  <div class="card">
    <div class="tabs">
      <button class="tab <?= $activeTab==='login'?'active':'' ?>" onclick="switchTab('login',this)">Login</button>
      <button class="tab <?= $activeTab==='register'?'active':'' ?>" onclick="switchTab('register',this)">Daftar</button>
    </div>

    <div class="tab-body <?= $activeTab==='login'?'active':'' ?>" id="tab-login">
      <?php if($timeoutMsg):?><div class="alert alert-warning"><?= e($timeoutMsg) ?></div><?php endif;?>
      <?php if($errorMsg && $activeTab==='login'):?><div class="alert alert-error"><?= e($errorMsg) ?></div><?php endif;?>
      <?php if($successMsg):?><div class="alert alert-success"><?= e($successMsg) ?></div><?php endif;?>
      <form action="/auth/login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="nama@email.com" required maxlength="100">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Password" required maxlength="128">
        </div>
        <button type="submit" class="btn-submit">Masuk</button>
      </form>
    </div>

    <div class="tab-body <?= $activeTab==='register'?'active':'' ?>" id="tab-register">
      <?php if($errorMsg && $activeTab==='register'):?><div class="alert alert-error"><?= e($errorMsg) ?></div><?php endif;?>
      <form action="/auth/register.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input type="text" name="full_name" placeholder="Nama Lengkap" required maxlength="100">
        </div>
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" placeholder="username_kamu" required maxlength="20">
          <div class="hint">3-20 karakter, huruf/angka/underscore</div>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="nama@email.com" required maxlength="100">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Password" required maxlength="128">
          <div class="hint">Min 8 karakter, huruf besar/kecil, angka, simbol</div>
        </div>
        <div class="form-group">
          <label>Konfirmasi Password</label>
          <input type="password" name="confirm_password" placeholder="Ulangi password" required maxlength="128">
        </div>
        <button type="submit" class="btn-submit">Daftar</button>
      </form>
    </div>
  </div>
  <div class="back"><a href="/start">Kembali ke Beranda</a></div>
</div>
<script>
function switchTab(tab, btn) {
  document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-body').forEach(c => c.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  btn.classList.add('active');
}
</script>
</body>
</html>
