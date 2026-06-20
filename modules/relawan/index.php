<?php
// modules/relawan/index.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';
cekAkses(['admin_pusat', 'petugas_lapangan']);

$pageTitle = 'Manajemen Relawan';
$errors    = [];

// ── Proses tambah / edit relawan ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_relawan'])) {
    $aksi      = $_POST['aksi_relawan'] ?? 'tambah';
    $rid       = (int) ($_POST['relawan_id'] ?? 0);
    $nama      = trim($_POST['nama']      ?? '');
    $noHp      = trim($_POST['no_hp']     ?? '');
    $email     = trim($_POST['email']     ?? '');
    $keahlian  = trim($_POST['keahlian']  ?? '');
    $alamat    = trim($_POST['alamat']    ?? '');

    if (!$nama)  $errors[] = 'Nama relawan wajib diisi.';
    if (!$noHp)  $errors[] = 'Nomor HP wajib diisi.';

    if (empty($errors)) {
        if ($aksi === 'edit' && $rid) {
            $pdo->prepare("
                UPDATE relawan
                SET nama=:nama, no_hp=:hp, email=:email, keahlian=:keahlian, alamat=:alamat
                WHERE id=:id
            ")->execute([
                'nama'     => $nama, 'hp'       => $noHp,
                'email'    => $email ?: null,
                'keahlian' => $keahlian, 'alamat' => $alamat, 'id' => $rid
            ]);
            catatLog($pdo, $_SESSION['user_id'], 'relawan', 'EDIT_RELAWAN',
                "Relawan #$rid '$nama' diperbarui.");
            $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Data relawan berhasil diperbarui.'];
        } else {
            $pdo->prepare("
                INSERT INTO relawan (nama, no_hp, email, keahlian, alamat, status_aktif)
                VALUES (:nama, :hp, :email, :keahlian, :alamat, 1)
            ")->execute([
                'nama'     => $nama, 'hp'       => $noHp,
                'email'    => $email ?: null,
                'keahlian' => $keahlian, 'alamat' => $alamat
            ]);
            catatLog($pdo, $_SESSION['user_id'], 'relawan', 'TAMBAH_RELAWAN',
                "Relawan baru: $nama");
            $_SESSION['flash'] = [
                'type'  => 'success',
                'pesan' => "Relawan \"$nama\" berhasil ditambahkan."
            ];
        }
        header('Location: index.php');
        exit;
    }
}

// ── Proses hapus relawan ─────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $hid  = (int) $_GET['hapus'];
    $namaR = $pdo->prepare("SELECT nama FROM relawan WHERE id=:id");
    $namaR->execute(['id' => $hid]);
    $nr   = $namaR->fetchColumn();
    $pdo->prepare("DELETE FROM relawan WHERE id=:id")->execute(['id' => $hid]);
    catatLog($pdo, $_SESSION['user_id'], 'relawan', 'HAPUS_RELAWAN',
        "Relawan #$hid '$nr' dihapus.");
    $_SESSION['flash'] = ['type' => 'success', 'pesan' => "Relawan \"$nr\" berhasil dihapus."];
    header('Location: index.php');
    exit;
}

// ── Ambil data relawan ───────────────────────────────────────────
$relawanList = $pdo->query("
    SELECT r.*, u.nama AS nama_akun
    FROM relawan r
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.nama ASC
")->fetchAll();

$relawanEdit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM relawan WHERE id=:id");
    $st->execute(['id' => (int) $_GET['edit']]);
    $relawanEdit = $st->fetch();
}

include '../../includes/header.php';
?>

<h5 class="fw-bold mb-4"><i class="bi bi-people-fill me-2"></i>Manajemen Relawan</h5>

<div class="row g-4">

  <!-- ── Form Tambah / Edit ─────────────────────────────────────── -->
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">
        <?= $relawanEdit ? 'Edit Relawan' : 'Tambah Relawan Baru' ?>
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
          <input type="hidden" name="aksi_relawan" value="<?= $relawanEdit ? 'edit' : 'tambah' ?>">
          <?php if ($relawanEdit): ?>
            <input type="hidden" name="relawan_id" value="<?= $relawanEdit['id'] ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Nama Lengkap <span class="text-danger">*</span>
            </label>
            <input type="text" name="nama" class="form-control" required
              value="<?= htmlspecialchars($relawanEdit['nama'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              No. HP / WhatsApp <span class="text-danger">*</span>
            </label>
            <input type="text" name="no_hp" class="form-control" required
              placeholder="08xxxxxxxxxx"
              value="<?= htmlspecialchars($relawanEdit['no_hp'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control"
              value="<?= htmlspecialchars($relawanEdit['email'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Keahlian</label>
            <input type="text" name="keahlian" class="form-control"
              placeholder="cth: Medis, Logistik, SAR..."
              value="<?= htmlspecialchars($relawanEdit['keahlian'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Alamat</label>
            <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($relawanEdit['alamat'] ?? '') ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-<?= $relawanEdit ? 'warning' : 'primary' ?> btn-sm">
              <i class="bi bi-<?= $relawanEdit ? 'check-circle' : 'plus-circle' ?> me-1"></i>
              <?= $relawanEdit ? 'Simpan' : 'Tambah Relawan' ?>
            </button>
            <?php if ($relawanEdit): ?>
              <a href="index.php" class="btn btn-outline-secondary btn-sm">Batal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Tabel Daftar Relawan ───────────────────────────────────── -->
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Daftar Relawan (<?= count($relawanList) ?>)</span>
        <a href="jadwal_piket.php" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-calendar3 me-1"></i> Jadwal Piket
        </a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Nama</th>
                <th>No. HP</th>
                <th>Keahlian</th>
                <th>Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($relawanList)): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    Belum ada relawan terdaftar.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($relawanList as $i => $r): ?>
                  <tr>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['nama']) ?></td>
                    <td>
                      <a href="https://wa.me/<?= preg_replace('/^0/', '62', $r['no_hp']) ?>"
                        target="_blank" class="text-decoration-none small">
                        <i class="bi bi-whatsapp text-success me-1"></i>
                        <?= htmlspecialchars($r['no_hp']) ?>
                      </a>
                    </td>
                    <td class="small"><?= htmlspecialchars($r['keahlian'] ?? '-') ?></td>
                    <td>
                      <span class="badge bg-<?= $r['status_aktif'] ? 'success' : 'secondary' ?>">
                        <?= $r['status_aktif'] ? 'Aktif' : 'Nonaktif' ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <a href="index.php?edit=<?= $r['id'] ?>"
                        class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="index.php?hapus=<?= $r['id'] ?>"
                        class="btn btn-sm btn-outline-danger" title="Hapus"
                        onclick="return confirm('Hapus relawan ini? Jadwal piket terkait juga akan terhapus.')">
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
