<?php
// modules/posko/index.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';
require '../../includes/notifikasi_helper.php';
cekAkses(['admin_pusat', 'petugas_lapangan']);

$pageTitle = 'Posko Lapangan';
$errors    = [];

// Ambil daftar kampanye aktif dan petugas untuk dropdown
$kampanyeList = $pdo->query(
    "SELECT id, nama_bencana FROM kampanye_bencana WHERE status='aktif' ORDER BY nama_bencana"
)->fetchAll();
$petugasList = $pdo->query(
    "SELECT id, nama FROM users WHERE role='petugas_lapangan' AND is_aktif=1 ORDER BY nama"
)->fetchAll();

// ── Proses tambah / edit posko ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_posko'])) {
    $aksi       = $_POST['aksi_posko']    ?? 'tambah';
    $poskoId    = (int) ($_POST['posko_id']    ?? 0);
    $kampanyeId = (int) ($_POST['kampanye_id'] ?? 0);
    $namaPosko  = trim($_POST['nama_posko']    ?? '');
    $alamat     = trim($_POST['alamat_posko']  ?? '');
    $balita     = (int) ($_POST['jml_balita']   ?? 0);
    $lansia     = (int) ($_POST['jml_lansia']   ?? 0);
    $ibuHamil   = (int) ($_POST['jml_ibu_hamil']?? 0);
    $totalJiwa  = (int) ($_POST['total_jiwa']   ?? 0);
    $petugasId  = ($_POST['petugas_id'] !== '') ? (int) $_POST['petugas_id'] : null;
    $lat        = $_POST['latitude']  !== '' ? (float) $_POST['latitude']  : null;
    $lng        = $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;
    $statusPosko= $_POST['status_posko'] ?? 'aktif';

    if (!$kampanyeId)   $errors[] = 'Pilih kampanye bencana.';
    if (!$namaPosko)    $errors[] = 'Nama posko wajib diisi.';
    if ($totalJiwa < 0) $errors[] = 'Total jiwa tidak boleh negatif.';

    if (empty($errors)) {
        if ($aksi === 'edit' && $poskoId) {
            $pdo->prepare("
                UPDATE posko_lapangan SET
                    kampanye_id=:kid, nama_posko=:nama, alamat_posko=:alamat,
                    jml_balita=:balita, jml_lansia=:lansia, jml_ibu_hamil=:hamil,
                    total_jiwa=:jiwa, petugas_id=:pid,
                    latitude=:lat, longitude=:lng, status_posko=:status
                WHERE id=:id
            ")->execute([
                'kid'    => $kampanyeId, 'nama'   => $namaPosko, 'alamat' => $alamat,
                'balita' => $balita,     'lansia' => $lansia,    'hamil'  => $ibuHamil,
                'jiwa'   => $totalJiwa,  'pid'    => $petugasId,
                'lat'    => $lat,        'lng'    => $lng,
                'status' => $statusPosko,'id'     => $poskoId
            ]);
            catatLog($pdo, $_SESSION['user_id'], 'posko', 'EDIT_POSKO',
                "Posko #$poskoId '$namaPosko' diperbarui.");
            $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Posko berhasil diperbarui.'];
        } else {
            // Tambah posko baru
            $pdo->prepare("
                INSERT INTO posko_lapangan
                    (kampanye_id, nama_posko, alamat_posko, jml_balita, jml_lansia,
                     jml_ibu_hamil, total_jiwa, petugas_id, latitude, longitude, status_posko)
                VALUES (:kid, :nama, :alamat, :balita, :lansia, :hamil, :jiwa, :pid, :lat, :lng, :status)
            ")->execute([
                'kid'    => $kampanyeId, 'nama'   => $namaPosko, 'alamat' => $alamat,
                'balita' => $balita,     'lansia' => $lansia,    'hamil'  => $ibuHamil,
                'jiwa'   => $totalJiwa,  'pid'    => $petugasId,
                'lat'    => $lat,        'lng'    => $lng,        'status' => $statusPosko
            ]);
            $newPoskoId = (int) $pdo->lastInsertId();

            // Auto-generate permintaan logistik berdasarkan template jenis bencana
            require_once '../logistik/generate_kebutuhan_otomatis.php';
            $jumlahDibuat = generateKebutuhanOtomatis($pdo, $newPoskoId, $_SESSION['user_id']);

            catatLog($pdo, $_SESSION['user_id'], 'posko', 'TAMBAH_POSKO',
                "Posko baru: $namaPosko ($totalJiwa jiwa). $jumlahDibuat permintaan auto-generated.");

            $_SESSION['flash'] = [
                'type'  => 'success',
                'pesan' => "Posko \"$namaPosko\" berhasil ditambahkan." .
                           ($jumlahDibuat ? " $jumlahDibuat permintaan logistik otomatis dibuat." : '')
            ];
        }
        header('Location: index.php');
        exit;
    }
}

