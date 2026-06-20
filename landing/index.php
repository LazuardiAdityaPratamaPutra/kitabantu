<?php
// landing/index.php — Halaman publik, tidak butuh login
require '../config/koneksi.php';

$kampanye = $pdo->query("
    SELECT k.*, j.nama_jenis, j.icon,
           ROUND((k.dana_terkumpul / NULLIF(k.target_dana,0)) * 100, 1) AS persen
    FROM kampanye_bencana k
    LEFT JOIN jenis_bencana j ON k.jenis_bencana_id = j.id
    WHERE k.status = 'aktif'
    ORDER BY k.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>KitaBantu — Donasi Bencana</title>
  <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<!-- Hero -->
<div class="bg-primary text-white py-5 text-center">
  <h1 class="display-5 fw-bold">🆘 KitaBantu</h1>
  <p class="lead">Bersama kita bantu sesama yang terdampak bencana</p>
</div>

<!-- Daftar Kampanye -->
<div class="container py-5">
  <h3 class="fw-bold mb-4">Kampanye Aktif</h3>
  <div class="row g-4">
    <?php foreach ($kampanye as $k): ?>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm">
        <?php if ($k['foto_bencana']): ?>
          <img src="../uploads/foto_bencana/<?= htmlspecialchars($k['foto_bencana']) ?>" class="card-img-top" style="height:180px;object-fit:cover">
        <?php endif; ?>
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi <?= htmlspecialchars($k['icon'] ?? 'bi-exclamation-triangle') ?> text-danger"></i>
            <span class="badge bg-danger-subtle text-danger"><?= htmlspecialchars($k['nama_jenis'] ?? 'Bencana') ?></span>
          </div>
          <h5 class="card-title"><?= htmlspecialchars($k['nama_bencana']) ?></h5>
          <p class="text-muted small"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($k['lokasi']) ?></p>
          <!-- Progress bar dana -->
          <div class="mb-2">
            <div class="d-flex justify-content-between small mb-1">
              <span>Terkumpul</span>
              <span><?= $k['persen'] ?>%</span>
            </div>
            <div class="progress" style="height:8px">
              <div class="progress-bar bg-success" style="width:<?= min($k['persen'], 100) ?>%"></div>
            </div>
            <div class="d-flex justify-content-between small mt-1 text-muted">
              <span>Rp <?= number_format($k['dana_terkumpul'], 0, ',', '.') ?></span>
              <span>Target: Rp <?= number_format($k['target_dana'], 0, ',', '.') ?></span>
            </div>
          </div>
        </div>
        <div class="card-footer bg-transparent">
          <a href="form_donasi.php?kampanye=<?= $k['id'] ?>" class="btn btn-primary w-100">
            <i class="bi bi-heart-fill me-1"></i> Donasi Sekarang
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
