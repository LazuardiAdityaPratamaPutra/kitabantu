<?php
// modules/pengiriman/lacak_pengiriman.php
session_start();
require '../../config/koneksi.php';
require '../../includes/cek_akses.php';
cekLogin();

// Ambil ID dari URL parameter pencarian ?id=...
$pengiriman_id = (int) ($_GET['id'] ?? 0);
$pageTitle = "Lacak Pengiriman #$pengiriman_id";

// Menggunakan LEFT JOIN agar data tetap muncul seandainya posko_id atau nama_supir bernilai NULL / Kosong
$stmt = $pdo->prepare("
    SELECT pa.*, pl.barang_diminta, pl.kuantitas, pl.satuan,
           IFNULL(po.nama_posko, 'Posko Pusat / Belum Ditentukan') as nama_posko, 
           u.nama as nama_supir_user
    FROM pengiriman_armada pa
    JOIN permintaan_logistik pl ON pa.permintaan_id = pl.id
    LEFT JOIN posko_lapangan po ON pl.posko_id = po.id
    LEFT JOIN users u ON u.nama = pa.nama_supir
    WHERE pa.id = :id
");
$stmt->execute(['id' => $pengiriman_id]);
$pengiriman = $stmt->fetch();

$timeline = $pdo->prepare("
    SELECT r.*, u.nama as dicatat_oleh_nama
    FROM riwayat_status_pengiriman r
    LEFT JOIN users u ON r.dicatat_oleh = u.id
    WHERE r.pengiriman_id = :pid
    ORDER BY r.created_at ASC
");
$timeline->execute(['pid' => $pengiriman_id]);
$riwayat = $timeline->fetchAll();

include '../../includes/header.php';
?>

<h5 class="fw-bold mb-4"><i class="bi bi-truck me-2"></i>Lacak Pengiriman #<?= $pengiriman_id ?></h5>

<?php if ($pengiriman): ?>
<div class="row">
  <div class="col-md-5 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">Detail Pengiriman</div>
      <div class="card-body">
        <table class="table table-borderless mb-0">
          <tr><td class="text-muted">Barang</td><td><b><?= htmlspecialchars($pengiriman['barang_diminta']) ?></b> (<?= $pengiriman['kuantitas'] ?> <?= $pengiriman['satuan'] ?>)</td></tr>
          <tr><td class="text-muted">Tujuan</td><td><?= htmlspecialchars($pengiriman['nama_posko']) ?></td></tr>
          <tr><td class="text-muted">Supir</td><td><?= htmlspecialchars($pengiriman['nama_supir']) ?></td></tr>
          <tr><td class="text-muted">Kendaraan</td><td><?= htmlspecialchars($pengiriman['no_kendaraan']) ?> (<?= htmlspecialchars($pengiriman['jenis_armada']) ?>)</td></tr>
          <tr><td class="text-muted">Tgl Kirim</td><td><?= $pengiriman['tgl_kirim'] ? date('d/m/Y H:i', strtotime($pengiriman['tgl_kirim'])) : '-' ?></td></tr>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">Timeline Status</div>
      <div class="card-body">
        <?php foreach ($riwayat as $i => $r): ?>
        <div class="d-flex gap-3 <?= $i < count($riwayat)-1 ? 'mb-3' : '' ?>">
          <!-- Lingkaran & garis vertikal -->
          <div class="d-flex flex-column align-items-center">
            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width:32px;height:32px;min-width:32px">
              <?= $i + 1 ?>
            </div>
            <?php if ($i < count($riwayat)-1): ?>
              <div class="bg-primary bg-opacity-25" style="width:2px;flex-grow:1;margin:4px 0"></div>
            <?php endif; ?>
          </div>
          <!-- Konten status -->
          <div class="pb-3 <?= $i < count($riwayat)-1 ? 'border-bottom' : '' ?> flex-grow-1">
            <div class="fw-semibold"><?= htmlspecialchars($r['status']) ?></div>
            <?php if ($r['catatan']): ?>
              <div class="text-muted small"><?= htmlspecialchars($r['catatan']) ?></div>
            <?php endif; ?>
            <div class="text-muted" style="font-size:.75rem">
              <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
              <?= $r['dicatat_oleh_nama'] ? "· " . htmlspecialchars($r['dicatat_oleh_nama']) : '' ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($riwayat)): ?>
          <p class="text-muted">Belum ada riwayat status.</p>
        <?php endif; ?>

        <!-- Form update status (untuk petugas/admin) -->
        <?php if (in_array($_SESSION['role'], ['admin_pusat','admin_logistik','petugas_lapangan'])): ?>
        <hr>
        <form id="formUpdateStatus">
          <input type="hidden" name="pengiriman_id" value="<?= $pengiriman_id ?>">
          <div class="row g-2">
            <div class="col-md-6">
              <select name="status" class="form-select form-select-sm" required>
                <option value="">-- Pilih status baru --</option>
                <option>Dalam Perjalanan</option>
                <option>Tiba di Posko</option>
                <option>Serah Terima Selesai</option>
              </select>
            </div>
            <div class="col-md-6">
              <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Catatan (opsional)">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary btn-sm">Perbarui Status</button>
            </div>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php else: ?>
  <div class="alert alert-warning">Data pengiriman tidak ditemukan.</div>
<?php endif; ?>

<script>
document.getElementById('formUpdateStatus')?.addEventListener('submit', async e => {
  e.preventDefault();
  const fd  = new FormData(e.target);
  const res = await fetch('../../ajax/update_status_pengiriman.php', { method:'POST', body:fd });
  const data= await res.json();
  alert(data.pesan);
  if (data.status === 'ok') location.reload();
});
</script>

<?php include '../../includes/footer.php'; ?>
