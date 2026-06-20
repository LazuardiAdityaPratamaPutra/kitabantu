<?php
// modules/donasi/donasi_barang.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';
cekAkses(['admin_pusat', 'admin_logistik']);

$pageTitle = 'Daftar Donasi Barang';

// ── Proses terima / tolak donasi barang ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_barang'])) {
    $donasiBarangId = (int) ($_POST['donasi_barang_id'] ?? 0);
    $aksi           = $_POST['aksi_barang'] ?? '';
    $alasan         = trim($_POST['alasan'] ?? '');
    $gudangId       = (int) ($_POST['gudang_id'] ?? 0);

    if ($donasiBarangId && in_array($aksi, ['terima', 'tolak'])) {
        try {
            $pdo->beginTransaction();

            $cek = $pdo->prepare("
                SELECT * FROM donasi_barang
                WHERE id = :id AND status_logistik = 'pending'
                FOR UPDATE
            ");
            $cek->execute(['id' => $donasiBarangId]);
            $donasi = $cek->fetch();

            if ($donasi) {
                $statusBaru = ($aksi === 'terima') ? 'diterima_di_gudang' : 'ditolak';

                $pdo->prepare("
                    UPDATE donasi_barang
                    SET status_logistik = :status, alasan_penolakan = :alasan
                    WHERE id = :id
                ")->execute([
                    'status' => $statusBaru,
                    'alasan' => $alasan ?: null,
                    'id'     => $donasiBarangId
                ]);

                // Jika diterima → tambahkan ke stok inventaris gudang yang dipilih
                if ($aksi === 'terima' && $gudangId) {
                    $cekStok = $pdo->prepare("
                        SELECT id FROM stok_inventaris
                        WHERE gudang_id = :gid AND nama_barang = :barang
                        LIMIT 1
                    ");
                    $cekStok->execute(['gid' => $gudangId, 'barang' => $donasi['nama_barang']]);
                    $stokAda = $cekStok->fetch();

                    if ($stokAda) {
                        $pdo->prepare("
                            UPDATE stok_inventaris
                            SET kuantitas = kuantitas + :qty
                            WHERE id = :sid
                        ")->execute(['qty' => $donasi['kuantitas'], 'sid' => $stokAda['id']]);
                    } else {
                        $pdo->prepare("
                            INSERT INTO stok_inventaris (gudang_id, nama_barang, kuantitas, satuan)
                            VALUES (:gid, :barang, :qty, :satuan)
                        ")->execute([
                            'gid'    => $gudangId,
                            'barang' => $donasi['nama_barang'],
                            'qty'    => $donasi['kuantitas'],
                            'satuan' => $donasi['satuan'],
                        ]);
                    }
                }

                $pdo->commit();
                catatLog($pdo, $_SESSION['user_id'], 'donasi', 'PROSES_DONASI_BARANG',
                    "Donasi barang #{$donasiBarangId} ({$donasi['nama_barang']}) di-$aksi.");
                $_SESSION['flash'] = [
                    'type'  => 'success',
                    'pesan' => "Donasi barang berhasil di-$statusBaru."
                ];
            } else {
                $pdo->rollBack();
                $_SESSION['flash'] = [
                    'type'  => 'danger',
                    'pesan' => 'Data tidak ditemukan atau sudah diproses.'
                ];
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['type' => 'danger', 'pesan' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
    header('Location: donasi_barang.php');
    exit;
}

// ── Ambil data pendukung ─────────────────────────────────────────
$gudangList = $pdo->query(
    "SELECT id, nama_gudang FROM gudang_pusat ORDER BY nama_gudang"
)->fetchAll();

$statusFilter = $_GET['status'] ?? '';
$cari         = trim($_GET['cari'] ?? '');

$where  = "WHERE 1=1";
$params = [];

if ($statusFilter !== '') {
    $where .= " AND db.status_logistik = :status";
    $params['status'] = $statusFilter;
}

// Integrasi fitur cari untuk Donasi Barang (Nama Donatur / Nama Barang / Resi)
if ($cari !== '') {
    $where .= " AND (u.nama LIKE :cari1 OR db.nama_barang LIKE :cari2 OR db.nomor_resi LIKE :cari3)";
    $params['cari1'] = "%$cari%";
    $params['cari2'] = "%$cari%";
    $params['cari3'] = "%$cari%";
}

$stmt = $pdo->prepare("
    SELECT db.*, u.nama AS nama_donatur, k.nama_bencana
    FROM donasi_barang db
    LEFT JOIN users u ON db.user_id = u.id
    JOIN kampanye_bencana k ON db.kampanye_id = k.id
    $where
    ORDER BY db.created_at DESC
");
$stmt->execute($params);
$donasiBarang = $stmt->fetchAll();

$statusMap = [
    'pending'            => 'warning text-dark',
    'diterima_di_gudang' => 'success',
    'ditolak'            => 'danger',
];
$statusLabel = [
    'pending'            => 'Pending',
    'diterima_di_gudang' => 'Diterima di Gudang',
    'ditolak'            => 'Ditolak',
];
$kondisiLabel = ['baru' => 'Baru', 'layak_pakai' => 'Layak Pakai', 'rusak' => 'Rusak'];

include '../../includes/header.php';
?>

<h5 class="fw-bold mb-3"><i class="bi bi-box-seam me-2"></i>Daftar Donasi Barang</h5>

<form method="GET" class="row g-2 mb-3">
  <div class="col-md-4">
    <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari donatur / nama barang / resi..." value="<?= htmlspecialchars($cari) ?>">
  </div>
  <div class="col-md-3">
    <select name="status" class="form-select form-select-sm">
      <option value="">-- Semua Status --</option>
      <?php foreach ($statusLabel as $val => $lbl): ?>
        <option value="<?= $val ?>" <?= $statusFilter === $val ? 'selected' : '' ?>>
          <?= $lbl ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
    <a href="donasi_barang.php" class="btn btn-sm btn-outline-secondary px-3">Reset</a>
  </div>
</form>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Donatur</th>
            <th>Kampanye</th>
            <th>Nama Barang</th>
            <th>Qty</th>
            <th>Kondisi</th>
            <th>No. Resi</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($donasiBarang)): ?>
            <tr>
              <td colspan="10" class="text-center text-muted py-4">
                Tidak ada data donasi barang.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($donasiBarang as $i => $d): ?>
              <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($d['nama_donatur'] ?? 'Anonim') ?></td>
                <td class="small"><?= htmlspecialchars($d['nama_bencana']) ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($d['nama_barang']) ?></td>
                <td><?= $d['kuantitas'] ?> <?= htmlspecialchars($d['satuan']) ?></td>
                <td>
                  <?php
                    $kondisiColor = ['baru' => 'success', 'layak_pakai' => 'secondary', 'rusak' => 'danger'];
                  ?>
                  <span class="badge bg-<?= $kondisiColor[$d['kondisi_barang']] ?? 'secondary' ?>">
                    <?= $kondisiLabel[$d['kondisi_barang']] ?? $d['kondisi_barang'] ?>
                  </span>
                </td>
                <td class="small"><?= htmlspecialchars($d['nomor_resi'] ?? '-') ?></td>
                <td>
                  <span class="badge bg-<?= $statusMap[$d['status_logistik']] ?? 'secondary' ?>">
                    <?= $statusLabel[$d['status_logistik']] ?? $d['status_logistik'] ?>
                  </span>
                </td>
                <td class="text-muted small">
                  <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                </td>
                <td class="text-center">
                  <?php if ($d['status_logistik'] === 'pending'): ?>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                      data-bs-target="#modalTerima"
                      data-id="<?= $d['id'] ?>"
                      data-barang="<?= htmlspecialchars(addslashes($d['nama_barang'])) ?>"
                      data-qty="<?= $d['kuantitas'] ?> <?= $d['satuan'] ?>">
                      Terima
                    </button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                      data-bs-target="#modalTolak"
                      data-id="<?= $d['id'] ?>"
                      data-barang="<?= htmlspecialchars(addslashes($d['nama_barang'])) ?>">
                      Tolak
                    </button>
                  <?php endif; ?>
                  <?php if ($d['alasan_penolakan']): ?>
                    <i class="bi bi-info-circle text-danger"
                       title="<?= htmlspecialchars($d['alasan_penolakan']) ?>"></i>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTerima" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-success">
          <i class="bi bi-check-circle me-2"></i>Terima Donasi Barang
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="aksi_barang" value="terima">
          <input type="hidden" name="donasi_barang_id" id="terimaId">
          <p>
            Terima donasi <strong id="terimaBarang"></strong>
            (<span id="terimaQty"></span>)?
          </p>
          <div class="mb-3">
            <label class="form-label fw-semibold">
              Simpan ke Gudang <span class="text-danger">*</span>
            </label>
            <select name="gudang_id" class="form-select" required>
              <option value="">-- Pilih Gudang --</option>
              <?php foreach ($gudangList as $g): ?>
                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_gudang']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">
              Barang akan otomatis ditambahkan ke stok gudang yang dipilih.
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Konfirmasi Terima</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTolak" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">
          <i class="bi bi-x-circle me-2"></i>Tolak Donasi Barang
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="aksi_barang" value="tolak">
          <input type="hidden" name="donasi_barang_id" id="tolakId">
          <p>Tolak donasi barang <strong id="tolakBarang"></strong>?</p>
          <div class="mb-3">
            <label class="form-label fw-semibold">Alasan Penolakan</label>
            <textarea name="alasan" class="form-control" rows="3"
              placeholder="cth: Barang rusak, tidak sesuai kebutuhan..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Konfirmasi Tolak</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('modalTerima').addEventListener('show.bs.modal', e => {
  const btn = e.relatedTarget;
  document.getElementById('terimaId').value          = btn.dataset.id;
  document.getElementById('terimaBarang').textContent = btn.dataset.barang;
  document.getElementById('terimaQty').textContent    = btn.dataset.qty;
});

document.getElementById('modalTolak').addEventListener('show.bs.modal', e => {
  const btn = e.relatedTarget;
  document.getElementById('tolakId').value           = btn.dataset.id;
  document.getElementById('tolakBarang').textContent  = btn.dataset.barang;
});
</script>

<?php include '../../includes/footer.php'; ?>