<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

// PERBAIKAN: Deteksi otomatis apakah sedang di XAMPP atau di Railway
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    $ROOT = '/eas'; // Jalur untuk XAMPP lokal
} else {
    $ROOT = '';     // Jalur untuk Railway (Live Server)
}

// Menyusun basis URL gambar secara dinamis agar path tidak pecah
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . $ROOT;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="adminHMD professional admin dashboard template">
  
  <title><?= $pageTitle ?? 'Dashboard | KitaBantu' ?></title>

  <link rel="stylesheet" href="<?= $ROOT ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $ROOT ?>/assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= $ROOT ?>/assets/css/style.css">
</head>

<body>
  <div class="admin-shell">
    <div class="sidebar-backdrop" data-sidebar-close></div>

    <?php 
    // OTOMATIS MEMANGGIL SIDEBAR DI SINI
    include __DIR__ . '/sidebar.php'; 
    ?>

    <div class="admin-main">
      
      <nav class="navbar admin-navbar navbar-expand bg-white">
        <div class="container-fluid px-3 px-lg-4">
          
          <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="adminSidebar" aria-expanded="true" aria-label="Toggle sidebar">
            <span></span>
            <span></span>
            <span></span>
          </button>

          <form class="d-none d-md-flex ms-3 flex-grow-1" role="search">
            <input class="form-control search-input" type="search" placeholder="Cari donasi, posko, atau laporan..." aria-label="Search">
          </form>

          <div class="navbar-actions ms-auto">
            
            <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme" title="Switch color theme">
              <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
            </button>
            
            <div class="dropdown">
              <button class="icon-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                <span id="notifBadge" class="notification-dot bg-danger text-white style-badge-notif" style="display:none; font-size:10px; width:15px; height:15px; text-align:center; line-height:15px; border-radius:50%; position:absolute; top:2px; right:2px;">0</span>
                <i class="bi bi-bell" aria-hidden="true"></i>
              </button>
              
              <div class="dropdown-menu dropdown-menu-end notification-menu" id="notifList" style="width:320px; max-height:400px; overflow-y:auto;">
                <div class="dropdown-header fw-bold text-body">Notifikasi Terbaru</div>
                <div id="notifItemsContainer">
                  <span class="dropdown-item text-muted small">Memuat notifikasi...</span>
                </div>
              </div>
            </div>

            <div class="dropdown">
              <button class="profile-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <!-- FIX: Menggunakan Base URL Dinamis + Timestamp Anti-Cache Browser -->
                <img class="avatar-img avatar-sm" 
                     src="<?= $baseUrl ?>/assets/images/avatar/avatar-4.jpg?v=<?= time() ?>" 
                     alt="User Avatar"
                     style="width: 32px; height: 32px; object-fit: cover; border-radius: 50%;">
                <span class="profile-name d-none d-sm-inline"><?= htmlspecialchars($_SESSION['nama'] ?? 'Guest User') ?></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= $ROOT ?>/modules/profile.php">Profil Saya</a></li>
                <li><a class="dropdown-item" href="<?= $ROOT ?>/modules/settings.php">Pengaturan Akun</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= $ROOT ?>/modules/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
              </ul>
            </div>

          </div>
        </div>
      </nav>

      <main class="dashboard-content">
        <div class="container-fluid px-3 px-lg-4 py-4">