<?php
// landing/daftar_relawan.php — Form pendaftaran relawan publik (tanpa login)
require '../config/koneksi.php';

$errors = [];
$sukses = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $noHp     = trim($_POST['no_hp']    ?? '');
    $email    = trim($_POST['email']    ?? '');
    $keahlian = trim($_POST['keahlian'] ?? '');
    $alamat   = trim($_POST['alamat']   ?? '');

    // Validasi
    if (!$nama)               $errors[] = 'Nama lengkap wajib diisi.';
    if (!$noHp)               $errors[] = 'Nomor HP wajib diisi.';
    if (strlen($noHp) < 9)   $errors[] = 'Nomor HP tidak valid (minimal 9 digit).';

    // Cek no_hp sudah terdaftar
    if (empty($errors)) {
        $cek = $pdo->prepare("SELECT id FROM relawan WHERE no_hp=:hp LIMIT 1");
        $cek->execute(['hp' => $noHp]);
        if ($cek->fetchColumn()) {
            $errors[] = 'Nomor HP tersebut sudah terdaftar sebagai relawan.';
        }
    }

    if (empty($errors)) {
        $pdo->prepare("
            INSERT INTO relawan (nama, no_hp, email, keahlian, alamat, status_aktif)
            VALUES (:nama, :hp, :email, :keahlian, :alamat, 1)
        ")->execute([
            'nama'     => $nama,
            'hp'       => $noHp,
            'email'    => $email    ?: null,
            'keahlian' => $keahlian ?: null,
            'alamat'   => $alamat   ?: null,
        ]);
        $sukses = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar Relawan — KitaBantu</title>
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

<div class="container py-5" style="max-width:600px">

  <?php if ($sukses): ?>
    <!-- ── Halaman sukses pendaftaran ────────────────────────── -->
    <div class="card shadow border-0 text-center p-5">
      <div class="text-success mb-3">
        <i class="bi bi-person-check-fill" style="font-size:4rem"></i>
      </div>
      <h4 class="fw-bold">Pendaftaran Berhasil!</h4>
      <p class="text-muted">
        Terima kasih telah mendaftarkan diri sebagai relawan KitaBantu.
        Tim kami akan menghubungi Anda melalui nomor HP yang didaftarkan.
      </p>
      <a href="index.php" class="btn btn-primary mt-2">Kembali ke Halaman Utama</a>
    </div>

  <?php else: ?>
    <!-- ── Form pendaftaran ───────────────────────────────────── -->
    <h4 class="fw-bold mb-1">
      <i class="bi bi-person-plus me-2"></i>Daftar Jadi Relawan
    </h4>
    <p class="text-muted mb-4">
      Bergabunglah dengan ribuan relawan KitaBantu dan bantu sesama yang membutuhkan.
    </p>

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
        <form method="POST">

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Nama Lengkap <span class="text-danger">*</span>
            </label>
            <input type="text" name="nama" class="form-control" required
              placeholder="Nama sesuai KTP"
              value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Nomor HP / WhatsApp <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-whatsapp text-success"></i>
              </span>
              <input type="tel" name="no_hp" class="form-control" required
                placeholder="08xxxxxxxxxx"
                value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
            </div>
            <div class="form-text">
              Kami akan menghubungi Anda melalui WhatsApp ini.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Email (Opsional)</label>
            <input type="email" name="email" class="form-control"
              placeholder="email@contoh.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Keahlian / Bidang</label>
            <input type="text" name="keahlian" class="form-control"
              placeholder="cth: Medis, Logistik, Dapur Umum, SAR, Psikologi..."
              value="<?= htmlspecialchars($_POST['keahlian'] ?? '') ?>">
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Alamat Domisili</label>
            <textarea name="alamat" class="form-control" rows="2"
              placeholder="Kota/Kabupaten domisili Anda..."
            ><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
          </div>

          <!-- Pernyataan kesediaan -->
          <div class="mb-4">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="setuju" required>
              <label class="form-check-label small" for="setuju">
                Saya bersedia dihubungi oleh tim KitaBantu dan siap berpartisipasi
                sebagai relawan saat dibutuhkan.
              </label>
            </div>
          </div>

          <button type="submit" class="btn btn-success w-100 btn-lg">
            <i class="bi bi-person-check me-2"></i>Daftar Sebagai Relawan
          </button>

        </form>
      </div>
    </div>

  <?php endif; ?>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
