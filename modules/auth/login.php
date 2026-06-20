<?php
// modules/auth/login.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ../../modules/dashboard/index.php');
    exit;
}

require '../../config/koneksi.php';

$error = '';
$ROOT  = '/eas'; // Basis URL proyek kamu

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT id, nama, email, password, role, is_aktif FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            if (!$user['is_aktif']) {
                $error = 'Akun Anda telah dinonaktifkan.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama']    = $user['nama'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];

                session_regenerate_id(true);
                header('Location: ../../modules/dashboard/index.php');
                exit;
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | adminHMD</title>

  <link rel="stylesheet" href="<?= $ROOT ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $ROOT ?>/assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= $ROOT ?>/assets/css/style.css">

  <style>
    /* Mengikuti layout pembungkus halaman otentikasi adminHMD */
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      font-family: 'Inter', sans-serif;
      position: relative;
      transition: background-color 0.2s, color 0.2s;
    }

    /* Penempatan tombol saklar tema di pojok kanan atas halaman */
    .theme-toggle-wrapper {
      position: absolute;
      top: 20px;
      right: 20px;
    }

    .auth-card {
      width: 100%;
      max-width: 440px;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
      transition: background-color 0.2s, border-color 0.2s;
    }

    .auth-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
    }

    .auth-logo-box {
      background-color: #3b82f6;
      color: white;
      width: 38px;
      height: 38px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }

    .auth-brand-name {
      font-size: 1.2rem;
      font-weight: 700;
    }

    .auth-brand-sub {
      font-size: 0.8rem;
      color: #64748b;
    }

    .auth-banner-img {
      width: 100%;
      border-radius: 6px;
      margin-bottom: 20px;
    }

    .secure-tag {
      color: #3b82f6;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .auth-title {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 2px;
    }

    .auth-subtitle {
      font-size: 0.85rem;
      color: #64748b;
      margin-bottom: 20px;
    }

    /* ATURAN VARIABEL SAKLAR TEMA (LIGHT & DARK ACCORDING TO CSS TEMPLATE) */
    /* Mode Terang Bawaan */
    body { background-color: #f8fafc; color: #1e293b; }
    .auth-card { background-color: #ffffff; border: 1px solid #e2e8f0; }
    .form-control { background-color: #ffffff; border: 1px solid #cbd5e1; color: #1e293b; }

    /* Otomatis Switch bila Body disuntik atribut dark oleh main.js template */
    body[data-theme="dark"] {
      background-color: #0b1320;
      color: #ffffff;
    }
    body[data-theme="dark"] .auth-card {
      background-color: #121d30;
      border: 1px solid #1e2d4a;
    }
    body[data-theme="dark"] .form-control {
      background-color: #0b1320;
      border: 1px solid #1e2d4a;
      color: #ffffff;
    }
    body[data-theme="dark"] .auth-brand-sub,
    body[data-theme="dark"] .auth-subtitle {
      color: #94a3b8;
    }
  </style>
</head>
<body>

<div class="theme-toggle-wrapper">
  <button class="icon-button theme-toggle btn btn-light rounded-circle border shadow-sm" type="button" data-theme-toggle aria-label="Switch color theme">
    <i class="bi bi-moon-stars" data-theme-icon></i>
  </button>
</div>

<div class="auth-card">
  
  <div class="auth-header">
    <div class="auth-logo-box">
      <i class="bi bi-grid-1x2-fill"></i>
    </div>
    <div>
      <div class="auth-brand-name">adminHMD</div>
      <div class="auth-brand-sub">Sign in to your admin workspace.</div>
    </div>
  </div>

  <img src="<?= $ROOT ?>/assets/images/login-banner.jpg" class="auth-banner-img" alt="Workspace" onerror="this.src='https://themewagon.github.io/adminhmd/assets/images/dashboard-brief.jpg'">

  <div class="secure-tag">Secure Access</div>
  <div class="auth-title">Login</div>
  <div class="auth-subtitle">Sign in to your admin workspace.</div>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small" role="alert">
      <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label for="email" class="form-label fw-medium">Email address</label>
      <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <label for="password" class="form-label mb-0 fw-medium">Password</label>
        <a href="#" class="text-decoration-none small" style="color: #3b82f6;">Forgot?</a>
      </div>
      <input type="password" id="password" name="password" class="form-control" required>
    </div>

    <div class="mb-4 form-check">
      <input type="checkbox" class="form-check-input" id="remember">
      <label class="form-check-label small text-muted" for="remember">Remember me</label>
    </div>

    <div class="d-grid">
      <button type="submit" class="btn btn-primary py-2 fw-medium">
        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
      </button>
    </div>
  </form>

  <div class="text-center mt-4 text-muted small">
    New here? <a href="../../landing/index.php" class="text-decoration-none fw-medium" style="color: #3b82f6;">Create an account</a>
  </div>

</div>

<script src="<?= $ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= $ROOT ?>/assets/js/main.js"></script>

</body>
</html>