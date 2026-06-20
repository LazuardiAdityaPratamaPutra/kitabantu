<?php
// modules/relawan/jadwal_piket.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';
cekAkses(['admin_pusat', 'petugas_lapangan']);

$pageTitle = 'Jadwal Piket Relawan';
$errors    = [];

// Ambil data untuk dropdown
$relawanList = $pdo->query(
    "SELECT id, nama FROM relawan WHERE status_aktif=1 ORDER BY nama"
)->fetchAll();
$poskoList = $pdo->query(
    "SELECT id, nama_posko FROM posko_lapangan WHERE status_posko='aktif' ORDER BY nama_posko"
)->fetchAll();

// ── Proses tambah / edit jadwal ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_jadwal'])) {
    $aksi        = $_POST['aksi_jadwal']  ?? 'tambah';
    $jid         = (int) ($_POST['jadwal_id']  ?? 0);
    $relawanId   = (int) ($_POST['relawan_id'] ?? 0);
    $poskoId     = (int) ($_POST['posko_id']   ?? 0);
    $tglPiket    = trim($_POST['tgl_piket']    ?? '');
    $shift       = $_POST['shift']             ?? 'pagi';
    $tugas       = trim($_POST['tugas']        ?? '');
    $statusHadir = $_POST['status_hadir']      ?? 'terjadwal';

    if (!$relawanId)  $errors[] = 'Pilih relawan.';
    if (!$poskoId)    $errors[] = 'Pilih posko.';
    if (!$tglPiket)   $errors[] = 'Tanggal piket wajib diisi.';
    if (!in_array($shift, ['pagi', 'siang', 'malam'])) $errors[] = 'Shift tidak valid.';

    if (empty($errors)) {
        if ($aksi === 'edit' && $jid) {
            $pdo->prepare("
                UPDATE jadwal_piket
                SET relawan_id=:rid, posko_id=:pid, tgl_piket=:tgl,
                    shift=:shift, tugas=:tugas, status_hadir=:status
                WHERE id=:id
            ")->execute([
                'rid'    => $relawanId, 'pid'    => $poskoId,
                'tgl'    => $tglPiket,  'shift'  => $shift,
                'tugas'  => $tugas,     'status' => $statusHadir, 'id' => $jid
            ]);
            $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Jadwal piket berhasil diperbarui.'];
        } else {
            $pdo->prepare("
                INSERT INTO jadwal_piket (relawan_id, posko_id, tgl_piket, shift, tugas, status_hadir)
                VALUES (:rid, :pid, :tgl, :shift, :tugas, 'terjadwal')
            ")->execute([
                'rid'   => $relawanId, 'pid'   => $poskoId,
                'tgl'   => $tglPiket,  'shift' => $shift, 'tugas' => $tugas
            ]);
            catatLog($pdo, $_SESSION['user_id'], 'relawan', 'TAMBAH_JADWAL_PIKET',
                "Jadwal piket: relawan #$relawanId di posko #$poskoId tgl $tglPiket ($shift).");
            $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Jadwal piket berhasil ditambahkan.'];
        }
        header('Location: jadwal_piket.php');
        exit;
    }
}

// ── Proses hapus jadwal ──────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $hid = (int) $_GET['hapus'];
    $pdo->prepare("DELETE FROM jadwal_piket WHERE id=:id")->execute(['id' => $hid]);
    $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Jadwal piket berhasil dihapus.'];
    header('Location: jadwal_piket.php');
    exit;
}

// ── Ambil jadwal (filter rentang tanggal) ────────────────────────
$tglMulai  = $_GET['dari']   ?? date('Y-m-d', strtotime('monday this week'));
$tglAkhir  = $_GET['sampai'] ?? date('Y-m-d', strtotime('sunday this week'));

$jadwalList = $pdo->prepare("
    SELECT jp.*, r.nama AS nama_relawan, r.no_hp, p.nama_posko
    FROM jadwal_piket jp
    JOIN relawan r ON jp.relawan_id = r.id
    JOIN posko_lapangan p ON jp.posko_id = p.id
    WHERE jp.tgl_piket BETWEEN :dari AND :sampai
    ORDER BY jp.tgl_piket ASC, FIELD(jp.shift,'pagi','siang','malam')
");
$jadwalList->execute(['dari' => $tglMulai, 'sampai' => $tglAkhir]);
$jadwalList = $jadwalList->fetchAll();

$jadwalEdit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM jadwal_piket WHERE id=:id");
    $st->execute(['id' => (int) $_GET['edit']]);
    $jadwalEdit = $st->fetch();
}

$shiftColor = ['pagi' => 'warning text-dark', 'siang' => 'info text-dark', 'malam' => 'dark'];
$hadirColor = ['terjadwal' => 'secondary', 'hadir' => 'success', 'tidak_hadir' => 'danger'];
$hadirLabel = ['terjadwal' => 'Terjadwal', 'hadir' => 'Hadir', 'tidak_hadir' => 'Tidak Hadir'];

include '../../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h5 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2"></i>Jadwal Piket Relawan</h5>
</div>

