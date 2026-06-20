<?php
// includes/footer.php
$ROOT = '/eas'; // Menggunakan /eas sesuai folder kamu agar aset terbaca
?>
        </div> </main> <footer class="admin-footer">
        <div class="container-fluid px-3 px-lg-4">
          <span>Copyright &copy; 2026 <span class="fw-bold text-success">KitaBantu</span>. All Rights Reserved.</span>
          <span>Sistem Informasi Manajemen Bencana</span>
        </div>
      </footer>

    </div> </div> <script src="<?= $ROOT ?>/assets/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $ROOT ?>/assets/js/main.js"></script>

  <script>
  async function muatNotifikasi() {
    try {
      const res  = await fetch('<?= $ROOT ?>/ajax/ambil_notifikasi.php');
      const data = await res.json();

      const badge = document.getElementById('notifBadge');
      const container = document.getElementById('notifItemsContainer');
      if (!badge || !container) return;

      // Update Angka/Badge Notifikasi di Lonceng Navbar
      if (data.belum_dibaca > 0) {
        badge.style.display = 'inline';
        badge.textContent   = data.belum_dibaca;
      } else {
        badge.style.display = 'none';
      }

      // Render daftar notifikasi ke dropdown menu adminHMD
      if (data.notifikasi.length === 0) {
        container.innerHTML = '<span class="dropdown-item text-muted small py-2">Tidak ada notifikasi baru</span>';
      } else {
        container.innerHTML = data.notifikasi.map(n => `
          <a class="dropdown-item py-2 border-bottom" href="${n.link_tujuan ?? '#'}">
            <span class="notification-title fw-semibold text-wrap d-block" style="font-size: 0.85rem;">${n.judul}</span>
            <span class="notification-time text-muted d-block text-wrap" style="font-size: 0.75rem; white-space: normal;">${n.pesan}</span>
          </a>
        `).join('');
      }
    } catch(e) { 
      /* Abaikan jika ada error jaringan saat background-polling */ 
    }
  }

  // Jalankan fungsi saat halaman pertama kali dimuat
  muatNotifikasi();
  // Jalankan otomatis di latar belakang setiap 30 detik (30000 ms)
  setInterval(muatNotifikasi, 30000);
  </script>

</body>
</html>