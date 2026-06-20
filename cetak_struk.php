<?php
// cetak_struk.php — Generate PDF struk donasi untuk donatur
// Memerlukan: composer require dompdf/dompdf
session_start();
require 'config/koneksi.php';
require 'includes/cek_akses.php';
cekLogin();

$id  = (int) ($_GET['id'] ?? 0);
$uid = (int) $_SESSION['user_id'];

// Ambil data donasi (user hanya bisa cetak milik sendiri)
$stmt = $pdo->prepare("
    SELECT d.*, u.nama as nama_donatur, u.email,
           k.nama_bencana, k.lokasi
    FROM donasi_uang d
    JOIN users u ON d.user_id = u.id
    JOIN kampanye_bencana k ON d.kampanye_id = k.id
    WHERE d.id = :id AND d.user_id = :uid AND d.status_verifikasi = 'terverifikasi'
");
$stmt->execute(['id' => $id, 'uid' => $uid]);
$d = $stmt->fetch();

if (!$d) {
    die('<div class="alert alert-danger">Struk tidak ditemukan atau donasi belum terverifikasi.</div>');
}

// Coba load dompdf (opsional)
$useDompdf = file_exists('vendor/autoload.php');
if ($useDompdf) require 'vendor/autoload.php';

$tglFormatted = date('d F Y', strtotime($d['tgl_disetujui']));
$nominalFormatted = 'Rp ' . number_format($d['nominal'], 0, ',', '.');

$html = "
<!DOCTYPE html>
<html><head><meta charset='UTF-8'>
<style>
  body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
  .header { text-align: center; border-bottom: 3px solid #0d6efd; padding-bottom: 15px; }
  .header h1 { color: #0d6efd; margin: 0; }
  .box { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-top: 20px; }
  .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
  .label { color: #666; }
  .value { font-weight: bold; }
  .nominal { font-size: 24px; color: #198754; text-align: center; margin: 20px 0; }
  .footer { text-align: center; margin-top: 30px; color: #999; font-size: 12px; }
  .badge { background: #198754; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; }
</style>
</head>
<body>
<div class='header'>
  <h1>🆘 KitaBantu</h1>
  <p>Sistem Manajemen Donasi dan Penyaluran Bencana</p>
  <span class='badge'>✓ TERVERIFIKASI</span>
</div>
<div class='box'>
  <div class='row'><span class='label'>No. Referensi</span><span class='value'>{$d['nomor_referensi']}</span></div>
  <div class='row'><span class='label'>Nama Donatur</span><span class='value'>{$d['nama_donatur']}</span></div>
  <div class='row'><span class='label'>Kampanye</span><span class='value'>{$d['nama_bencana']}</span></div>
  <div class='row'><span class='label'>Lokasi Bencana</span><span class='value'>{$d['lokasi']}</span></div>
  <div class='row'><span class='label'>Bank Asal</span><span class='value'>{$d['bank_asal']}</span></div>
  <div class='row'><span class='label'>Tanggal Disetujui</span><span class='value'>$tglFormatted</span></div>
</div>
<div class='nominal'>$nominalFormatted</div>
<div class='footer'>
  Dokumen ini digenerate otomatis oleh sistem KitaBantu.<br>
  Terima kasih atas kepedulian dan donasi Anda. 🙏
</div>
</body></html>
";

if ($useDompdf) {
    use Dompdf\Dompdf;
    $dompdf = new Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A5', 'portrait');
    $dompdf->render();
    $dompdf->stream("struk-{$d['nomor_referensi']}.pdf", ['Attachment' => true]);
} else {
    // Fallback: tampilkan HTML, donatur tekan Ctrl+P → Save as PDF
    echo $html;
    echo "<script>setTimeout(() => window.print(), 500);</script>";
}