<div class="row g-4">

  <!-- ── Form Tambah / Edit Jadwal ─────────────────────────────── -->
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">
        <?= $jadwalEdit ? 'Edit Jadwal' : 'Tambah Jadwal Piket' ?>
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
          <input type="hidden" name="aksi_jadwal" value="<?= $jadwalEdit ? 'edit' : 'tambah' ?>">
          <?php if ($jadwalEdit): ?>
            <input type="hidden" name="jadwal_id" value="<?= $jadwalEdit['id'] ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Relawan <span class="text-danger">*</span>
            </label>
            <select name="relawan_id" class="form-select" required>
              <option value="">-- Pilih Relawan --</option>
              <?php foreach ($relawanList as $r): ?>
                <option value="<?= $r['id'] ?>"
                  <?= ($jadwalEdit['relawan_id'] ?? '') == $r['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($r['nama']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Posko <span class="text-danger">*</span>
            </label>
            <select name="posko_id" class="form-select" required>
              <option value="">-- Pilih Posko --</option>
              <?php foreach ($poskoList as $p): ?>
                <option value="<?= $p['id'] ?>"
                  <?= ($jadwalEdit['posko_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['nama_posko']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Tanggal Piket <span class="text-danger">*</span>
            </label>
            <input type="date" name="tgl_piket" class="form-control" required
              value="<?= htmlspecialchars($jadwalEdit['tgl_piket'] ?? date('Y-m-d')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Shift <span class="text-danger">*</span>
            </label>
            <select name="shift" class="form-select" required>
              <option value="pagi"
                <?= ($jadwalEdit['shift'] ?? '') === 'pagi'  ? 'selected' : '' ?>>
                Pagi (06.00–14.00)
              </option>
              <option value="siang"
                <?= ($jadwalEdit['shift'] ?? '') === 'siang' ? 'selected' : '' ?>>
                Siang (14.00–22.00)
              </option>
              <option value="malam"
                <?= ($jadwalEdit['shift'] ?? '') === 'malam' ? 'selected' : '' ?>>
                Malam (22.00–06.00)
              </option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tugas</label>
            <input type="text" name="tugas" class="form-control"
              placeholder="cth: Distribusi makanan, Medis..."
              value="<?= htmlspecialchars($jadwalEdit['tugas'] ?? '') ?>">
          </div>

          <?php if ($jadwalEdit): ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Status Kehadiran</label>
            <select name="status_hadir" class="form-select">
              <option value="terjadwal"
                <?= ($jadwalEdit['status_hadir'] ?? '') === 'terjadwal'    ? 'selected' : '' ?>>
                Terjadwal
              </option>
              <option value="hadir"
                <?= ($jadwalEdit['status_hadir'] ?? '') === 'hadir'        ? 'selected' : '' ?>>
                Hadir
              </option>
              <option value="tidak_hadir"
                <?= ($jadwalEdit['status_hadir'] ?? '') === 'tidak_hadir'  ? 'selected' : '' ?>>
                Tidak Hadir
              </option>
            </select>
          </div>
          <?php endif; ?>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-<?= $jadwalEdit ? 'warning' : 'primary' ?> btn-sm">
              <i class="bi bi-<?= $jadwalEdit ? 'check-circle' : 'plus-circle' ?> me-1"></i>
              <?= $jadwalEdit ? 'Simpan' : 'Tambah Jadwal' ?>
            </button>
            <?php if ($jadwalEdit): ?>
              <a href="jadwal_piket.php" class="btn btn-outline-secondary btn-sm">Batal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Tabel Jadwal ───────────────────────────────────────────── -->
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
          <span class="fw-semibold me-1">Tampilkan tanggal:</span>
          <input type="date" name="dari"   class="form-control form-control-sm" style="width:auto"
            value="<?= $tglMulai ?>">
          <span class="text-muted">s/d</span>
          <input type="date" name="sampai" class="form-control form-control-sm" style="width:auto"
            value="<?= $tglAkhir ?>">
          <button type="submit" class="btn btn-sm btn-primary">Tampilkan</button>
        </form>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Shift</th>
                <th>Relawan</th>
                <th>Posko</th>
                <th>Tugas</th>
                <th>Kehadiran</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($jadwalList)): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    Tidak ada jadwal piket pada rentang tanggal ini.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($jadwalList as $i => $j): ?>
                  <tr>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td class="fw-semibold small">
                      <?= date('d/m/Y', strtotime($j['tgl_piket'])) ?>
                    </td>
                    <td>
                      <span class="badge bg-<?= $shiftColor[$j['shift']] ?? 'secondary' ?>">
                        <?= ucfirst($j['shift']) ?>
                      </span>
                    </td>
                    <td>
                      <div class="fw-semibold small">
                        <?= htmlspecialchars($j['nama_relawan']) ?>
                      </div>
                      <div class="text-muted" style="font-size:.75rem">
                        <?= htmlspecialchars($j['no_hp']) ?>
                      </div>
                    </td>
                    <td class="small"><?= htmlspecialchars($j['nama_posko']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($j['tugas'] ?? '-') ?></td>
                    <td>
                      <span class="badge bg-<?= $hadirColor[$j['status_hadir']] ?? 'secondary' ?>">
                        <?= $hadirLabel[$j['status_hadir']] ?? $j['status_hadir'] ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <a href="jadwal_piket.php?edit=<?= $j['id'] ?>"
                        class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="jadwal_piket.php?hapus=<?= $j['id'] ?>"
                        class="btn btn-sm btn-outline-danger" title="Hapus"
                        onclick="return confirm('Hapus jadwal piket ini?')">
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
