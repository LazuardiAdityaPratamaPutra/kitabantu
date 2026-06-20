<?php
// modules/dashboard/index.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
cekLogin();

$pageTitle = 'Dashboard — KitaBantu';

// Query statistik
$totalDonasi     = $pdo->query("SELECT COALESCE(SUM(nominal),0) FROM donasi_uang WHERE status_verifikasi='terverifikasi'")->fetchColumn();
$totalKampanye   = $pdo->query("SELECT COUNT(*) FROM kampanye_bencana ")->fetchColumn();
$totalPosko      = $pdo->query("SELECT COUNT(*) FROM posko_lapangan WHERE status_posko='aktif'")->fetchColumn();
$permintaanKrisis= $pdo->query("SELECT COUNT(*) FROM permintaan_logistik WHERE tingkat_urgensi='krisis' AND status_tiket='pending'")->fetchColumn();

// Data tren donasi 7 hari terakhir (untuk chart)
$tren = $pdo->query("
    SELECT DATE(created_at) as tgl, SUM(nominal) as total
    FROM donasi_uang
    WHERE status_verifikasi='terverifikasi' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at) ORDER BY tgl ASC
")->fetchAll();

include '../../includes/header.php';
?>

<!-- WIDGET CARDS — mapping ke .card AdminHMD -->
<div class="row g-3 mb-4">

  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
          <i class="bi bi-cash-stack fs-4 text-primary"></i>
        </div>
        <div>
          <div class="text-muted small">Total Donasi Terverifikasi</div>
          <div class="fw-bold fs-5">Rp <?= number_format($totalDonasi, 0, ',', '.') ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-circle bg-success bg-opacity-10 p-3">
          <i class="bi bi-megaphone fs-4 text-success"></i>
        </div>
        <div>
          <div class="text-muted small">Kampanye Aktif</div>
          <div class="fw-bold fs-5"><?= $totalKampanye ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-circle bg-info bg-opacity-10 p-3">
          <i class="bi bi-geo-alt fs-4 text-info"></i>
        </div>
        <div>
          <div class="text-muted small">Posko Aktif</div>
          <div class="fw-bold fs-5"><?= $totalPosko ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm <?= $permintaanKrisis > 0 ? 'border-danger border-2' : '' ?>">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
          <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
        </div>
        <div>
          <div class="text-muted small">Permintaan KRISIS</div>
          <div class="fw-bold fs-5 text-danger"><?= $permintaanKrisis ?></div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- CHART TREN DONASI — pakai Chart.js bawaan AdminHMD -->
<div class="card shadow-sm">
  <div class="card-header bg-white"><strong>Tren Donasi 7 Hari Terakhir</strong></div>
  <div class="card-body">
    <canvas id="chartDonasi" height="80"></canvas>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode(array_column($tren, 'tgl')) ?>;
const values = <?= json_encode(array_map('floatval', array_column($tren, 'total'))) ?>;

new Chart(document.getElementById('chartDonasi'), {
  type: 'line',
  data: {
    labels: labels,
    datasets: [{
      label: 'Donasi (Rp)',
      data: values,
      borderColor: '#0d6efd',
      backgroundColor: 'rgba(13,110,253,.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 5,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } } }
  }
});
</script>

<?php include '../../includes/footer.php'; ?>
