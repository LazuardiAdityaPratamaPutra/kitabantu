<?php
// modules/kampanye/index.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
cekAkses(['admin_pusat']);

$pageTitle = 'Kampanye Bencana';

// PERBAIKAN QUERY: Menggunakan LEFT JOIN ke donasi_uang dan di-SUM berdasarkan status terverifikasi
$query = "
    SELECT 
        k.id, k.nama_bencana, k.jenis_bencana_id, k.lokasi, k.target_dana, k.status, k.created_at,
        j.nama_jenis, j.icon,
        COALESCE(SUM(CASE WHEN d.status_verifikasi = 'terverifikasi' THEN d.nominal ELSE 0 END), 0) AS dana_terkumpul
    FROM kampanye_bencana k
    LEFT JOIN jenis_bencana j ON k.jenis_bencana_id = j.id
    LEFT JOIN donasi_uang d ON k.id = d.kampanye_id
    GROUP BY k.id
    ORDER BY k.created_at DESC
";

$kampanye = $pdo->query($query)->fetchAll();

$statusMap   = ['aktif' => 'success', 'selesai' => 'secondary', 'draf' => 'warning text-dark'];
$statusLabel = ['aktif' => 'Aktif',   'selesai' => 'Selesai',   'draf' => 'Draf'];

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-megaphone me-2"></i>Kampanye Bencana</h5>
  <a href="tambah.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-circle me-1"></i> Tambah Kampanye
  </a>
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Nama Bencana</th>
            <th>Jenis</th>
            <th>Lokasi</th>
            <th>Target Dana</th>
            <th>Terkumpul</th>
            <th>Progress</th>
            <th>Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($kampanye)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Belum ada kampanye.</td></tr>
          <?php else: ?>
            <?php foreach ($kampanye as $i => $k): 
              // Hitung persentase progress secara dinamis dan aman dari Division by Zero
              $target = $k['target_dana'] > 0 ? $k['target_dana'] : 1;
              $persen = round(($k['dana_terkumpul'] / $target) * 100, 1);
            ?>
              <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($k['nama_bencana']) ?></td>
                <td>
                  <span class="badge bg-danger-subtle text-danger">
                    <i class="bi <?= htmlspecialchars($k['icon'] ?? 'bi-exclamation-triangle') ?> me-1"></i>
                    <?= htmlspecialchars($k['nama_jenis'] ?? '-') ?>
                  </span>
                </td>
                <td class="small"><?= htmlspecialchars($k['lokasi']) ?></td>
                <td class="small">Rp <?= number_format($k['target_dana'], 0, ',', '.') ?></td>
                
                <td class="small fw-semibold text-success">Rp <?= number_format($k['dana_terkumpul'], 0, ',', '.') ?></td>
                
                <td style="min-width:100px">
                  <div class="progress" style="height:6px">
                    <div class="progress-bar bg-success" style="width:<?= min($persen, 100) ?>%"></div>
                  </div>
                  <div class="small text-muted mt-1"><?= $persen ?>%</div>
                </td>
                <td>
                  <span class="badge bg-<?= $statusMap[$k['status']] ?? 'secondary' ?>">
                    <?= $statusLabel[$k['status']] ?? $k['status'] ?>
                  </span>
                </td>
                <td class="text-center">
                  <a href="edit.php?id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <button class="btn btn-sm btn-outline-danger" title="Hapus"
                    onclick="konfirmasiHapus(<?= $k['id'] ?>, '<?= htmlspecialchars(addslashes($k['nama_bencana'])) ?>')">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalHapus" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">
          <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Hapus kampanye "<strong id="namaKampanye"></strong>"?
        Tindakan ini tidak bisa dibatalkan dan akan menghapus semua data donasi terkait.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <a href="#" id="linkHapus" class="btn btn-danger">Ya, Hapus</a>
      </div>
    </div>
  </div>
</div>

<script>
function konfirmasiHapus(id, nama) {
  document.getElementById('namaKampanye').textContent = nama;
  document.getElementById('linkHapus').href = 'hapus.php?id=' + id;
  new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>