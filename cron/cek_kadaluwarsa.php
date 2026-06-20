<?php
// cron/cek_kedaluwarsa.php
// Jalankan via cPanel Cron Job: php /path/ke/cron/cek_kedaluwarsa.php
// Atau include di header.php sementara (untuk demo EAS)

require dirname(__DIR__) . '/config/koneksi.php';
require dirname(__DIR__) . '/includes/notifikasi_helper.php';

$stmt = $pdo->prepare("
    SELECT si.id, si.nama_barang, si.kuantitas, si.satuan, si.tgl_kedaluwarsa,
           g.nama_gudang
    FROM stok_inventaris si
    JOIN gudang_pusat g ON si.gudang_id = g.id
    WHERE si.tgl_kedaluwarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
      AND si.kuantitas > 0
    ORDER BY si.tgl_kedaluwarsa ASC
");
$stmt->execute();
$items = $stmt->fetchAll();

$notifDikirim = 0;
foreach ($items as $item) {
    $sisaHari = (int) ceil((strtotime($item['tgl_kedaluwarsa']) - time()) / 86400);
    $level    = $sisaHari <= 7 ? 'danger' : 'warning';

    notifikasiSemuaAdminPusat(
        $pdo,
        ($sisaHari <= 7 ? '⚠️ SEGERA' : '📦') . " Stok Mendekati Kedaluwarsa",
        "{$item['nama_barang']} di {$item['nama_gudang']}: {$item['kuantitas']} {$item['satuan']} — kedaluwarsa dalam $sisaHari hari ({$item['tgl_kedaluwarsa']}).",
        $level,
        '/kitabantu/modules/gudang/stok_inventaris.php'
    );
    $notifDikirim++;
}

echo "[" . date('Y-m-d H:i:s') . "] Cek kedaluwarsa selesai. $notifDikirim item terdeteksi.\n";
