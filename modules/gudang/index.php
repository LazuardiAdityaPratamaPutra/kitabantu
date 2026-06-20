<?php
// modules/gudang/index.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';
cekAkses(['admin_pusat', 'admin_logistik']);

$pageTitle = 'Manajemen Gudang';
$errors    = [];

// ── Proses tambah atau edit gudang ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi   = $_POST['aksi']    ?? 'tambah';
    $gid    = (int) ($_POST['gudang_id'] ?? 0);
    $nama   = trim($_POST['nama_gudang'] ?? '');
    $alamat = trim($_POST['alamat']      ?? '');
    $kap    = (int) ($_POST['kapasitas'] ?? 0);
    $pj     = trim($_POST['penanggung_jawab'] ?? '');
    $telp   = trim($_POST['no_telp']     ?? '');
    $lat    = $_POST['latitude']  !== '' ? (float) $_POST['latitude']  : null;
    $lng    = $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;

    if (!$nama)   $errors[] = 'Nama gudang wajib diisi.';
    if (!$alamat) $errors[] = 'Alamat wajib diisi.';

    if (empty($errors)) {
        if ($aksi === 'edit' && $gid) {
            $pdo->prepare("
                UPDATE gudang_pusat
                SET nama_gudang=:nama, alamat=:alamat, kapasitas=:kap,
                    penanggung_jawab=:pj, no_telp=:telp, latitude=:lat, longitude=:lng
                WHERE id=:id
            ")->execute([
                'nama' => $nama, 'alamat' => $alamat, 'kap' => $kap,
                'pj'   => $pj,   'telp'   => $telp,   'lat' => $lat,
                'lng'  => $lng,  'id'     => $gid
            ]);
            catatLog($pdo, $_SESSION['user_id'], 'gudang', 'EDIT_GUDANG',
                "Gudang #$gid '$nama' diperbarui.");
            $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Gudang berhasil diperbarui.'];
        } else {
            $pdo->prepare("
                INSERT INTO gudang_pusat
                    (nama_gudang, alamat, kapasitas, penanggung_jawab, no_telp, latitude, longitude)
                VALUES (:nama, :alamat, :kap, :pj, :telp, :lat, :lng)
            ")->execute([
                'nama'   => $nama,   'alamat' => $alamat, 'kap' => $kap,
                'pj'     => $pj,     'telp'   => $telp,
                'lat'    => $lat,    'lng'    => $lng
            ]);
            catatLog($pdo, $_SESSION['user_id'], 'gudang', 'TAMBAH_GUDANG',
                "Gudang baru: $nama");
            $_SESSION['flash'] = [
                'type'  => 'success',
                'pesan' => "Gudang \"$nama\" berhasil ditambahkan."
            ];
        }
        header('Location: index.php');
        exit;
    }
}

// ── Proses hapus gudang ──────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $hid   = (int) $_GET['hapus'];
    $namaG = $pdo->prepare("SELECT nama_gudang FROM gudang_pusat WHERE id=:id");
    $namaG->execute(['id' => $hid]);
    $ng    = $namaG->fetchColumn();
    $pdo->prepare("DELETE FROM gudang_pusat WHERE id=:id")->execute(['id' => $hid]);
    catatLog($pdo, $_SESSION['user_id'], 'gudang', 'HAPUS_GUDANG',
        "Gudang #$hid '$ng' dihapus.");
    $_SESSION['flash'] = ['type' => 'success', 'pesan' => "Gudang \"$ng\" berhasil dihapus."];
    header('Location: index.php');
    exit;
}

// ── Ambil data gudang dengan ringkasan stok ──────────────────────
$gudangList = $pdo->query("
    SELECT g.*,
           COUNT(DISTINCT si.id)         AS jumlah_item,
           COALESCE(SUM(si.kuantitas),0) AS total_stok
    FROM gudang_pusat g
    LEFT JOIN stok_inventaris si ON g.id = si.gudang_id
    GROUP BY g.id
    ORDER BY g.nama_gudang
")->fetchAll();

// Ambil data gudang yang ingin diedit
$gudangEdit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM gudang_pusat WHERE id=:id");
    $st->execute(['id' => (int) $_GET['edit']]);
    $gudangEdit = $st->fetch();
}

