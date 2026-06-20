<?php
// modules/donasi/seed_donasi.php
session_start();
require '../../config/koneksi.php';

try {
    // 1. Ambil salah satu ID kampanye bencana yang ada
    $kampanye = $pdo->query("SELECT id FROM kampanye_bencana LIMIT 1")->fetch();
    
    // 2. Ambil salah satu ID user (donatur/admin) yang ada
    $user = $pdo->query("SELECT id FROM users LIMIT 1")->fetch();

    if (!$kampanye || !$user) {
        die("Eror: Kamu harus buat Kampanye Bencana dan User (akun) dulu di database biar data donasinya bisa nyambung!");
    }

    $kampanye_id = $kampanye['id'];
    $user_id = $user['id'];

    // 3. Masukkan data donasi tiruan dengan status 'pending'
    $sql = "INSERT INTO donasi_uang (nomor_referensi, user_id, kampanye_id, nominal, bank_asal, status_verifikasi, created_at) 
            VALUES (:nomor_referensi, :user_id, :kampanye_id, :nominal, :bank_asal, 'pending', NOW())";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'nomor_referensi' => 'REF-' . date('Ymd') . rand(100, 999),
        'user_id' => $user_id,
        'kampanye_id' => $kampanye_id,
        'nominal' => 750000,
        'bank_asal' => 'BCA'
    ]);

    echo "<h3>Mantap! Data donasi tiruan Rp 750.000 berhasil ditambahkan otomatis.</h3>";
    echo "<a href='donasi_uang.php'>Kembali ke Daftar Donasi Uang</a>";

} catch (PDOException $e) {
    echo "Eror pas input data: " . $e->getMessage();
}