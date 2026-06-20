<?php
// modules/laporan/log_aktivitas.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
cekAkses(['admin_pusat']);

$pageTitle = 'Log Aktivitas Sistem';

$logs = $pdo->query("
    SELECT l.*, u.nama AS nama_user
    FROM log_aktivitas l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 200
")->fetchAll();

// Mapping warna badge per modul
$modulWarna = [
    'donasi'      => 'primary',
    'logistik'    => 'success',
    'pengiriman'  => 'info',
    'posko'       => 'warning',
    'auth'        => 'secondary',
];

include '../../includes/header.php';
?>

<h5 class="fw-bold mb-4"><i class="bi bi-journal-text me-2"></i>Log Aktivitas Sistem</h5>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Waktu</th>
            <th>User</th>
            <th>Modul</th>
            <th>Aksi</th>
            <th>Deskripsi</th>
            <th>IP Address</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada log aktivitas.</td></tr>
          <?php else: ?>
            <?php foreach ($logs as $i => $log): ?>
              <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td class="text-muted small"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                <td><?= htmlspecialchars($log['nama_user'] ?? 'Sistem') ?></td>
                <td>
                  <span class="badge bg-<?= $modulWarna[$log['modul']] ?? 'secondary' ?>">
                    <?= strtoupper(htmlspecialchars($log['modul'])) ?>
                  </span>
                </td>
                <td><code class="small"><?= htmlspecialchars($log['aksi']) ?></code></td>
                <td class="small"><?= htmlspecialchars($log['deskripsi'] ?? '-') ?></td>
                <td class="text-muted small"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>