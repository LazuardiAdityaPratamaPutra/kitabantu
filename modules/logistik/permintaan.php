<?php
// modules/logistik/permintaan.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Ambil koneksi karena dibutuhkan untuk mengambil data kampanye bencana di dalam form
require '../../config/koneksi.php';

// 1. Panggil komponen kerangka template utama
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Cek akses: Pastikan yang mengakses adalah role yang diizinkan (Admin Pusat, Admin Logistik, Petugas Lapangan)
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin_pusat', 'admin_logistik', 'petugas_lapangan'])) {
    echo "<script>alert('Akses ditolak!'); window.location.href='../../modules/dashboard/index.php';</script>";
    exit;
}
?>

<div class="page-heading">
  <div class="page-heading-copy">
    <span class="page-icon"><i class="bi bi-ui-checks-grid" aria-hidden="true"></i></span>
    <div>
      <p class="eyebrow mb-1">Logistik</p>
      <h1 class="h3 mb-1">Permintaan Logistik</h1>
      <p class="text-muted mb-0">Ajukan atau tambahkan baris barang kebutuhan posko lapangan secara berkala.</p>
    </div>
  </div>
</div>

<section class="row g-3">
  <div class="col-12 col-xl-12">
    
    <form class="panel needs-validation" id="formPermintaan" method="POST" action="simpan_permintaan.php" novalidate>
      <input type="hidden" name="posko_id" value="<?= $_SESSION['posko_id'] ?? '' ?>">

      <div class="panel-header">
        <div>
          <h2 class="h5 mb-1 section-title">
            <i class="bi bi-file-earmark-medical" aria-hidden="true"></i>
            <span>Form Permintaan Multi-Barang</span>
          </h2>
          <p class="text-muted mb-0">Pilih kampanye bencana terkait dan isi kebutuhan barang posko.</p>
        </div>
      </div>

      <div class="row p-3 bg-light rounded mx-1 mb-3 g-2 border">
        <div class="col-md-12">
          <label class="form-label fw-bold text-dark"><i class="bi bi-flag me-1"></i> Hubungkan dengan Kampanye Bencana :</label>
          <select name="kampanye_id" class="form-select" required>
            <option value="">-- Pilih Kampanye Bencana yang Membutuhkan Logistik --</option>

            <?php
try {
    // Menggunakan kolom nama_bencana
    $stmtKampanye = $pdo->query("SELECT id, nama_bencana FROM kampanye_bencana ORDER BY id DESC");
    while ($k = $stmtKampanye->fetch()) {
        echo "<option value='{$k['id']}'>" . htmlspecialchars($k['nama_bencana']) . "</option>";
    }
} catch (Exception $e) {
    echo "<option value=''>Gagal memuat: " . htmlspecialchars($e->getMessage()) . "</option>";
}
            ?>
          </select>
          <div class="invalid-feedback">Anda harus memilih kampanye bencana terkait.</div>
        </div>
      </div>

      <div id="daftarBarang" class="mt-3">
        
        <div class="row g-2 mb-2 item-baris">
          <div class="col-md-5">
            <label class="form-label d-md-none">Nama Barang</label>
            <input type="text" name="barang[]" class="form-control" placeholder="Contoh: Tenda Darurat, Beras, Selimut" required>
            <div class="invalid-feedback">Nama barang harus diisi.</div>
          </div>
          
          <div class="col-md-2">
            <label class="form-label d-md-none">Kuantitas</label>
            <input type="number" name="kuantitas[]" class="form-control" placeholder="Qty" min="1" required>
            <div class="invalid-feedback">Isi kuantitas (min. 1).</div>
          </div>
          
          <div class="col-md-2">
            <label class="form-label d-md-none">Satuan</label>
            <input type="text" name="satuan[]" class="form-control" placeholder="Pcs, Kg, Box" required>
            <div class="invalid-feedback">Isi satuan barang.</div>
          </div>
          
          <div class="col-md-2">
            <label class="form-label d-md-none">Tingkat Urgensi</label>
            <select name="urgensi[]" class="form-select">
              <option value="normal">Normal</option>
              <option value="tinggi">Tinggi</option>
              <option value="krisis">Krisis</option>
            </select>
          </div>
          
          <div class="col-md-1">
            <label class="form-label d-md-none">Aksi</label>
            <button type="button" class="btn btn-outline-danger w-100 btn-hapus" disabled>
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>

      </div>

      <div class="mt-3">
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnTambahBaris">
          <i class="bi bi-plus-circle"></i> Tambah Barang
        </button>
      </div>

      <div class="d-flex justify-content-end border-top mt-4 pt-3">
        <button class="btn btn-primary" type="submit">
          <i class="bi bi-send" aria-hidden="true"></i> Kirim Permintaan
        </button>
      </div>

    </form>

  </div>
</section>

<script>
// 1. Template HTML baris baru ketika tombol "+" diklik
function templateBaris() {
  return `
    <div class="row g-2 mb-2 item-baris">
      <div class="col-md-5">
        <input type="text" name="barang[]" class="form-control" placeholder="Nama barang..." required>
        <div class="invalid-feedback">Nama barang harus diisi.</div>
      </div>
      <div class="col-md-2">
        <input type="number" name="kuantitas[]" class="form-control" placeholder="Qty" min="1" required>
        <div class="invalid-feedback">Isi kuantitas.</div>
      </div>
      <div class="col-md-2">
        <input type="text" name="satuan[]" class="form-control" placeholder="Satuan" required>
        <div class="invalid-feedback">Isi satuan.</div>
      </div>
      <div class="col-md-2">
        <select name="urgensi[]" class="form-select">
          <option value="normal">Normal</option>
          <option value="tinggi">Tinggi</option>
          <option value="krisis">Krisis</option>
        </select>
      </div>
      <div class="col-md-1">
        <button type="button" class="btn btn-outline-danger w-100 btn-hapus">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    </div>`;
}

// 2. Event Listener: Aksi Tambah Baris Baru
document.getElementById('btnTambahBaris').addEventListener('click', () => {
  document.getElementById('daftarBarang').insertAdjacentHTML('beforeend', templateBaris());
});

// 3. Event Listener: Aksi Hapus Baris Terpilih (Menggunakan delegasi event)
document.getElementById('daftarBarang').addEventListener('click', e => {
  const tombolHapus = e.target.closest('.btn-hapus');
  if (tombolHapus && !tombolHapus.disabled) {
    e.target.closest('.item-baris').remove();
  }
});

// 4. Integrasi Logika HTML5 Form Validation Bawaan Template adminHMD
(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>

<?php
// 2. Panggil komponen penutup kaki template halaman
include '../../includes/footer.php';
?>