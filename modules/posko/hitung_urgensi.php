<?php
// modules/posko/hitung_urgensi.php
// Bisa dipanggil sebagai fungsi dari file lain, atau via endpoint AJAX

require_once '../../config/koneksi.php';
require_once '../../includes/notifikasi_helper.php';

/**
 * Hitung rasio logistik posko dan update tingkat_urgensi permintaan.
 *
 * Rumus:
 *   stok_per_jiwa = total stok terkirim / total_jiwa
 *   < 0.3 → krisis | 0.3–0.7 → tinggi | > 0.7 → normal
 *
 * @param int $posko_id
 * @return array ['urgensi' => string, 'stok_per_jiwa' => float]
 */
function hitungUrgensiPosko(PDO $pdo, int $posko_id): array {
    // Ambil total jiwa posko
    $stmtPosko = $pdo->prepare("SELECT total_jiwa FROM posko_lapangan WHERE id = :pid");
    $stmtPosko->execute(['pid' => $posko_id]);
    $posko = $stmtPosko->fetch();

    if (!$posko || $posko['total_jiwa'] == 0) {
        return ['urgensi' => 'normal', 'stok_per_jiwa' => 0.0];
    }

    // Hitung total kuantitas yang sudah berstatus 'dikirim' atau 'diterima'
    $stmtStok = $pdo->prepare("
        SELECT COALESCE(SUM(kuantitas), 0) AS total_stok
        FROM permintaan_logistik
        WHERE posko_id = :pid AND status_tiket IN ('dikirim', 'diterima')
    ");
    $stmtStok->execute(['pid' => $posko_id]);
    $totalStok = (float) $stmtStok->fetchColumn();

    $stokPerJiwa = $totalStok / $posko['total_jiwa'];

    // Tentukan level urgensi
    if ($stokPerJiwa < 0.3) {
        $urgensi = 'krisis';
    } elseif ($stokPerJiwa < 0.7) {
        $urgensi = 'tinggi';
    } else {
        $urgensi = 'normal';
    }

    // Update semua permintaan pending posko ini dengan urgensi baru
    $pdo->prepare("
        UPDATE permintaan_logistik
        SET tingkat_urgensi = :urgensi
        WHERE posko_id = :pid AND status_tiket = 'pending'
    ")->execute(['urgensi' => $urgensi, 'pid' => $posko_id]);

    // Kirim notifikasi jika level krisis
    if ($urgensi === 'krisis') {
        notifikasiSemuaAdminPusat(
            $pdo,
            '🚨 Status KRISIS — Posko Kekurangan Stok',
            "Posko ID #$posko_id memiliki rasio stok/jiwa sangat rendah ($stokPerJiwa). Segera kirim bantuan!",
            'danger',
            "/kitabantu/modules/posko/index.php"
        );
    }

    return ['urgensi' => $urgensi, 'stok_per_jiwa' => round($stokPerJiwa, 3)];
}

// Jika file ini dipanggil langsung via AJAX
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    session_start();
    header('Content-Type: application/json');
    $posko_id = (int) ($_GET['posko_id'] ?? 0);
    if (!$posko_id) { echo json_encode(['status' => 'gagal', 'pesan' => 'posko_id wajib diisi']); exit; }
    echo json_encode(hitungUrgensiPosko($pdo, $posko_id));
}
