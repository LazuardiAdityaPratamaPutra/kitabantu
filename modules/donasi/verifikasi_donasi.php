<?php
// modules/donasi/verifikasi_donasi.php
// (Verifikasi langsung — tanpa approval berjenjang, untuk kasus sederhana)
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';

cekAkses(['admin_pusat', 'admin_keuangan']);

header('Content-Type: application/json');

$donasi_id = (int) ($_POST['donasi_id'] ?? 0);
$aksi      = $_POST['aksi'] ?? '';           // 'terverifikasi' atau 'ditolak'
$alasan    = trim($_POST['alasan'] ?? '');

if (!$donasi_id || !in_array($aksi, ['terverifikasi', 'ditolak'])) {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Parameter tidak valid']); exit;
}

try {
    $pdo->beginTransaction();

    $cek = $pdo->prepare("SELECT * FROM donasi_uang WHERE id = :id AND status_verifikasi IN ('pending','menunggu_approval') FOR UPDATE");
    $cek->execute(['id' => $donasi_id]);
    $donasi = $cek->fetch();

    if (!$donasi) throw new Exception('Donasi tidak ditemukan atau sudah diproses.');

    // Update status donasi
    $pdo->prepare("
        UPDATE donasi_uang
        SET status_verifikasi = :aksi,
            disetujui_oleh    = :uid,
            tgl_disetujui     = NOW(),
            alasan_penolakan  = :alasan
        WHERE id = :id
    ")->execute(['aksi' => $aksi, 'uid' => $_SESSION['user_id'], 'alasan' => $alasan ?: null, 'id' => $donasi_id]);

    // Jika terverifikasi, tambahkan ke dana_terkumpul kampanye
    if ($aksi === 'terverifikasi') {
        $pdo->prepare("
            UPDATE kampanye_bencana
            SET dana_terkumpul = dana_terkumpul + :nominal
            WHERE id = :kid
        ")->execute(['nominal' => $donasi['nominal'], 'kid' => $donasi['kampanye_id']]);
    }

    $pdo->commit();

    catatLog($pdo, $_SESSION['user_id'], 'donasi', strtoupper($aksi) . '_DONASI',
        "Donasi #$donasi_id sebesar Rp " . number_format($donasi['nominal'], 0, ',', '.') . " di-$aksi.");

    echo json_encode(['status' => 'ok', 'pesan' => "Donasi berhasil di-$aksi."]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'gagal', 'pesan' => $e->getMessage()]);
}
