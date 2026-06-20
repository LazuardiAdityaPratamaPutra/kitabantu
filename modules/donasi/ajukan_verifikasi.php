<?php
// modules/donasi/ajukan_verifikasi.php
// Staf keuangan mengajukan donasi untuk disetujui admin pusat
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/notifikasi_helper.php';

cekAkses(['admin_keuangan', 'admin_pusat']);
header('Content-Type: application/json');

$donasi_id = (int) ($_POST['donasi_id'] ?? 0);
if (!$donasi_id) { echo json_encode(['status' => 'gagal', 'pesan' => 'ID donasi tidak valid']); exit; }

$stmt = $pdo->prepare("
    UPDATE donasi_uang
    SET status_verifikasi = 'menunggu_approval',
        diajukan_oleh     = :uid,
        tgl_diajukan      = NOW()
    WHERE id = :id AND status_verifikasi = 'pending'
");
$stmt->execute(['uid' => $_SESSION['user_id'], 'id' => $donasi_id]);

if ($stmt->rowCount() > 0) {
    notifikasiSemuaAdminPusat(
        $pdo,
        '📋 Persetujuan Donasi Diperlukan',
        "Donasi #$donasi_id menunggu persetujuan akhir dari Admin Pusat.",
        'warning',
        "/kitabantu/modules/donasi/donasi_uang.php?id=$donasi_id"
    );
    echo json_encode(['status' => 'ok', 'pesan' => 'Berhasil diajukan. Menunggu persetujuan Admin Pusat.']);
} else {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Donasi tidak ditemukan atau sudah diproses.']);
}