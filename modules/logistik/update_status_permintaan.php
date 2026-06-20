<?php
// modules/logistik/update_status_permintaan.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';
require '../../includes/notifikasi_helper.php';

cekAkses(['admin_pusat', 'admin_logistik']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Method tidak diizinkan']); exit;
}

$permintaan_id = (int) ($_POST['permintaan_id'] ?? 0);
$gudang_id     = (int) ($_POST['gudang_id']     ?? 0);
$nama_supir    = trim($_POST['nama_supir']    ?? '');
$no_kendaraan  = trim($_POST['no_kendaraan']  ?? '');
$jenis_armada  = trim($_POST['jenis_armada']  ?? '');

if (!$permintaan_id || !$gudang_id || !$nama_supir || !$no_kendaraan || !$jenis_armada) {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Data tidak lengkap']); exit;
}

try {
    $pdo->beginTransaction();

    // 1. Ambil data permintaan (kunci baris agar tidak ada race condition)
    $stmt = $pdo->prepare("
        SELECT pl.*, p.posko_id
        FROM permintaan_logistik pl
        JOIN posko_lapangan p ON pl.posko_id = p.id
        WHERE pl.id = :id AND pl.status_tiket = 'diproses'
        FOR UPDATE
    ");
    $stmt->execute(['id' => $permintaan_id]);
    $permintaan = $stmt->fetch();

    if (!$permintaan) {
        throw new Exception('Permintaan tidak ditemukan atau status tidak sesuai (harus "diproses").');
    }

    // 2. Cek & kurangi stok di gudang
    $cekStok = $pdo->prepare("
        SELECT id, kuantitas FROM stok_inventaris
        WHERE gudang_id = :gid AND nama_barang = :barang
        LIMIT 1 FOR UPDATE
    ");
    $cekStok->execute(['gid' => $gudang_id, 'barang' => $permintaan['barang_diminta']]);
    $stok = $cekStok->fetch();

    if (!$stok) {
        throw new Exception("Barang '{$permintaan['barang_diminta']}' tidak ditemukan di gudang yang dipilih.");
    }
    if ($stok['kuantitas'] < $permintaan['kuantitas']) {
        throw new Exception("Stok tidak mencukupi. Tersedia: {$stok['kuantitas']} {$permintaan['satuan']}, dibutuhkan: {$permintaan['kuantitas']}.");
    }

    $pdo->prepare("UPDATE stok_inventaris SET kuantitas = kuantitas - :qty WHERE id = :sid")
        ->execute(['qty' => $permintaan['kuantitas'], 'sid' => $stok['id']]);

    // 3. Update status permintaan → dikirim
    $pdo->prepare("UPDATE permintaan_logistik SET status_tiket = 'dikirim', updated_at = NOW() WHERE id = :id")
        ->execute(['id' => $permintaan_id]);

    // 4. Insert record pengiriman armada
    $insArmada = $pdo->prepare("
        INSERT INTO pengiriman_armada (permintaan_id, nama_supir, no_kendaraan, jenis_armada, tgl_kirim)
        VALUES (:pid, :supir, :kend, :armada, NOW())
    ");
    $insArmada->execute([
        'pid'    => $permintaan_id,
        'supir'  => $nama_supir,
        'kend'   => $no_kendaraan,
        'armada' => $jenis_armada,
    ]);
    $pengiriman_id = $pdo->lastInsertId();

    // 5. Insert baris pertama timeline pengiriman
    $pdo->prepare("
        INSERT INTO riwayat_status_pengiriman (pengiriman_id, status, catatan, dicatat_oleh)
        VALUES (:pid, 'Disiapkan & Dikirim dari Gudang', :catatan, :uid)
    ")->execute([
        'pid'    => $pengiriman_id,
        'catatan'=> "Dikirim memakai $jenis_armada oleh $nama_supir (No. Kend: $no_kendaraan)",
        'uid'    => $_SESSION['user_id'],
    ]);

    // 6. Notifikasi ke petugas lapangan di posko terkait
    $petugas = $pdo->prepare("SELECT petugas_id FROM posko_lapangan WHERE id = :pid");
    $petugas->execute(['pid' => $permintaan['posko_id']]);
    $petugasId = $petugas->fetchColumn();
    if ($petugasId) {
        buatNotifikasi($pdo, $petugasId, 'Logistik Dikirim',
            "Permintaan #{$permintaan_id} telah dikirim via $jenis_armada. Harap konfirmasi penerimaan.",
            'success', "/kitabantu/modules/pengiriman/lacak_pengiriman.php?id=$pengiriman_id");
    }

    $pdo->commit();

    // 7. Audit log (setelah commit agar tidak ikut rollback)
    catatLog($pdo, $_SESSION['user_id'], 'logistik', 'KIRIM_BARANG',
        "Permintaan #$permintaan_id dikirim ke posko #{$permintaan['posko_id']} via $jenis_armada");

    echo json_encode(['status' => 'ok', 'pesan' => 'Pengiriman berhasil diproses.', 'pengiriman_id' => $pengiriman_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'gagal', 'pesan' => $e->getMessage()]);
}
