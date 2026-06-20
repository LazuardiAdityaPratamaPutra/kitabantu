<?php
// landing/form_donasi.php — Form donasi uang publik (tidak perlu login)
require '../config/koneksi.php';

$kampanyeId    = (int) ($_GET['kampanye'] ?? 0);
$errors        = [];
$sukses        = false;
$nomorRef_tampil = '';

// Ambil semua kampanye aktif untuk dropdown
$semuaKampanye = $pdo->query("
    SELECT id, nama_bencana, lokasi
    FROM kampanye_bencana
    WHERE status = 'aktif'
    ORDER BY nama_bencana
")->fetchAll();

// Ambil detail kampanye yang dipilih (jika ada dari URL)
$kampanyeTerpilih = null;
if ($kampanyeId) {
    $stmtK = $pdo->prepare("SELECT * FROM kampanye_bencana WHERE id=:id AND status='aktif'");
    $stmtK->execute(['id' => $kampanyeId]);
    $kampanyeTerpilih = $stmtK->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kampanyeId  = (int) ($_POST['kampanye_id']    ?? 0);
    $namaLengkap = trim($_POST['nama_lengkap']     ?? '');
    $emailForm   = trim($_POST['email']            ?? '');
    $nominal     = (float) str_replace(['.', ','], ['', '.'], $_POST['nominal'] ?? '0');
    $bankAsal    = trim($_POST['bank_asal']         ?? '');
    $noRek       = trim($_POST['nomor_rekening']    ?? '');
    $atasNama    = trim($_POST['atas_nama']         ?? '');
    $catatan     = trim($_POST['catatan']           ?? '');

    // Validasi
    if (!$kampanyeId)        $errors[] = 'Pilih kampanye yang ingin didonasikan.';
    if (!$namaLengkap)       $errors[] = 'Nama lengkap wajib diisi.';
    if ($nominal < 10000)    $errors[] = 'Nominal donasi minimal Rp 10.000.';
    if (!$bankAsal)          $errors[] = 'Nama bank wajib diisi.';

    // Upload bukti transfer
    $namaBukti = null;
    if (!empty($_FILES['bukti_transfer']['name'])) {
        $ext  = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
        $izin = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $izin)) {
            $errors[] = 'Format bukti transfer harus JPG, PNG, atau PDF.';
        } elseif ($_FILES['bukti_transfer']['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Ukuran file maksimal 3 MB.';
        } else {
            $namaBukti = uniqid('bukti_') . '.' . $ext;
            move_uploaded_file(
                $_FILES['bukti_transfer']['tmp_name'],
                '../uploads/bukti_transfer/' . $namaBukti
            );
        }
    }

    if (empty($errors)) {
        // Cari user_id jika email terdaftar sebagai donatur
        $userId = null;
        if ($emailForm) {
            $cekUser = $pdo->prepare(
                "SELECT id FROM users WHERE email=:email AND role='donatur' LIMIT 1"
            );
            $cekUser->execute(['email' => $emailForm]);
            $userId = $cekUser->fetchColumn() ?: null;
        }

        // Generate nomor referensi unik
        $nomorRef = 'KB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        $pdo->prepare("
            INSERT INTO donasi_uang
                (nomor_referensi, user_id, kampanye_id, nominal, bank_asal,
                 nomor_rekening_asal, atas_nama, bukti_transfer,
                 catatan_donatur, status_verifikasi)
            VALUES
                (:ref, :uid, :kid, :nominal, :bank,
                 :norek, :atns, :bukti,
                 :catatan, 'pending')
        ")->execute([
            'ref'     => $nomorRef,
            'uid'     => $userId,
            'kid'     => $kampanyeId,
            'nominal' => $nominal,
            'bank'    => $bankAsal,
            'norek'   => $noRek ?: null,
            'atns'    => $atasNama ?: $namaLengkap,
            'bukti'   => $namaBukti,
            'catatan' => $catatan ?: null,
        ]);

        $sukses          = true;
        $nomorRef_tampil = $nomorRef;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Form Donasi — KitaBantu</title>
  <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>body { background: #f4f6f9; }</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-dark bg-primary px-4">
  <a class="navbar-brand fw-bold" href="index.php">🆘 KitaBantu</a>
  <a href="index.php" class="btn btn-outline-light btn-sm">← Kembali</a>
</nav>

<div class="container py-5" style="max-width:700px">

  <?php if ($sukses): ?>
    <!-- ── Halaman sukses donasi ─────────────────────────────── -->
    <div class="card shadow border-0 text-center p-5">
      <div class="text-success mb-3">
        <i class="bi bi-check-circle-fill" style="font-size:4rem"></i>
      </div>
      <h4 class="fw-bold">Donasi Berhasil Dikirim!</h4>
      <p class="text-muted">
        Terima kasih atas kepedulian Anda. Donasi akan segera diverifikasi oleh tim kami.
      </p>
      <div class="alert alert-info mx-auto" style="max-width:360px">
        <div class="small text-muted">Nomor Referensi Anda</div>
        <div class="fw-bold fs-5"><?= htmlspecialchars($nomorRef_tampil) ?></div>
        <div class="small text-muted mt-1">
          Simpan nomor ini untuk pelacakan donasi Anda.
        </div>
      </div>
      <a href="index.php" class="btn btn-primary mt-2">Kembali ke Halaman Utama</a>
    </div>

  <?php else: ?>
    <!-- ── Form donasi ────────────────────────────────────────── -->
    <h4 class="fw-bold mb-1">Form Donasi Uang</h4>
    <?php if ($kampanyeTerpilih): ?>
      <p class="text-muted mb-4">
        Untuk kampanye: <strong><?= htmlspecialchars($kampanyeTerpilih['nama_bencana']) ?></strong>
        — <?= htmlspecialchars($kampanyeTerpilih['lokasi']) ?>
      </p>
    <?php else: ?>
      <p class="text-muted mb-4">Pilih kampanye bencana yang ingin Anda bantu.</p>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card shadow border-0">
      <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">

          <!-- Pilih Kampanye -->
          <div class="mb-3">
            <label class="form-label fw-semibold">
              Kampanye Bencana <span class="text-danger">*</span>
            </label>
            <select name="kampanye_id" class="form-select" required>
              <option value="">-- Pilih Kampanye --</option>
              <?php foreach ($semuaKampanye as $k): ?>
                <option value="<?= $k['id'] ?>"
                  <?= ($kampanyeId == $k['id'] || ($_POST['kampanye_id'] ?? 0) == $k['id'])
                      ? 'selected' : '' ?>>
                  <?= htmlspecialchars($k['nama_bencana']) ?>
                  (<?= htmlspecialchars($k['lokasi']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <hr class="my-3">
          <h6 class="fw-semibold mb-3 text-muted">Data Donatur</h6>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">
                Nama Lengkap <span class="text-danger">*</span>
              </label>
              <input type="text" name="nama_lengkap" class="form-control" required
                placeholder="Nama sesuai rekening"
                value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Email (Opsional)</label>
              <input type="email" name="email" class="form-control"
                placeholder="untuk konfirmasi donasi"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
          </div>

          <hr class="my-3">
          <h6 class="fw-semibold mb-3 text-muted">Data Transfer</h6>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">
                Nominal Donasi (Rp) <span class="text-danger">*</span>
              </label>
              <input type="number" name="nominal" class="form-control" required min="10000"
                placeholder="Min. 10.000"
                value="<?= htmlspecialchars($_POST['nominal'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">
                Bank Asal <span class="text-danger">*</span>
              </label>
              <select name="bank_asal" class="form-select" required>
                <option value="">-- Pilih Bank --</option>
                <?php foreach (['BCA','BNI','BRI','Mandiri','BSI','BTPN','Permata','CIMB','Danamon','Lainnya'] as $b): ?>
                  <option value="<?= $b ?>"
                    <?= ($_POST['bank_asal'] ?? '') === $b ? 'selected' : '' ?>>
                    <?= $b ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Atas Nama Pengirim</label>
              <input type="text" name="atas_nama" class="form-control"
                placeholder="(jika berbeda dari nama di atas)"
                value="<?= htmlspecialchars($_POST['atas_nama'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nomor Rekening Asal</label>
              <input type="text" name="nomor_rekening" class="form-control"
                placeholder="(opsional)"
                value="<?= htmlspecialchars($_POST['nomor_rekening'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Upload Bukti Transfer</label>
              <input type="file" name="bukti_transfer" class="form-control"
                accept="image/*,.pdf">
              <div class="form-text">Format JPG/PNG/PDF, maks 3 MB</div>
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label fw-semibold">Pesan / Catatan (Opsional)</label>
            <textarea name="catatan" class="form-control" rows="2"
              placeholder="Doa atau pesan untuk para korban bencana..."
            ><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
          </div>

          <!-- Info rekening tujuan -->
          <div class="alert alert-info">
            <h6 class="fw-bold mb-2">
              <i class="bi bi-bank me-1"></i>Rekening Tujuan KitaBantu
            </h6>
            <div>BCA — <strong>1234567890</strong> — a.n. Yayasan KitaBantu Indonesia</div>
            <div>BNI — <strong>0987654321</strong> — a.n. Yayasan KitaBantu Indonesia</div>
            <div class="mt-1 small text-muted">
              Transfer tepat sesuai nominal lalu upload bukti di atas.
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 btn-lg">
            <i class="bi bi-heart-fill me-2"></i>Kirim Donasi
          </button>

        </form>
      </div>
    </div>

  <?php endif; ?>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
