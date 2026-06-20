<?php
// modules/kampanye/tambah.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';
cekAkses(['admin_pusat']);

$pageTitle = 'Tambah Kampanye Bencana';
$errors    = [];

// Ambil master jenis bencana untuk dropdown
$jenisBencana = $pdo->query(
    "SELECT id, nama_jenis, icon FROM jenis_bencana ORDER BY nama_jenis"
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

    // Validasi
    if (!$nama)             $errors[] = 'Nama bencana wajib diisi.';
    if (!$lokasi)           $errors[] = 'Lokasi wajib diisi.';
    if ($target_dana <= 0)  $errors[] = 'Target dana harus lebih dari 0.';
    if (!in_array($status, ['aktif', 'selesai', 'draf'])) $errors[] = 'Status tidak valid.';

    // Proteksi tipe data INT
    if ($target_dana > 2000000000) {
        $errors[] = 'Target dana terlalu besar. Maksimal adalah Rp 2.000.000.000 (2 Miliar).';
    }

    // Upload foto
    $namaFoto = null;
    if (!empty($_FILES['foto_bencana']['name'])) {
        $ext  = strtolower(pathinfo($_FILES['foto_bencana']['name'], PATHINFO_EXTENSION));
        $izin = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $izin)) {
            $errors[] = 'Format foto harus JPG, PNG, atau WEBP.';
        } elseif ($_FILES['foto_bencana']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $namaFoto = uniqid('bencana_') . '.' . $ext;
            move_uploaded_file(
                $_FILES['foto_bencana']['tmp_name'],
                '../../uploads/foto_bencana/' . $namaFoto
            );
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                INSERT INTO kampanye_bencana
                    (jenis_bencana_id, nama_bencana, lokasi, deskripsi, target_dana,
                     status, latitude, longitude, foto_bencana, created_by, created_at)
                VALUES
                    (:jid, :nama, :lokasi, :deskripsi, :target,
                     :status, :lat, :lng, :foto, :uid, NOW())
            ");
            $stmt->execute([
                'jid'       => $jenis_id ?: null,
                'nama'      => $nama,
                'lokasi'    => $lokasi,
                'deskripsi' => $deskripsi,
                'target'    => $target_dana,
                'status'    => $status,
                'lat'       => $latitude,
                'lng'       => $longitude,
                'foto'      => $namaFoto,
                'uid'       => $_SESSION['user_id'] ?? 1,
            ]);

            $id_kampanye_baru = $pdo->lastInsertId();

            // =========================================================================
            // PROSES SIMULASI OTOMATIS (DONASI UANG & BARANG LANGSUNG MASUK BARENGAN)
            // =========================================================================
            $donatur_id_fix = $_SESSION['user_id'] ?? 1;

            // --- 1. PROSES DATA DONASI UANG ---
            $nominal_dinamis = $target_dana * 0.10;
            $nominal_dinamis = ceil($nominal_dinamis / 50000) * 50000;
            if ($nominal_dinamis < 100000) $nominal_dinamis = 100000;
            if ($nominal_dinamis > 50000000) $nominal_dinamis = 50000000;

            $daftar_bank = ['BCA', 'Mandiri', 'BNI', 'BRI'];
            $bank_acak = $daftar_bank[array_rand($daftar_bank)];

            $sqlDonasiUang = "INSERT INTO donasi_uang (nomor_referensi, user_id, kampanye_id, nominal, bank_asal, status_verifikasi, created_at) 
                              VALUES (:nomor_referensi, :user_id, :kampanye_id, :nominal, :bank_asal, 'terverifikasi', NOW())";
            $stmtDonasiUang = $pdo->prepare($sqlDonasiUang);
            $stmtDonasiUang->execute([
                'nomor_referensi' => 'REF-' . date('Ymd') . rand(100, 999),
                'user_id'         => $donatur_id_fix, 
                'kampanye_id'     => $id_kampanye_baru,    
                'nominal'         => $nominal_dinamis,     
                'bank_asal'       => $bank_acak        
            ]);


            // --- 2. PROSES DATA DONASI BARANG (100% SESUAI ISI SCREENSHOT PHP_MYADMIN KAMU) ---
            $daftar_barang = [
                ['nama' => 'Beras Pandan Wangi', 'kuantitas' => 100, 'kondisi' => 'Baru', 'satuan' => 'Kg'],
                ['nama' => 'Masker Medis 3-Ply', 'kuantitas' => 25, 'kondisi' => 'Baru', 'satuan' => 'Box'],
                ['nama' => 'Mie Instan Kardus', 'kuantitas' => 50, 'kondisi' => 'Baru', 'satuan' => 'Kardus']
            ];
            $barang_pilihan = $daftar_barang[array_rand($daftar_barang)];

            // Menggunakan kolom: nomor_referensi, user_id, kampanye_id, nama_barang, kuantitas, satuan, kondisi_barang, status_logistik, created_at
            $sqlDonasiBarang = "INSERT INTO donasi_barang (nomor_referensi, user_id, kampanye_id, nama_barang, kuantitas, satuan, kondisi_barang, status_logistik, created_at) 
                                VALUES (:nomor_referensi, :user_id, :kampanye_id, :nama_barang, :kuantitas, :satuan, :kondisi_barang, 'pending', NOW())";
            $stmtDonasiBarang = $pdo->prepare($sqlDonasiBarang);
            $stmtDonasiBarang->execute([
                'nomor_referensi' => 'REF-BRG-' . date('Ymd') . rand(100, 999),
                'user_id'         => $donatur_id_fix,
                'kampanye_id'     => $id_kampanye_baru,
                'nama_barang'     => $barang_pilihan['nama'],
                'kuantitas'       => $barang_pilihan['kuantitas'],
                'satuan'          => $barang_pilihan['satuan'],
                'kondisi_barang'  => $barang_pilihan['kondisi']
            ]);
            // =========================================================================

            $pdo->commit();

            catatLog($pdo, $_SESSION['user_id'] ?? 1, 'kampanye', 'TAMBAH_KAMPANYE', "Kampanye baru: $nama di $lokasi");
            $_SESSION['flash'] = [
                'type'  => 'success',
                'pesan' => "Kampanye \"$nama\" berhasil ditambah beserta simulasi otomatis donasi uang & barang!"
            ];
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h5 class="fw-bold mb-0">Tambah Kampanye Bencana</h5>
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
            placeholder="cth: Gempa Bumi Cianjur 2025"
            value="<?= htmlspecialchars($_POST['nama_bencana'] ?? '') ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Jenis Bencana</label>
          <select name="jenis_bencana_id" class="form-select">
            <option value="">-- Pilih Jenis --</option>
            <?php foreach ($jenisBencana as $j): ?>
              <option value="<?= $j['id'] ?>"
                <?= ($_POST['jenis_bencana_id'] ?? '') == $j['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($j['nama_jenis']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Lokasi <span class="text-danger">*</span></label>
          <input type="text" name="lokasi" class="form-control" required
            placeholder="cth: Kabupaten Cianjur, Jawa Barat"
            value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
          <select name="status" class="form-select">
            <option value="draf"    <?= ($_POST['status'] ?? 'draf') === 'draf'    ? 'selected' : '' ?>>Draf</option>
            <option value="aktif"   <?= ($_POST['status'] ?? '')     === 'aktif'   ? 'selected' : '' ?>>Aktif</option>
            <option value="selesai" <?= ($_POST['status'] ?? '')     === 'selesai' ? 'selected' : '' ?>>Selesai</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Target Dana (Rp) <span class="text-danger">*</span></label>
          <input type="number" name="target_dana" class="form-control" required min="1"
            placeholder="0"
            value="<?= htmlspecialchars($_POST['target_dana'] ?? '') ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Deskripsi Situasi</label>
          <textarea name="deskripsi" class="form-control" rows="4"
            placeholder="Jelaskan kondisi bencana, jumlah korban, kebutuhan mendesak..."
          ><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Latitude</label>
          <input type="text" name="latitude" class="form-control"
            placeholder="-6.9147"
            value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
          <div class="form-text">Salin dari Google Maps</div>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Longitude</label>
          <input type="text" name="longitude" class="form-control"
            placeholder="107.6706"
            value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Foto Bencana</label>
          <input type="file" name="foto_bencana" class="form-control" accept="image/*">
          <div class="form-text">Format JPG/PNG/WEBP, maks 2 MB.</div>
        </div>

      </div><hr class="my-4">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-circle me-1"></i> Simpan Kampanye
        </button>
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>