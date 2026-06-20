<?php
// modules/kampanye/hapus.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
require '../../includes/log_helper.php';
cekAkses(['admin_pusat']);

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['flash'] = ['type' => 'danger', 'pesan' => 'ID kampanye tidak valid.'];
    header('Location: index.php');
    exit;
}

// Ambil data sebelum hapus untuk keperluan log dan hapus file foto
$stmt = $pdo->prepare("SELECT nama_bencana, foto_bencana FROM kampanye_bencana WHERE id = :id");
$stmt->execute(['id' => $id]);
$kampanye = $stmt->fetch();

if (!$kampanye) {
    $_SESSION['flash'] = ['type' => 'danger', 'pesan' => 'Kampanye tidak ditemukan.'];
    header('Location: index.php');
    exit;
}

// Hapus file foto jika ada
if ($kampanye['foto_bencana'] &&
    file_exists('../../uploads/foto_bencana/' . $kampanye['foto_bencana'])) {
    unlink('../../uploads/foto_bencana/' . $kampanye['foto_bencana']);
}

// Hapus dari database (ON DELETE CASCADE menghapus donasi terkait secara otomatis)
$pdo->prepare("DELETE FROM kampanye_bencana WHERE id = :id")->execute(['id' => $id]);

catatLog($pdo, $_SESSION['user_id'], 'kampanye', 'HAPUS_KAMPANYE',
    "Kampanye #{$id} '{$kampanye['nama_bencana']}' dihapus.");

$_SESSION['flash'] = [
    'type'  => 'success',
    'pesan' => "Kampanye \"{$kampanye['nama_bencana']}\" berhasil dihapus."
];
header('Location: index.php');
exit;
