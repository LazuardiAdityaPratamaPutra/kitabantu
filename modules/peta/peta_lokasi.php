<?php
// modules/peta/peta_lokasi.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
cekLogin();

$pageTitle = 'Peta Lokasi Bencana & Posko';

$posko  = $pdo->query("SELECT nama_posko, latitude, longitude, total_jiwa FROM posko_lapangan WHERE latitude IS NOT NULL AND status_posko='aktif'")->fetchAll();
$gudang = $pdo->query("SELECT nama_gudang, latitude, longitude FROM gudang_pusat WHERE latitude IS NOT NULL")->fetchAll();
$bencana= $pdo->query("SELECT nama_bencana, lokasi, latitude, longitude FROM kampanye_bencana WHERE latitude IS NOT NULL AND status='aktif'")->fetchAll();

include '../../includes/header.php';
?>

<h5 class="fw-bold mb-3"><i class="bi bi-map me-2"></i>Peta Lokasi Bencana, Posko & Gudang</h5>

<!-- Legenda -->
<div class="d-flex gap-3 mb-3 flex-wrap">
  <span><span class="badge bg-danger">●</span> Titik Bencana</span>
  <span><span class="badge bg-primary">●</span> Posko Lapangan</span>
  <span><span class="badge bg-success">●</span> Gudang Pusat</span>
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div id="peta" style="height:520px;border-radius:.5rem;"></div>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('peta').setView([-2.5, 118], 5);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Marker bencana (merah)
<?= json_encode($bencana) ?>.forEach(b => {
  if (!b.latitude) return;
  L.circleMarker([b.latitude, b.longitude], { color:'#dc3545', radius:10, fillOpacity:.8 })
   .bindPopup(`<b>🔴 ${b.nama_bencana}</b><br>${b.lokasi}`).addTo(map);
});

// Marker posko (biru)
<?= json_encode($posko) ?>.forEach(p => {
  if (!p.latitude) return;
  L.marker([p.latitude, p.longitude])
   .bindPopup(`<b>🔵 ${p.nama_posko}</b><br>Jiwa terdampak: <b>${p.total_jiwa}</b>`).addTo(map);
});

// Marker gudang (hijau)
<?= json_encode($gudang) ?>.forEach(g => {
  if (!g.latitude) return;
  L.circleMarker([g.latitude, g.longitude], { color:'#198754', radius:8, fillOpacity:.9 })
   .bindPopup(`<b>🟢 Gudang: ${g.nama_gudang}</b>`).addTo(map);
});
</script>

<?php include '../../includes/footer.php'; ?>
