<?php
// modules/gudang/stok_inventaris.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';
cekAkses(['admin_pusat', 'admin_logistik']);

$pageTitle = 'Stok Inventaris';
$errors    = [];
$gudangId  = (int) ($_GET['gudang'] ?? 0);

// Ambil semua gudang untuk dropdown filter
$gudangList = $pdo->query(
    "SELECT id, nama_gudang FROM gudang_pusat ORDER BY nama_gudang"
)->fetchAll();

// Nama gudang aktif (untuk judul)
$namaGudang = '';
if ($gudangId) {
    $ng = $pdo->prepare("SELECT nama_gudang FROM gudang_pusat WHERE id=:id");
    $ng->execute(['id' => $gudangId]);
    $namaGudang = $ng->fetchColumn() ?: '';
}

// ── Proses tambah / edit stok ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_stok'])) {
    $aksi       = $_POST['aksi_stok']      ?? 'tambah';
    $stokId     = (int) ($_POST['stok_id']  ?? 0);
    $gid        = (int) ($_POST['gudang_id']  ?? 0);
    $namaBarang = trim($_POST['nama_barang']  ?? '');
    $kategori   = $_POST['kategori']          ?? 'lainnya';
    $qty        = (int) ($_POST['kuantitas']   ?? 0);
    $satuan     = trim($_POST['satuan']         ?? 'unit');
    $kadaluarsa = $_POST['tgl_kedaluwarsa']    ?? null;
    $keterangan = trim($_POST['keterangan']    ?? '');

    if (!$gid)        $errors[] = 'Pilih gudang.';
    if (!$namaBarang) $errors[] = 'Nama barang wajib diisi.';
    if ($qty < 0)     $errors[] = 'Kuantitas tidak boleh negatif.';

    if (empty($errors)) {
        if ($aksi === 'edit' && $stokId) {
            $pdo->prepare("
                UPDATE stok_inventaris
                SET gudang_id=:gid, nama_barang=:nama, kategori=:kat,
                    kuantitas=:qty, satuan=:satuan,
                    tgl_kedaluwarsa=:kd, keterangan=:ket
                WHERE id=:id
            ")->execute([
                'gid'    => $gid,   'nama'   => $namaBarang, 'kat' => $kategori,
                'qty'    => $qty,   'satuan' => $satuan,
                'kd'     => $kadaluarsa ?: null,
                'ket'    => $keterangan, 'id' => $stokId
            ]);
            $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Data stok berhasil diperbarui.'];
        } else {
            $pdo->prepare("
                INSERT INTO stok_inventaris
                    (gudang_id, nama_barang, kategori, kuantitas, satuan, tgl_kedaluwarsa, keterangan)
                VALUES (:gid, :nama, :kat, :qty, :satuan, :kd, :ket)
            ")->execute([
                'gid'    => $gid,   'nama'   => $namaBarang, 'kat' => $kategori,
                'qty'    => $qty,   'satuan' => $satuan,
                'kd'     => $kadaluarsa ?: null, 'ket' => $keterangan
            ]);
            catatLog($pdo, $_SESSION['user_id'], 'gudang', 'TAMBAH_STOK',
                "Stok baru: $namaBarang ($qty $satuan) di gudang #$gid");
            $_SESSION['flash'] = [
                'type'  => 'success',
                'pesan' => "Stok \"$namaBarang\" berhasil ditambahkan."
            ];
        }
        header('Location: stok_inventaris.php' . ($gudangId ? "?gudang=$gudangId" : ''));
        exit;
    }
}

// ── Proses hapus stok ────────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $hid = (int) $_GET['hapus'];
    $pdo->prepare("DELETE FROM stok_inventaris WHERE id=:id")->execute(['id' => $hid]);
    $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Item stok berhasil dihapus.'];
    header('Location: stok_inventaris.php' . ($gudangId ? "?gudang=$gudangId" : ''));
    exit;
}

// ── Ambil data stok ──────────────────────────────────────────────
$where  = "WHERE 1=1";
$params = [];
if ($gudangId) {
    $where          .= " AND si.gudang_id = :gid";
    $params['gid']   = $gudangId;
}

