<?php
// ajax/ambil_notifikasi.php
session_start();
require '../config/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']); exit;
}

$uid = $_SESSION['user_id'];

// Ambil 10 notifikasi terbaru
$stmt = $pdo->prepare("
    SELECT id, judul, pesan, jenis, link_tujuan, is_read, created_at
    FROM notifikasi
    WHERE user_id = :uid
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute(['uid' => $uid]);

// Hitung yang belum dibaca
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = :uid AND is_read = 0");
$cntStmt->execute(['uid' => $uid]);

// Tandai semua sebagai sudah dibaca setelah diambil
$pdo->prepare("UPDATE notifikasi SET is_read = 1 WHERE user_id = :uid AND is_read = 0")
    ->execute(['uid' => $uid]);

echo json_encode([
    'notifikasi'   => $stmt->fetchAll(),
    'belum_dibaca' => (int) $cntStmt->fetchColumn(),
]);