include '../../includes/header.php';
?>

<h5 class="fw-bold mb-4"><i class="bi bi-building me-2"></i>Manajemen Gudang Pusat</h5>

<div class="row g-4">

  <!-- ── Form Tambah / Edit ─────────────────────────────────────── -->
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">
        <?= $gudangEdit ? 'Edit Gudang' : 'Tambah Gudang Baru' ?>
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
          <input type="hidden" name="aksi" value="<?= $gudangEdit ? 'edit' : 'tambah' ?>">
          <?php if ($gudangEdit): ?>
            <input type="hidden" name="gudang_id" value="<?= $gudangEdit['id'] ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Gudang <span class="text-danger">*</span></label>
            <input type="text" name="nama_gudang" class="form-control" required
              value="<?= htmlspecialchars($gudangEdit['nama_gudang'] ?? $_POST['nama_gudang'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Alamat <span class="text-danger">*</span></label>
            <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($gudangEdit['alamat'] ?? $_POST['alamat'] ?? '') ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Kapasitas (unit/dus)</label>
            <input type="number" name="kapasitas" class="form-control" min="0"
              value="<?= htmlspecialchars($gudangEdit['kapasitas'] ?? '0') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Penanggung Jawab</label>
            <input type="text" name="penanggung_jawab" class="form-control"
              value="<?= htmlspecialchars($gudangEdit['penanggung_jawab'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">No. Telp</label>
            <input type="text" name="no_telp" class="form-control"
              value="<?= htmlspecialchars($gudangEdit['no_telp'] ?? '') ?>">
          </div>
          <div class="row g-2 mb-3">
            <div class="col">
              <label class="form-label fw-semibold">Latitude</label>
              <input type="number" name="latitude" class="form-control" step="any"
                value="<?= htmlspecialchars($gudangEdit['latitude'] ?? '') ?>">
            </div>
            <div class="col">
              <label class="form-label fw-semibold">Longitude</label>
              <input type="number" name="longitude" class="form-control" step="any"
                value="<?= htmlspecialchars($gudangEdit['longitude'] ?? '') ?>">
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-<?= $gudangEdit ? 'warning' : 'primary' ?> btn-sm">
              <i class="bi bi-<?= $gudangEdit ? 'check-circle' : 'plus-circle' ?> me-1"></i>
              <?= $gudangEdit ? 'Simpan Perubahan' : 'Tambah Gudang' ?>
            </button>
            <?php if ($gudangEdit): ?>
              <a href="index.php" class="btn btn-outline-secondary btn-sm">Batal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Tabel Daftar Gudang ────────────────────────────────────── -->
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">
        Daftar Gudang (<?= count($gudangList) ?>)
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Nama Gudang</th>
                <th>Alamat</th>
                <th>PJ</th>
                <th class="text-center">Jml Item</th>
                <th class="text-center">Total Stok</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($gudangList)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">Belum ada gudang.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($gudangList as $i => $g): ?>
                  <tr class="<?= ($gudangEdit && $gudangEdit['id'] == $g['id']) ? 'table-warning' : '' ?>">
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($g['nama_gudang']) ?></td>
                    <td class="small text-muted">
                      <?= htmlspecialchars(mb_substr($g['alamat'], 0, 40)) ?>...
                    </td>
                    <td class="small"><?= htmlspecialchars($g['penanggung_jawab'] ?? '-') ?></td>
                    <td class="text-center"><?= $g['jumlah_item'] ?></td>
                    <td class="text-center fw-semibold">
                      <?= number_format($g['total_stok'], 0, ',', '.') ?>
                    </td>
                    <td class="text-center">
                      <a href="stok_inventaris.php?gudang=<?= $g['id'] ?>"
                        class="btn btn-sm btn-outline-info" title="Lihat Stok">
                        <i class="bi bi-archive"></i>
                      </a>
                      <a href="index.php?edit=<?= $g['id'] ?>"
                        class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="index.php?hapus=<?= $g['id'] ?>"
                        class="btn btn-sm btn-outline-danger" title="Hapus"
                        onclick="return confirm('Hapus gudang ini? Semua stok di dalamnya juga akan terhapus.')">
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