// ── Proses hapus posko ───────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $hid  = (int) $_GET['hapus'];
    $namaP = $pdo->prepare("SELECT nama_posko FROM posko_lapangan WHERE id=:id");
    $namaP->execute(['id' => $hid]);
    $np   = $namaP->fetchColumn();
    $pdo->prepare("DELETE FROM posko_lapangan WHERE id=:id")->execute(['id' => $hid]);
    catatLog($pdo, $_SESSION['user_id'], 'posko', 'HAPUS_POSKO', "Posko #$hid '$np' dihapus.");
    $_SESSION['flash'] = ['type' => 'success', 'pesan' => "Posko \"$np\" berhasil dihapus."];
    header('Location: index.php');
    exit;
}

// ── Ambil data posko ─────────────────────────────────────────────
$poskoList = $pdo->query("
    SELECT p.*, k.nama_bencana, u.nama AS nama_petugas,
           (SELECT COUNT(*) FROM permintaan_logistik pl
            WHERE pl.posko_id = p.id AND pl.status_tiket = 'pending') AS jml_pending,
           (SELECT tingkat_urgensi FROM permintaan_logistik pl2
            WHERE pl2.posko_id = p.id AND pl2.status_tiket = 'pending'
            ORDER BY FIELD(tingkat_urgensi,'krisis','tinggi','normal') ASC
            LIMIT 1) AS urgensi_tertinggi
    FROM posko_lapangan p
    JOIN kampanye_bencana k ON p.kampanye_id = k.id
    LEFT JOIN users u ON p.petugas_id = u.id
    ORDER BY p.created_at DESC
")->fetchAll();

$poskoEdit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM posko_lapangan WHERE id=:id");
    $st->execute(['id' => (int) $_GET['edit']]);
    $poskoEdit = $st->fetch();
}

$urgensiColor = ['normal' => 'success', 'tinggi' => 'warning', 'krisis' => 'danger'];
$statusColor  = ['aktif' => 'success', 'tidak_aktif' => 'secondary'];

include '../../includes/header.php';
?>

<h5 class="fw-bold mb-4"><i class="bi bi-geo-alt me-2"></i>Posko Lapangan</h5>