$stmtStok = $pdo->prepare("
    SELECT si.*, g.nama_gudang,
           DATEDIFF(si.tgl_kedaluwarsa, CURDATE()) AS sisa_hari
    FROM stok_inventaris si
    JOIN gudang_pusat g ON si.gudang_id = g.id
    $where
    ORDER BY si.tgl_kedaluwarsa ASC, si.nama_barang ASC
");
$stmtStok->execute($params);
$stokList = $stmtStok->fetchAll();

$kategoriMap = [
    'sembako'        => ['label' => 'Sembako',        'color' => 'warning'],
    'obat_obatan'    => ['label' => 'Obat-obatan',    'color' => 'danger'],
    'pakaian'        => ['label' => 'Pakaian',         'color' => 'info'],
    'tenda_sanitasi' => ['label' => 'Tenda/Sanitasi', 'color' => 'primary'],
    'khusus_rentan'  => ['label' => 'Khusus Rentan',  'color' => 'success'],
    'lainnya'        => ['label' => 'Lainnya',         'color' => 'secondary'],
];

// Stok yang sedang diedit
$stokEdit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM stok_inventaris WHERE id=:id");
    $st->execute(['id' => (int) $_GET['edit']]);
    $stokEdit = $st->fetch();
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-archive me-2"></i>Stok Inventaris
    <?= $namaGudang ? "— $namaGudang" : '' ?>
  </h5>
  <a href="index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-building me-1"></i> Kelola Gudang
  </a>
</div>

<!-- Filter Gudang -->
<form method="GET" class="row g-2 mb-4">
  <div class="col-md-3">
    <select name="gudang" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">-- Semua Gudang --</option>
      <?php foreach ($gudangList as $g): ?>
        <option value="<?= $g['id'] ?>" <?= $gudangId == $g['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($g['nama_gudang']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="row g-4">

  <!-- ── Form Tambah / Edit Stok ───────────────────────────────── -->
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">
        <?= $stokEdit ? 'Edit Item Stok' : 'Tambah Stok Baru' ?>
      </div>
      <div class="card-body">
        <?php if ($errors): ?>
          <div class="alert alert-danger py-2">
            <?php foreach ($errors as $e): ?>
              <div class="small"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="aksi_stok" value="<?= $stokEdit ? 'edit' : 'tambah' ?>">
          <?php if ($stokEdit): ?>
            <input type="hidden" name="stok_id" value="<?= $stokEdit['id'] ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Gudang <span class="text-danger">*</span></label>
            <select name="gudang_id" class="form-select" required>
              <option value="">-- Pilih Gudang --</option>
              <?php foreach ($gudangList as $g): ?>
                <option value="<?= $g['id'] ?>"
                  <?= ($stokEdit ? $stokEdit['gudang_id'] : $gudangId) == $g['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($g['nama_gudang']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Barang <span class="text-danger">*</span></label>
            <input type="text" name="nama_barang" class="form-control" required
              placeholder="cth: Beras, Minyak Goreng..."
              value="<?= htmlspecialchars($stokEdit['nama_barang'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Kategori</label>
            <select name="kategori" class="form-select">
              <?php foreach ($kategoriMap as $val => $kat): ?>
                <option value="<?= $val ?>"
                  <?= ($stokEdit['kategori'] ?? 'lainnya') === $val ? 'selected' : '' ?>>
                  <?= $kat['label'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col">
              <label class="form-label fw-semibold">Kuantitas</label>
              <input type="number" name="kuantitas" class="form-control" min="0"
                value="<?= htmlspecialchars($stokEdit['kuantitas'] ?? '0') ?>">
            </div>
            <div class="col">
              <label class="form-label fw-semibold">Satuan</label>
              <input type="text" name="satuan" class="form-control" placeholder="kg, pcs, dus..."
                value="<?= htmlspecialchars($stokEdit['satuan'] ?? 'unit') ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tanggal Kedaluwarsa</label>
            <input type="date" name="tgl_kedaluwarsa" class="form-control"
              value="<?= htmlspecialchars($stokEdit['tgl_kedaluwarsa'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($stokEdit['keterangan'] ?? '') ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-<?= $stokEdit ? 'warning' : 'primary' ?> btn-sm">
              <i class="bi bi-<?= $stokEdit ? 'check-circle' : 'plus-circle' ?> me-1"></i>
              <?= $stokEdit ? 'Simpan Perubahan' : 'Tambah Stok' ?>
            </button>
            <?php if ($stokEdit): ?>
              <a href="stok_inventaris.php<?= $gudangId ? "?gudang=$gudangId" : '' ?>"
                class="btn btn-outline-secondary btn-sm">Batal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Tabel Stok ────────────────────────────────────────────── -->
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">
        Daftar Stok (<?= count($stokList) ?> item)
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Gudang</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th class="text-end">Qty</th>
                <th>Kedaluwarsa</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($stokList)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    Tidak ada stok ditemukan.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($stokList as $i => $s): ?>
                  <?php
                    $rowClass = '';
                    $badgeKed = '';
                    if ($s['tgl_kedaluwarsa']) {
                        if ($s['sisa_hari'] <= 0) {
                            $rowClass = 'table-danger';
                            $badgeKed = '<span class="badge bg-danger ms-1">Kedaluwarsa!</span>';
                        } elseif ($s['sisa_hari'] <= 7) {
                            $rowClass = 'table-danger';
                            $badgeKed = "<span class='badge bg-danger ms-1'>{$s['sisa_hari']} hari</span>";
                        } elseif ($s['sisa_hari'] <= 30) {
                            $rowClass = 'table-warning';
                            $badgeKed = "<span class='badge bg-warning text-dark ms-1'>{$s['sisa_hari']} hari</span>";
                        }
                    }
                    $kat = $kategoriMap[$s['kategori']] ?? ['label' => $s['kategori'], 'color' => 'secondary'];
                  ?>
                  <tr class="<?= $rowClass ?>">
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td class="small"><?= htmlspecialchars($s['nama_gudang']) ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($s['nama_barang']) ?></td>
                    <td>
                      <span class="badge bg-<?= $kat['color'] ?>"><?= $kat['label'] ?></span>
                    </td>
                    <td class="text-end fw-semibold">
                      <?= number_format($s['kuantitas'], 0, ',', '.') ?>
                      <?= htmlspecialchars($s['satuan']) ?>
                    </td>
                    <td class="small">
                      <?= $s['tgl_kedaluwarsa']
                          ? date('d/m/Y', strtotime($s['tgl_kedaluwarsa']))
                          : '<span class="text-muted">-</span>' ?>
                      <?= $badgeKed ?>
                    </td>
                    <td class="text-center">
                      <a href="stok_inventaris.php?edit=<?= $s['id'] ?><?= $gudangId ? "&gudang=$gudangId" : '' ?>"
                        class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="stok_inventaris.php?hapus=<?= $s['id'] ?><?= $gudangId ? "&gudang=$gudangId" : '' ?>"
                        class="btn btn-sm btn-outline-danger" title="Hapus"
                        onclick="return confirm('Hapus item stok ini?')">
                        <i class="bi bi-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>

<?php include '../../includes/footer.php'; ?>
