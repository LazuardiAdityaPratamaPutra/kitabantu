<?php
// modules/kampanye/edit.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';
cekAkses(['admin_pusat']);

$pageTitle = 'Edit Kampanye';
$id        = (int) ($_GET['id'] ?? 0);
$errors    = [];

// Ambil data kampanye yang akan diedit
$stmt = $pdo->prepare("SELECT * FROM kampanye_bencana WHERE id = :id");
$stmt->execute(['id' => $id]);
$kampanye = $stmt->fetch();

if (!$kampanye) {
    $_SESSION['flash'] = ['type' => 'danger', 'pesan' => 'Kampanye tidak ditemukan.'];
    header('Location: index.php');
    exit;
}

// Ambil master jenis bencana
$jenisBencana = $pdo->query(
    "SELECT id, nama_jenis FROM jenis_bencana ORDER BY nama_jenis"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_id    = (int) ($_POST['jenis_bencana_id'] ?? 0);
    $nama        = trim($_POST['nama_bencana']  ?? '');
    $lokasi      = trim($_POST['lokasi']         ?? '');
    $deskripsi   = trim($_POST['deskripsi']      ?? '');
    $target_dana = (float) str_replace(['.', ','], ['', '.'], $_POST['target_dana'] ?? '0');
    $status      = $_POST['status']   ?? 'draf';
    $latitude    = $_POST['latitude']  !== '' ? (float) $_POST['latitude']  : null;
    $longitude   = $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;

    if (!$nama)   $errors[] = 'Nama bencana wajib diisi.';
    if (!$lokasi) $errors[] = 'Lokasi wajib diisi.';

    // Upload foto baru jika ada
    $namaFoto = $kampanye['foto_bencana']; // Pertahankan foto lama
    if (!empty($_FILES['foto_bencana']['name'])) {
        $ext  = strtolower(pathinfo($_FILES['foto_bencana']['name'], PATHINFO_EXTENSION));
        $izin = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $izin)) {
            $errors[] = 'Format foto harus JPG, PNG, atau WEBP.';
        } elseif ($_FILES['foto_bencana']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            // Hapus foto lama jika ada
            if ($namaFoto && file_exists('../../uploads/foto_bencana/' . $namaFoto)) {
                unlink('../../uploads/foto_bencana/' . $namaFoto);
            }
            $namaFoto = uniqid('bencana_') . '.' . $ext;
            move_uploaded_file(
                $_FILES['foto_bencana']['tmp_name'],
                '../../uploads/foto_bencana/' . $namaFoto
            );
        }
    }

    if (empty($errors)) {
        $pdo->prepare("
            UPDATE kampanye_bencana SET
                jenis_bencana_id = :jid,
                nama_bencana     = :nama,
                lokasi           = :lokasi,
                deskripsi        = :deskripsi,
                target_dana      = :target,
                status           = :status,
                latitude         = :lat,
                longitude        = :lng,
                foto_bencana     = :foto
            WHERE id = :id
        ")->execute([
            'jid'       => $jenis_id ?: null,
            'nama'      => $nama,
            'lokasi'    => $lokasi,
            'deskripsi' => $deskripsi,
            'target'    => $target_dana,
            'status'    => $status,
            'lat'       => $latitude,
            'lng'       => $longitude,
            'foto'      => $namaFoto,
            'id'        => $id,
        ]);

        catatLog($pdo, $_SESSION['user_id'], 'kampanye', 'EDIT_KAMPANYE',
            "Kampanye #$id diperbarui: $nama");
        $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Kampanye berhasil diperbarui.'];
        header('Location: index.php');
        exit;
    }

    // Jika ada error, timpa data form dengan POST agar tidak hilang
    $kampanye = array_merge($kampanye, $_POST);
}

include '../../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h5 class="fw-bold mb-0">Edit Kampanye — <?= htmlspecialchars($kampanye['nama_bencana']) ?></h5>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <div class="row g-3">

        <div class="col-md-8">
          <label class="form-label fw-semibold">Nama Bencana <span class="text-danger">*</span></label>
          <input type="text" name="nama_bencana" class="form-control" required
            value="<?= htmlspecialchars($kampanye['nama_bencana']) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Jenis Bencana</label>
          <select name="jenis_bencana_id" class="form-select">
            <option value="">-- Pilih Jenis --</option>
            <?php foreach ($jenisBencana as $j): ?>
              <option value="<?= $j['id'] ?>"
                <?= $kampanye['jenis_bencana_id'] == $j['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($j['nama_jenis']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Lokasi <span class="text-danger">*</span></label>
          <input type="text" name="lokasi" class="form-control" required
            value="<?= htmlspecialchars($kampanye['lokasi']) ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="draf"    <?= $kampanye['status'] === 'draf'    ? 'selected' : '' ?>>Draf</option>
            <option value="aktif"   <?= $kampanye['status'] === 'aktif'   ? 'selected' : '' ?>>Aktif</option>
            <option value="selesai" <?= $kampanye['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Target Dana (Rp)</label>
          <input type="number" name="target_dana" class="form-control" min="0"
            value="<?= htmlspecialchars($kampanye['target_dana']) ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Deskripsi</label>
          <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($kampanye['deskripsi'] ?? '') ?></textarea>
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Latitude</label>
          <input type="number" name="latitude" class="form-control" step="any"
            value="<?= htmlspecialchars($kampanye['latitude'] ?? '') ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Longitude</label>
          <input type="number" name="longitude" class="form-control" step="any"
            value="<?= htmlspecialchars($kampanye['longitude'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Ganti Foto Bencana</label>
          <?php if ($kampanye['foto_bencana']): ?>
            <div class="mb-2">
              <img
                src="../../uploads/foto_bencana/<?= htmlspecialchars($kampanye['foto_bencana']) ?>"
                style="height:80px;border-radius:6px;object-fit:cover" alt="Foto saat ini">
              <div class="form-text">Foto saat ini. Upload baru untuk mengganti.</div>
            </div>
          <?php endif; ?>
          <input type="file" name="foto_bencana" class="form-control" accept="image/*">
        </div>

      </div><!-- /row -->

      <hr class="my-4">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-warning">
          <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
        </button>
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