<div class="row g-4">

  <!-- ── Form Tambah / Edit ─────────────────────────────────────── -->
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">
        <?= $poskoEdit ? 'Edit Posko' : 'Tambah Posko Baru' ?>
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
          <input type="hidden" name="aksi_posko" value="<?= $poskoEdit ? 'edit' : 'tambah' ?>">
          <?php if ($poskoEdit): ?>
            <input type="hidden" name="posko_id" value="<?= $poskoEdit['id'] ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Kampanye Bencana <span class="text-danger">*</span>
            </label>
            <select name="kampanye_id" class="form-select" required>
              <option value="">-- Pilih Kampanye --</option>
              <?php foreach ($kampanyeList as $k): ?>
                <option value="<?= $k['id'] ?>"
                  <?= ($poskoEdit['kampanye_id'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($k['nama_bencana']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Nama Posko <span class="text-danger">*</span>
            </label>
            <input type="text" name="nama_posko" class="form-control" required
              placeholder="cth: Posko Utama GOR Cianjur"
              value="<?= htmlspecialchars($poskoEdit['nama_posko'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Alamat Posko</label>
            <textarea name="alamat_posko" class="form-control" rows="2"><?= htmlspecialchars($poskoEdit['alamat_posko'] ?? '') ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Koordinator Posko (Petugas)</label>
            <select name="petugas_id" class="form-select">
              <option value="">-- Pilih Petugas --</option>
              <?php foreach ($petugasList as $p): ?>
                <option value="<?= $p['id'] ?>"
                  <?= ($poskoEdit['petugas_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['nama']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <label class="form-label fw-semibold">Jumlah Pengungsi</label>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <input type="number" name="jml_balita" class="form-control form-control-sm"
                placeholder="Balita" min="0"
                value="<?= htmlspecialchars($poskoEdit['jml_balita'] ?? '0') ?>">
            </div>
            <div class="col-6">
              <input type="number" name="jml_lansia" class="form-control form-control-sm"
                placeholder="Lansia" min="0"
                value="<?= htmlspecialchars($poskoEdit['jml_lansia'] ?? '0') ?>">
            </div>
            <div class="col-6">
              <input type="number" name="jml_ibu_hamil" class="form-control form-control-sm"
                placeholder="Ibu Hamil" min="0"
                value="<?= htmlspecialchars($poskoEdit['jml_ibu_hamil'] ?? '0') ?>">
            </div>
            <div class="col-6">
              <input type="number" name="total_jiwa" class="form-control form-control-sm"
                placeholder="Total Jiwa *" min="0" required
                value="<?= htmlspecialchars($poskoEdit['total_jiwa'] ?? '0') ?>">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col">
              <label class="form-label small fw-semibold">Latitude</label>
              <input type="number" name="latitude" class="form-control form-control-sm" step="any"
                value="<?= htmlspecialchars($poskoEdit['latitude'] ?? '') ?>">
            </div>
            <div class="col">
              <label class="form-label small fw-semibold">Longitude</label>
              <input type="number" name="longitude" class="form-control form-control-sm" step="any"
                value="<?= htmlspecialchars($poskoEdit['longitude'] ?? '') ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Status Posko</label>
            <select name="status_posko" class="form-select form-select-sm">
              <option value="aktif"
                <?= ($poskoEdit['status_posko'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>
                Aktif
              </option>
              <option value="tidak_aktif"
                <?= ($poskoEdit['status_posko'] ?? '') === 'tidak_aktif' ? 'selected' : '' ?>>
                Tidak Aktif
              </option>
            </select>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-<?= $poskoEdit ? 'warning' : 'primary' ?> btn-sm">
              <i class="bi bi-<?= $poskoEdit ? 'check-circle' : 'plus-circle' ?> me-1"></i>
              <?= $poskoEdit ? 'Simpan Perubahan' : 'Tambah Posko' ?>
            </button>
            <?php if ($poskoEdit): ?>
              <a href="index.php" class="btn btn-outline-secondary btn-sm">Batal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="card mt-3 border-0 bg-light">
      <div class="card-body py-2 px-3">
        <p class="small text-muted mb-0">
          <i class="bi bi-info-circle me-1"></i>
          Saat posko baru ditambahkan, sistem secara otomatis membuat permintaan logistik
          berdasarkan template jenis bencana dan total jiwa pengungsi.
        </p>
      </div>
    </div>
  </div>

  <!-- ── Tabel Daftar Posko ─────────────────────────────────────── -->
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">
        Daftar Posko (<?= count($poskoList) ?>)
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Nama Posko</th>
                <th>Kampanye</th>
                <th>Petugas</th>
                <th class="text-center">Total Jiwa</th>
                <th class="text-center">Urgensi</th>
                <th class="text-center">Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($poskoList)): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    Belum ada posko lapangan.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($poskoList as $i => $p): ?>
                  <tr>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($p['nama_posko']) ?></td>
                    <td class="small"><?= htmlspecialchars($p['nama_bencana']) ?></td>
                    <td class="small"><?= htmlspecialchars($p['nama_petugas'] ?? '-') ?></td>
                    <td class="text-center fw-semibold">
                      <?= number_format($p['total_jiwa'], 0, ',', '.') ?>
                    </td>
                    <td class="text-center">
                      <?php if ($p['urgensi_tertinggi']): ?>
                        <span class="badge bg-<?= $urgensiColor[$p['urgensi_tertinggi']] ?? 'secondary' ?>">
                          <?= strtoupper($p['urgensi_tertinggi']) ?>
                          <?php if ($p['jml_pending'] > 0): ?>
                            <span class="badge bg-white text-dark ms-1">
                              <?= $p['jml_pending'] ?>
                            </span>
                          <?php endif; ?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted small">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-<?= $statusColor[$p['status_posko']] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_', ' ', $p['status_posko'])) ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <a href="index.php?edit=<?= $p['id'] ?>"
                        class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="../../modules/logistik/permintaan.php?posko=<?= $p['id'] ?>"
                        class="btn btn-sm btn-outline-primary" title="Lihat Permintaan">
                        <i class="bi bi-clipboard-check"></i>
                      </a>
                      <a href="index.php?hapus=<?= $p['id'] ?>"
                        class="btn btn-sm btn-outline-danger" title="Hapus"
                        onclick="return confirm('Hapus posko ini? Semua permintaan logistik terkait juga akan terhapus.')">
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
