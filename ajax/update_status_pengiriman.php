<?php
// ajax/update_status_pengiriman.php
// Endpoint AJAX untuk menambah baris timeline pengiriman
session_start();
require '../config/koneksi.php';
require '../includes/cek_akses.php';
require '../includes/log_helper.php';
cekLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Method tidak diizinkan']); exit;
}

$pengiriman_id = (int) ($_POST['pengiriman_id'] ?? 0);
$status        = trim($_POST['status'] ?? '');
$catatan       = trim($_POST['catatan'] ?? '');

if (!$pengiriman_id || !$status) {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Data tidak lengkap']); exit;
}

// Validasi status yang diizinkan
$statusDiizinkan = ['Dalam Perjalanan', 'Tiba di Posko', 'Serah Terima Selesai'];
if (!in_array($status, $statusDiizinkan)) {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Status tidak valid']); exit;
}

try {
    $pdo->beginTransaction();

    // Insert ke riwayat timeline
    $pdo->prepare("
        INSERT INTO riwayat_status_pengiriman (pengiriman_id, status, catatan, dicatat_oleh)
        VALUES (:pid, :status, :catatan, :uid)
    ")->execute([
        'pid'     => $pengiriman_id,
        'status'  => $status,
        'catatan' => $catatan ?: null,
        'uid'     => $_SESSION['user_id'],
    ]);

    // Jika status "Tiba di Posko" → update tgl_sampai & status permintaan
    if ($status === 'Tiba di Posko') {
        $pdo->prepare("UPDATE pengiriman_armada SET tgl_sampai = NOW() WHERE id = :id")
            ->execute(['id' => $pengiriman_id]);

        $getPermintaan = $pdo->prepare("SELECT permintaan_id FROM pengiriman_armada WHERE id = :id");
        $getPermintaan->execute(['id' => $pengiriman_id]);
        $permintaanId = $getPermintaan->fetchColumn();

        if ($permintaanId) {
            $pdo->prepare("UPDATE permintaan_logistik SET status_tiket = 'diterima', updated_at = NOW() WHERE id = :id")
                ->execute(['id' => $permintaanId]);
        }
    }

    $pdo->commit();

    catatLog($pdo, $_SESSION['user_id'], 'pengiriman', 'UPDATE_STATUS_PENGIRIMAN',
        "Status pengiriman #$pengiriman_id diperbarui menjadi '$status'");

    echo json_encode(['status' => 'ok', 'pesan' => 'Status berhasil diperbarui.']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'gagal', 'pesan' => $e->getMessage()]);
}

