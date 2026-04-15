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
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
  <div class="brand">
    <div class="brand-icon">🌏</div>
    <h1>Seismograph</h1>
    <p>Earthquake Monitoring System</p>
  </div>
  <div class="card">
    <div class="tabs">
      <button class="tab <?= $activeTab==='login'?'active':'' ?>" onclick="switchTab('login',this)">Login</button>
      <button class="tab <?= $activeTab==='register'?'active':'' ?>" onclick="switchTab('register',this)">Daftar</button>
    </div>

    <!-- TAB LOGIN -->
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
          <div class="password-wrapper">
            <input type="password" name="password" id="pw-login" placeholder="Password" required maxlength="128">
            <button type="button" class="toggle-pw" onclick="togglePassword('pw-login', this)" aria-label="Tampilkan password">
              👁
            </button>
          </div>
        </div>
        <button type="submit" class="btn-submit">Masuk</button>
      </form>
    </div>

    <!-- TAB REGISTER -->
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
          <div class="password-wrapper">
            <input type="password" name="password" id="pw-reg" placeholder="Password" required maxlength="128">
            <button type="button" class="toggle-pw" onclick="togglePassword('pw-reg', this)" aria-label="Tampilkan password">
              👁
            </button>
          </div>
          <div class="hint">Min 8 karakter, huruf besar/kecil, angka, simbol</div>
        </div>
        <div class="form-group">
          <label>Konfirmasi Password</label>
          <div class="password-wrapper">
            <input type="password" name="confirm_password" id="pw-confirm" placeholder="Ulangi password" required maxlength="128">
            <button type="button" class="toggle-pw" onclick="togglePassword('pw-confirm', this)" aria-label="Tampilkan password">
              👁
            </button>
          </div>
        </div>
        <button type="submit" class="btn-submit">Daftar</button>
      </form>
    </div>
  </div>
  <div class="back"><a href="/start">← Kembali ke Beranda</a></div>
</div>
<script>
function switchTab(tab, btn) {
  document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-body').forEach(c => c.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  btn.classList.add('active');
}

function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  btn.textContent = isHidden ? '🙈' : '👁';
}
</script>
</body>
</html>
