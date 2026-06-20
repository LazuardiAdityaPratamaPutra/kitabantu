<?php
// modules/logistik/generate_kebutuhan_otomatis.php
require_once '../../config/koneksi.php';

/**
 * Auto-generate permintaan logistik berdasarkan template jenis bencana.
 * Dipanggil setelah INSERT posko baru berhasil.
 */
function generateKebutuhanOtomatis(PDO $pdo, int $posko_id, int $dibuat_oleh): int {
    // Ambil total_jiwa + jenis_bencana dari posko & kampanye terkait
    $stmt = $pdo->prepare("
        SELECT p.total_jiwa, k.jenis_bencana_id
        FROM posko_lapangan p
        JOIN kampanye_bencana k ON p.kampanye_id = k.id
        WHERE p.id = :pid
    ");
    $stmt->execute(['pid' => $posko_id]);
    $posko = $stmt->fetch();

    if (!$posko || !$posko['jenis_bencana_id'] || $posko['total_jiwa'] <= 0) return 0;

    // Ambil template kebutuhan berdasarkan jenis bencana
    $template = $pdo->prepare("
        SELECT nama_barang, satuan, rasio_per_jiwa FROM template_kebutuhan_bencana
        WHERE jenis_bencana_id = :jid
    ");
    $template->execute(['jid' => $posko['jenis_bencana_id']]);

    $insert = $pdo->prepare("
        INSERT INTO permintaan_logistik (posko_id, barang_diminta, kuantitas, satuan, tingkat_urgensi, status_tiket, dibuat_oleh)
        VALUES (:pid, :barang, :qty, :satuan, 'normal', 'pending', :uid)
    ");

    $jumlahDibuat = 0;
    foreach ($template->fetchAll() as $t) {
        $kuantitas = (int) ceil($posko['total_jiwa'] * $t['rasio_per_jiwa']);
        if ($kuantitas <= 0) continue;
        $insert->execute([
            'pid'    => $posko_id,
            'barang' => $t['nama_barang'],
            'qty'    => $kuantitas,
            'satuan' => $t['satuan'],
            'uid'    => $dibuat_oleh,
        ]);
        $jumlahDibuat++;
    }

    return $jumlahDibuat; // Kembalikan jumlah item yang digenerate
}