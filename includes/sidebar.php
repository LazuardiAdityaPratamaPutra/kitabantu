<?php
// includes/sidebar.php
if (session_status() === PHP_SESSION_NONE) session_start();

$role = $_SESSION['role'] ?? '';

// PERBAIKAN: Deteksi otomatis apakah sedang di XAMPP atau di Railway
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    $ROOT = '/eas'; // Jalur untuk XAMPP lokal
} else {
    $ROOT = '';     // Jalur untuk Railway (Live Server)
}

$cur = $_SERVER['PHP_SELF'];

// Menyusun basis URL gambar secara dinamis agar path tidak pecah
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . $ROOT;

/**
 * Fungsi menuItem dibungkus cek eksistensi agar tidak memicu fatal error
 * jika file sidebar ter-include lebih dari satu kali.
 */
if (!function_exists('menuItem')) {
    function menuItem(string $href, string $icon, string $label, string $cur): string {
        $active = (str_contains($cur, $href)) ? 'active' : '';
        return "<a class=\"nav-link $active\" href=\"$href\">
                  <span class=\"nav-icon\"><i class=\"bi $icon\" aria-hidden=\"true\"></i></span>
                  <span class=\"nav-text\">$label</span>
                </a>";
    }
}
?>

<aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">
  <div class="sidebar-header">
    <a class="brand-mark" href="<?= $ROOT ?>/modules/dashboard/index.php" aria-label="adminHMD dashboard">
      <span class="brand-icon"><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i></span>
      <span class="brand-copy">
        <span class="brand-title">KitaBantu</span>
        <span class="brand-subtitle">Admin Panel</span>
      </span>
    </a>
  </div>

  <nav class="sidebar-nav">
    
    <?= menuItem("$ROOT/modules/dashboard/index.php", 'bi-speedometer2', 'Dashboard', $cur) ?>

    <?php if (in_array($role, ['admin_pusat'])): ?>
      <?= menuItem("$ROOT/modules/kampanye/index.php", 'bi-megaphone', 'Kampanye Bencana', $cur) ?>
    <?php endif; ?>

    <?php if (in_array($role, ['admin_pusat', 'admin_keuangan'])): ?>
      <?= menuItem("$ROOT/modules/donasi/donasi_uang.php", 'bi-cash-coin', 'Donasi Uang', $cur) ?>
      <?= menuItem("$ROOT/modules/donasi/donasi_barang.php", 'bi-box-seam', 'Donasi Barang', $cur) ?>
    <?php endif; ?>

    <?php if (in_array($role, ['admin_pusat', 'admin_logistik', 'petugas_lapangan'])): ?>
      <?= menuItem("$ROOT/modules/gudang/stok_inventaris.php", 'bi-archive', 'Stok Inventaris', $cur) ?>
      <?= menuItem("$ROOT/modules/logistik/permintaan.php", 'bi-clipboard-check', 'Permintaan Logistik', $cur) ?>
      <?= menuItem("$ROOT/modules/pengiriman/lacak_pengiriman.php", 'bi-truck', 'Lacak Pengiriman', $cur) ?>
    <?php endif; ?>

    <?php if (in_array($role, ['admin_pusat', 'petugas_lapangan'])): ?>
      <?= menuItem("$ROOT/modules/posko/index.php", 'bi-geo-alt', 'Posko Lapangan', $cur) ?>
      <?= menuItem("$ROOT/modules/relawan/index.php", 'bi-people-fill', 'Relawan', $cur) ?>
    <?php endif; ?>

    <?php if (in_array($role, ['admin_pusat'])): ?>
      <?= menuItem("$ROOT/modules/laporan/log_aktivitas.php", 'bi-journal-text', 'Log Aktivitas', $cur) ?>
      <?= menuItem("$ROOT/modules/peta/peta_lokasi.php", 'bi-map', 'Peta Lokasi', $cur) ?>
    <?php endif; ?>

  </nav>

  <div class="sidebar-user d-flex align-items-center gap-2" style="padding: 12px 16px;">
    <!-- FIX: Menggunakan Base URL Dinamis + Timestamp Anti-Cache Browser -->
    <img class="avatar-img avatar-md sidebar-user-avatar" 
         src="<?= $baseUrl ?>/assets/images/avatar/avatar-4.jpg?v=<?= time() ?>" 
         alt="User Avatar" 
         style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
    <div class="d-flex flex-column align-items-start text-start" style="line-height: 1.2;">
      <strong class="text-dark d-block" style="font-size: 0.9rem; font-weight: 700;">
        <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin Hasan') ?>
      </strong>
      <small class="text-muted text-capitalize" style="font-size: 0.75rem; display: block; margin-top: 2px;">
        <?= htmlspecialchars(str_replace('_', ' ', $role)) ?>
      </small>
    </div>
  </div>

  <div class="sidebar-footer">
    <span class="status-dot"></span>
    <span class="sidebar-footer-text">System running smoothly</span>
  </div>
</aside>