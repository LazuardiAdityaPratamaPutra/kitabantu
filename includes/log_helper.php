<?php
// includes/log_helper.php

function catatLog(
    PDO    $pdo,
    int    $user_id,
    string $modul,
    string $aksi,
    string $deskripsi,
    array  $sebelum = [],
    array  $sesudah = []
): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO log_aktivitas (user_id, aksi, modul, deskripsi, data_sebelum, data_sesudah, ip_address)
            VALUES (:uid, :aksi, :modul, :deskripsi, :sebelum, :sesudah, :ip)
        ");
        $stmt->execute([
            'uid'       => $user_id,
            'aksi'      => $aksi,
            'modul'     => $modul,
            'deskripsi' => $deskripsi,
            'sebelum'   => $sebelum ? json_encode($sebelum) : null,
            'sesudah'   => $sesudah ? json_encode($sesudah) : null,
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    } catch (PDOException $e) {
        // Log error ke file, jangan tampilkan ke user
        error_log("catatLog error: " . $e->getMessage());
    }
}

