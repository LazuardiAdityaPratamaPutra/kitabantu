<?php
// modules/donasi/approve_final.php
// Hanya admin_pusat yang bisa menyetujui akhir, dan TIDAK boleh approve pengajuan sendiri
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';

cekAkses(['admin_pusat']);
header('Content-Type: application/json');

$donasi_id = (int) ($_POST['donasi_id'] ?? 0);
$aksi      = $_POST['aksi'] ?? ''; // 'setujui' atau 'tolak'

try {
    $pdo->beginTransaction();

    $cek = $pdo->prepare("
        SELECT * FROM donasi_uang
        WHERE id = :id AND status_verifikasi = 'menunggu_approval'
        FOR UPDATE
    ");
    $cek->execute(['id' => $donasi_id]);
    $donasi = $cek->fetch();

    if (!$donasi) throw new Exception('Donasi tidak ditemukan atau tidak dalam status menunggu persetujuan.');

    // Segregation of duty: tidak boleh setujui pengajuan sendiri
    if ($aksi === 'setujui' && $donasi['diajukan_oleh'] == $_SESSION['user_id']) {
        throw new Exception('Anda tidak bisa menyetujui pengajuan yang Anda buat sendiri.');
    }

    $statusBaru = ($aksi === 'setujui') ? 'terverifikasi' : 'ditolak';
    $pdo->prepare("
        UPDATE donasi_uang
        SET status_verifikasi = :status, disetujui_oleh = :uid, tgl_disetujui = NOW()
        WHERE id = :id
    ")->execute(['status' => $statusBaru, 'uid' => $_SESSION['user_id'], 'id' => $donasi_id]);

    if ($aksi === 'setujui') {
        $pdo->prepare("
            UPDATE kampanye_bencana SET dana_terkumpul = dana_terkumpul + :nominal WHERE id = :kid
        ")->execute(['nominal' => $donasi['nominal'], 'kid' => $donasi['kampanye_id']]);
    }

    $pdo->commit();

    catatLog($pdo, $_SESSION['user_id'], 'donasi',
        ($aksi === 'setujui' ? 'APPROVE_DONASI' : 'TOLAK_DONASI'),
        "Donasi #$donasi_id $statusBaru. Nominal: Rp " . number_format($donasi['nominal'],0,',','.')
    );

    echo json_encode(['status' => 'ok', 'pesan' => "Donasi berhasil di-$statusBaru."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'gagal', 'pesan' => $e->getMessage()]);
}