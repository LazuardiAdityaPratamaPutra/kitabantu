<?php
// modules/logistik/simpan_permintaan.php
session_start();
require '../../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data identitas posko dan kampanye bencana dari form
    $posko_id = !empty($_POST['posko_id']) ? (int)$_POST['posko_id'] : null;
    $kampanye_id = !empty($_POST['kampanye_id']) ? (int)$_POST['kampanye_id'] : null;
    
    // Ambil data array multi-barang
    $barang_array = $_POST['barang'] ?? [];
    $kuantitas_array = $_POST['kuantitas'] ?? [];
    $satuan_array = $_POST['satuan'] ?? [];
    $urgensi_array = $_POST['urgensi'] ?? [];
    $dibuat_oleh = $_SESSION['user_id'] ?? null;

    // Validasi input wajib
    if (empty($barang_array) || empty($kampanye_id)) {
        echo "<script>alert('Gagal! Pastikan Kampanye Bencana dan Barang sudah diisi.'); window.location.href='permintaan.php';</script>";
        exit;
    }

    try {
        $pdo->beginTransaction();
        $id_pengiriman_terakhir = 0;

        // Loop array multi-barang untuk disimpan berurutan
        foreach ($barang_array as $index => $barang_nama) {
            if (empty($barang_nama)) continue;

            $kuantitas = (int)$kuantitas_array[$index];
            $satuan = $satuan_array[$index];
            $urgensi = $urgensi_array[$index];

            // 1. Simpan ke tabel permintaan_logistik (Status diset otomatis menjadi 'dikirim')
            $stmtPermintaan = $pdo->prepare("
                INSERT INTO permintaan_logistik (posko_id, kampanye_id, barang_diminta, kuantitas, satuan, tingkat_urgensi, status_tiket, dibuat_oleh, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'dikirim', ?, NOW())
            ");
            $stmtPermintaan->execute([$posko_id, $kampanye_id, $barang_nama, $kuantitas, $satuan, $urgensi, $dibuat_oleh]);
            $permintaan_id = $pdo->lastInsertId();

            // 2. OTOMATIS Buat baris data di pengiriman_armada (Sistem simulasikan armada siap jalan)
            $nama_supir_otomatis = "Supir Siaga Korban Bencana";
            $no_kendaraan_otomatis = "L " . rand(1111, 9999) . " AB"; // Simulasi plat nomor Jawa Timur acak
            $jenis_armada_otomatis = "Truck Logistik Medium";

            $stmtArmada = $pdo->prepare("
                INSERT INTO pengiriman_armada (permintaan_id, nama_supir, no_kendaraan, jenis_armada, tgl_kirim) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmtArmada->execute([$permintaan_id, $nama_supir_otomatis, $no_kendaraan_otomatis, $jenis_armada_otomatis]);
            $pengiriman_id = $pdo->lastInsertId();
            
            // Catat ID pengiriman terakhir untuk digunakan pada pengalihan tautan lokasi
            $id_pengiriman_terakhir = $pengiriman_id;

            // 3. OTOMATIS Buat riwayat timeline status awal di riwayat_status_pengiriman
            $stmtRiwayat = $pdo->prepare("
                INSERT INTO riwayat_status_pengiriman (pengiriman_id, status, catatan, dicatat_oleh, created_at) 
                VALUES (?, 'Dalam Perjalanan', 'Permintaan diverifikasi sistem. Paket logistik sedang dalam perjalanan menuju lokasi.', ?, NOW())
            ");
            $stmtRiwayat->execute([$pengiriman_id, $dibuat_oleh]);
        }

        $pdo->commit();

        // FLOW UTAMA: Berhasil disimpan, langsung oper user ke halaman Lacak Pengiriman!
        echo "<script>
            alert('Permintaan logistik berhasil diproses ke armada pengiriman!');
            window.location.href = '../pengiriman/lacak_pengiriman.php?id=" . $id_pengiriman_terakhir . "';
        </script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Terjadi kegagalan sistem database: " . $e->getMessage());
    }
} else {
    header('Location: permintaan.php');
    exit;
}