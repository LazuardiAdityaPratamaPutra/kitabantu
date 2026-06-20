<?php
// includes/notifikasi_helper.php

function buatNotifikasi(
    PDO     $pdo,
    int     $user_id,
    string  $judul,
    string  $pesan,
    string  $jenis = 'info',
    ?string $link  = null
): void {
    $stmt = $pdo->prepare("
        INSERT INTO notifikasi (user_id, judul, pesan, jenis, link_tujuan)
        VALUES (:uid, :judul, :pesan, :jenis, :link)
    ");
    $stmt->execute([
        'uid'   => $user_id,
        'judul' => $judul,
        'pesan' => $pesan,
        'jenis' => $jenis,
        'link'  => $link,
    ]);
}

function notifikasiSemuaAdminPusat(
    PDO     $pdo,
    string  $judul,
    string  $pesan,
    string  $jenis = 'warning',
    ?string $link  = null
): void {
    $admins = $pdo->query("SELECT id FROM users WHERE role IN ('admin_pusat') AND is_aktif = 1")
                  ->fetchAll(PDO::FETCH_COLUMN);
    foreach ($admins as $adminId) {
        buatNotifikasi($pdo, $adminId, $judul, $pesan, $jenis, $link);
    }
}