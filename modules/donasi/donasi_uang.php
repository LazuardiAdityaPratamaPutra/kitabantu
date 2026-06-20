<?php
// modules/donasi/donasi_uang.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
cekAkses(['admin_pusat', 'admin_keuangan']);

$pageTitle = 'Daftar Donasi Uang';

// 1. Tangkap filter opsional dari method GET
$statusFilter = $_GET['status'] ?? '';
$cari         = trim($_GET['cari'] ?? '');

// 2. Inisialisasi kondisi dasar query
$where  = "WHERE 1=1";
$params = [];

// 3. Tambahkan kondisi parameter jika form terisi
if ($statusFilter !== '') { 
    $where .= " AND d.status_verifikasi = :status"; 
    $params['status'] = $statusFilter; 
}

// Memisahkan token :cari menjadi :cari1 dan :cari2 agar jumlah parameter sinkron
if ($cari !== '') { 
    $where .= " AND (u.nama LIKE :cari1 OR d.nomor_referensi LIKE :cari2)"; 
    $params['cari1'] = "%$cari%"; 
    $params['cari2'] = "%$cari%"; 
}

// 4. Siapkan Query SQL dengan menyisipkan $where di tempat yang benar
$sql = "
    SELECT d.*, u.nama as nama_donatur, k.nama_bencana
    FROM donasi_uang d
    LEFT JOIN users u ON d.user_id = u.id
    JOIN kampanye_bencana k ON d.kampanye_id = k.id
    $where
    ORDER BY d.created_at DESC
";

// 5. Eksekusi Statement PDO secara aman
$stmt = $pdo->prepare($sql);
$stmt->execute($params); 
$donasi = $stmt->fetchAll();

// Mapping badge warna
$badgeMap = [
    'pending'           => 'warning text-dark',
    'menunggu_approval' => 'info text-dark',
    'terverifikasi'     => 'success',
    'ditolak'           => 'danger',
];
$labelMap = [
    'pending'           => 'Pending',
    'menunggu_approval' => 'Menunggu Approval',
    'terverifikasi'     => 'Terverifikasi',
    'ditolak'           => 'Ditolak',
];

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-cash-coin me-2"></i>Daftar Donasi Uang</h5>
</div>

<form method="GET" class="row g-2 mb-3">
  <div class="col-md-4">
    <input type="text" name="cari" class="form-control" placeholder="Cari nama / no. referensi..." value="<?= htmlspecialchars($cari) ?>">
  </div>
  <div class="col-md-3">
    <select name="status" class="form-select">
      <option value="">-- Semua Status --</option>
      <?php foreach ($labelMap as $val => $lbl): ?>
        <option value="<?= $val ?>" <?= $statusFilter === $val ? 'selected' : '' ?>><?= $lbl ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-primary px-3">Filter</button>
    <a href="donasi_uang.php" class="btn btn-outline-secondary px-3">Reset</a>
  </div>
</form>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>No. Referensi</th>
            <th>Donatur</th>
            <th>Kampanye</th>
            <th class="text-end">Nominal</th>
            <th>Bank</th>
            <th>Status</th>
            <th>Tanggal</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($donasi)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data donasi.</td></tr>
          <?php else: ?>
            <?php foreach ($donasi as $i => $d): ?>
              <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td><code><?= htmlspecialchars($d['nomor_referensi'] ?? '-') ?></code></td>
                <td><?= htmlspecialchars($d['nama_donatur'] ?? 'Anonim') ?></td>
                <td><?= htmlspecialchars($d['nama_bencana']) ?></td>
                <td class="text-end fw-semibold">Rp <?= number_format($d['nominal'], 0, ',', '.') ?></td>
                <td><?= htmlspecialchars($d['bank_asal']) ?></td>
                <td>
                  <span class="badge bg-<?= $badgeMap[$d['status_verifikasi']] ?? 'secondary' ?>">
                    <?= $labelMap[$d['status_verifikasi']] ?? $d['status_verifikasi'] ?>
                  </span>
                </td>
                <td class="text-muted small"><?= date('d/m/Y', strtotime($d['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>