-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 20, 2026 at 02:21 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kitabantu`
--

-- --------------------------------------------------------

--
-- Table structure for table `donasi_barang`
--

CREATE TABLE `donasi_barang` (
  `id` int NOT NULL,
  `nomor_referensi` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `kampanye_id` int NOT NULL,
  `nama_barang` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kuantitas` int NOT NULL DEFAULT '1',
  `satuan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unit',
  `kondisi_barang` enum('baru','layak_pakai','rusak') COLLATE utf8mb4_unicode_ci DEFAULT 'baru',
  `nomor_resi` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ekspedisi` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_logistik` enum('pending','diterima_di_gudang','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `alasan_penolakan` text COLLATE utf8mb4_unicode_ci,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donasi_barang`
--

INSERT INTO `donasi_barang` (`id`, `nomor_referensi`, `user_id`, `kampanye_id`, `nama_barang`, `kuantitas`, `satuan`, `kondisi_barang`, `nomor_resi`, `ekspedisi`, `status_logistik`, `alasan_penolakan`, `catatan`, `created_at`) VALUES
(1, 'REF-BRG-20260620587', 1, 40, 'Masker Medis 3-Ply', 25, 'Box', 'baru', NULL, NULL, 'diterima_di_gudang', NULL, NULL, '2026-06-20 11:28:57'),
(2, 'REF-BRG-20260620640', 1, 41, 'Masker Medis 3-Ply', 25, 'Box', 'baru', NULL, NULL, 'diterima_di_gudang', NULL, NULL, '2026-06-20 18:15:54');

-- --------------------------------------------------------

--
-- Table structure for table `donasi_uang`
--

CREATE TABLE `donasi_uang` (
  `id` int NOT NULL,
  `nomor_referensi` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `kampanye_id` int NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `bank_asal` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor_rekening_asal` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `atas_nama` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_transfer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan_donatur` text COLLATE utf8mb4_unicode_ci,
  `status_verifikasi` enum('pending','menunggu_approval','terverifikasi','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `alasan_penolakan` text COLLATE utf8mb4_unicode_ci,
  `diajukan_oleh` int DEFAULT NULL,
  `disetujui_oleh` int DEFAULT NULL,
  `tgl_diajukan` datetime DEFAULT NULL,
  `tgl_disetujui` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donasi_uang`
--

INSERT INTO `donasi_uang` (`id`, `nomor_referensi`, `user_id`, `kampanye_id`, `nominal`, `bank_asal`, `nomor_rekening_asal`, `atas_nama`, `bukti_transfer`, `catatan_donatur`, `status_verifikasi`, `alasan_penolakan`, `diajukan_oleh`, `disetujui_oleh`, `tgl_diajukan`, `tgl_disetujui`, `created_at`) VALUES
(13, 'REF-20260620352', 1, 16, '12250000.00', 'BCA', NULL, NULL, NULL, NULL, 'terverifikasi', NULL, NULL, NULL, NULL, NULL, '2026-06-20 10:17:29'),
(24, 'REF-20260620672', 1, 28, '1000000.00', 'Mandiri', NULL, NULL, NULL, NULL, 'terverifikasi', NULL, NULL, NULL, NULL, NULL, '2026-06-20 11:14:11'),
(33, 'REF-20260620332', 1, 37, '1000000.00', 'BNI', NULL, NULL, NULL, NULL, 'terverifikasi', NULL, NULL, NULL, NULL, NULL, '2026-06-20 11:23:55'),
(36, 'REF-20260620614', 1, 40, '100000.00', 'Mandiri', NULL, NULL, NULL, NULL, 'terverifikasi', NULL, NULL, NULL, NULL, NULL, '2026-06-20 11:28:57'),
(37, 'REF-20260620731', 1, 41, '1000000.00', 'BRI', NULL, NULL, NULL, NULL, 'terverifikasi', NULL, NULL, NULL, NULL, NULL, '2026-06-20 18:15:54');

-- --------------------------------------------------------

--
-- Table structure for table `gudang_pusat`
--

CREATE TABLE `gudang_pusat` (
  `id` int NOT NULL,
  `nama_gudang` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `kapasitas` int NOT NULL DEFAULT '0' COMMENT 'Kapasitas dalam unit/dus',
  `penanggung_jawab` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_telp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gudang_pusat`
--

INSERT INTO `gudang_pusat` (`id`, `nama_gudang`, `alamat`, `kapasitas`, `penanggung_jawab`, `no_telp`, `latitude`, `longitude`, `created_at`) VALUES
(1, 'Gudang Coba', 'vcsdef', 6, 'swde', 'egfg', '1.0000000', '1.0000000', '2026-06-20 10:35:45');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_piket`
--

CREATE TABLE `jadwal_piket` (
  `id` int NOT NULL,
  `relawan_id` int NOT NULL,
  `posko_id` int NOT NULL,
  `tgl_piket` date NOT NULL,
  `shift` enum('pagi','siang','malam') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tugas` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_hadir` enum('terjadwal','hadir','tidak_hadir') COLLATE utf8mb4_unicode_ci DEFAULT 'terjadwal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jadwal_piket`
--

INSERT INTO `jadwal_piket` (`id`, `relawan_id`, `posko_id`, `tgl_piket`, `shift`, `tugas`, `status_hadir`) VALUES
(1, 1, 1, '2026-06-20', 'pagi', 'Teknologi', 'terjadwal');

-- --------------------------------------------------------

--
-- Table structure for table `jenis_bencana`
--

CREATE TABLE `jenis_bencana` (
  `id` int NOT NULL,
  `nama_jenis` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'bi-exclamation-triangle'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jenis_bencana`
--

INSERT INTO `jenis_bencana` (`id`, `nama_jenis`, `icon`) VALUES
(1, 'Gempa Bumi', 'bi-globe'),
(2, 'Banjir', 'bi-water'),
(3, 'Longsor', 'bi-layers'),
(4, 'Kebakaran', 'bi-fire'),
(5, 'Tsunami', 'bi-tsunami'),
(6, 'Angin Puting Beliung', 'bi-tornado');

-- --------------------------------------------------------

--
-- Table structure for table `kampanye_bencana`
--

CREATE TABLE `kampanye_bencana` (
  `id` int NOT NULL,
  `jenis_bencana_id` int DEFAULT NULL,
  `nama_bencana` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lokasi` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `target_dana` decimal(15,2) NOT NULL DEFAULT '0.00',
  `dana_terkumpul` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('aktif','selesai','draf') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draf',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `foto_bencana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kampanye_bencana`
--

INSERT INTO `kampanye_bencana` (`id`, `jenis_bencana_id`, `nama_bencana`, `lokasi`, `deskripsi`, `target_dana`, `dana_terkumpul`, `status`, `latitude`, `longitude`, `foto_bencana`, `created_by`, `created_at`, `updated_at`) VALUES
(16, 2, 'g7ug', 'ss', 'ss', '122341432.00', '0.00', 'aktif', '3.0000000', '3.0000000', NULL, 1, '2026-06-20 10:17:28', '2026-06-20 10:17:28'),
(28, 2, 'sodcifj0ewf', 'sd', 'dssa', '10000000.00', '0.00', 'selesai', '1.0000000', '1.0000000', NULL, 1, '2026-06-20 11:14:11', '2026-06-20 11:14:11'),
(37, 6, 'dingin', 'ye', 'fhs', '10000000.00', '0.00', 'selesai', '1.0000000', '1.0000000', NULL, 1, '2026-06-20 11:23:55', '2026-06-20 11:23:55'),
(40, 6, 'ertgf', 'gf', 'f', '100000.00', '0.00', 'selesai', '0.0000000', '0.0000000', NULL, 1, '2026-06-20 11:28:57', '2026-06-20 11:28:57'),
(41, 4, 'Yuuu', 'ewr', 'dskdsg', '10000000.00', '0.00', 'selesai', '1.0000000', '1.0000000', NULL, 1, '2026-06-20 18:15:54', '2026-06-20 18:15:54');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `aksi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modul` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `data_sebelum` json DEFAULT NULL,
  `data_sesudah` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `user_id`, `aksi`, `modul`, `deskripsi`, `data_sebelum`, `data_sesudah`, `ip_address`, `created_at`) VALUES
(1, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: TSUNAMI ACEH 2004 di Aceh', NULL, NULL, '::1', '2026-06-19 06:02:08'),
(2, 1, 'TOLAK_DONASI', 'donasi', 'Donasi #1 ditolak. Nominal: Rp 750.000', NULL, NULL, '::1', '2026-06-19 16:11:01'),
(3, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #1 \'TSUNAMI ACEH 2004\' dihapus.', NULL, NULL, '::1', '2026-06-19 16:12:09'),
(4, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: TSUNAMI ACEH 2004 di Aceh', NULL, NULL, '::1', '2026-06-19 16:12:47'),
(5, 1, 'EDIT_KAMPANYE', 'kampanye', 'Kampanye #2 diperbarui: banjir', NULL, NULL, '::1', '2026-06-19 16:17:08'),
(6, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #2 \'banjir\' dihapus.', NULL, NULL, '::1', '2026-06-19 16:31:27'),
(7, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: banjir di jakarta', NULL, NULL, '::1', '2026-06-19 16:31:58'),
(8, 1, 'TOLAK_DONASI', 'donasi', 'Donasi #3 ditolak. Nominal: Rp 750.000', NULL, NULL, '::1', '2026-06-19 16:34:27'),
(9, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #3 \'banjir\' dihapus.', NULL, NULL, '::1', '2026-06-19 16:36:58'),
(10, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: banjir di jakarta', NULL, NULL, '::1', '2026-06-19 16:37:28'),
(11, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #4 \'banjir\' dihapus.', NULL, NULL, '::1', '2026-06-19 16:37:53'),
(12, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: banjir di jakarta', NULL, NULL, '::1', '2026-06-19 16:38:19'),
(13, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: sodcifj0ewf di sdfdswfw', NULL, NULL, '::1', '2026-06-19 16:38:57'),
(14, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: aaa di aaa', NULL, NULL, '::1', '2026-06-19 16:49:44'),
(15, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: g7ug di re', NULL, NULL, '::1', '2026-06-19 16:51:24'),
(16, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #8 \'g7ug\' dihapus.', NULL, NULL, '::1', '2026-06-19 17:08:39'),
(17, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #7 \'aaa\' dihapus.', NULL, NULL, '::1', '2026-06-19 17:08:42'),
(18, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #6 \'sodcifj0ewf\' dihapus.', NULL, NULL, '::1', '2026-06-19 17:08:45'),
(19, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #5 \'banjir\' dihapus.', NULL, NULL, '::1', '2026-06-19 17:08:47'),
(20, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: g7ug di sdfdswfw', NULL, NULL, '::1', '2026-06-19 17:10:01'),
(21, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: TSUNAMI ACEH 2004 di Aceh', NULL, NULL, '::1', '2026-06-19 17:21:43'),
(22, 1, 'EDIT_KAMPANYE', 'kampanye', 'Kampanye #10 diperbarui: TSUNAMI ACEH 2004', NULL, NULL, '::1', '2026-06-19 17:21:59'),
(23, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #10 \'TSUNAMI ACEH 2004\' dihapus.', NULL, NULL, '::1', '2026-06-20 10:07:01'),
(24, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: banjir di jakarta', NULL, NULL, '::1', '2026-06-20 10:07:27'),
(25, 1, 'EDIT_KAMPANYE', 'kampanye', 'Kampanye #11 diperbarui: banjir', NULL, NULL, '::1', '2026-06-20 10:07:52'),
(26, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: banjir di sdfdswfw', NULL, NULL, '::1', '2026-06-20 10:09:56'),
(27, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: angin di sefd', NULL, NULL, '::1', '2026-06-20 10:14:11'),
(28, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: ikan di aaa', NULL, NULL, '::1', '2026-06-20 10:16:29'),
(29, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #15 \'ikan\' dihapus.', NULL, NULL, '::1', '2026-06-20 10:16:49'),
(30, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #14 \'angin\' dihapus.', NULL, NULL, '::1', '2026-06-20 10:16:51'),
(31, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #13 \'angin\' dihapus.', NULL, NULL, '::1', '2026-06-20 10:16:53'),
(32, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #12 \'banjir\' dihapus.', NULL, NULL, '::1', '2026-06-20 10:16:55'),
(33, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #11 \'banjir\' dihapus.', NULL, NULL, '::1', '2026-06-20 10:16:59'),
(34, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #9 \'g7ug\' dihapus.', NULL, NULL, '::1', '2026-06-20 10:17:01'),
(35, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: g7ug di ss', NULL, NULL, '::1', '2026-06-20 10:17:29'),
(36, 1, 'TAMBAH_GUDANG', 'gudang', 'Gudang baru: Gudang Coba', NULL, NULL, '::1', '2026-06-20 10:35:45'),
(37, 1, 'TAMBAH_STOK', 'gudang', 'Stok baru: Beras (100 kg) di gudang #1', NULL, NULL, '::1', '2026-06-20 10:37:43'),
(38, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: TSUNAMI ACEH 2004 di 3e', NULL, NULL, '::1', '2026-06-20 11:08:50'),
(39, 1, 'EDIT_KAMPANYE', 'kampanye', 'Kampanye #22 diperbarui: TSUNAMI ACEH 2004', NULL, NULL, '::1', '2026-06-20 11:09:27'),
(40, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: sodcifj0ewf di sd', NULL, NULL, '::1', '2026-06-20 11:14:11'),
(41, 1, 'HAPUS_KAMPANYE', 'kampanye', 'Kampanye #22 \'TSUNAMI ACEH 2004\' dihapus.', NULL, NULL, '::1', '2026-06-20 11:18:53'),
(42, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: dingin di ye', NULL, NULL, '::1', '2026-06-20 11:23:55'),
(43, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: ertgf di gf', NULL, NULL, '::1', '2026-06-20 11:28:57'),
(44, 1, 'PROSES_DONASI_BARANG', 'donasi', 'Donasi barang #1 (Masker Medis 3-Ply) di-terima.', NULL, NULL, '::1', '2026-06-20 11:30:11'),
(45, 1, 'TAMBAH_POSKO', 'posko', 'Posko baru: posko ciani (1 jiwa). 3 permintaan auto-generated.', NULL, NULL, '::1', '2026-06-20 12:10:03'),
(46, 1, 'TAMBAH_RELAWAN', 'relawan', 'Relawan baru: Lazuardi Aditya', NULL, NULL, '::1', '2026-06-20 12:11:48'),
(47, 1, 'TAMBAH_JADWAL_PIKET', 'relawan', 'Jadwal piket: relawan #1 di posko #1 tgl 2026-06-20 (pagi).', NULL, NULL, '::1', '2026-06-20 12:12:21'),
(48, 1, 'UPDATE_STATUS_PENGIRIMAN', 'pengiriman', 'Status pengiriman #2 diperbarui menjadi \'Tiba di Posko\'', NULL, NULL, '::1', '2026-06-20 18:04:26'),
(49, 1, 'TAMBAH_KAMPANYE', 'kampanye', 'Kampanye baru: Yuuu di ewr', NULL, NULL, '::1', '2026-06-20 18:15:54'),
(50, 1, 'PROSES_DONASI_BARANG', 'donasi', 'Donasi barang #2 (Masker Medis 3-Ply) di-terima.', NULL, NULL, '::1', '2026-06-20 18:16:19'),
(51, 1, 'UPDATE_STATUS_PENGIRIMAN', 'pengiriman', 'Status pengiriman #3 diperbarui menjadi \'Dalam Perjalanan\'', NULL, NULL, '::1', '2026-06-20 18:35:00'),
(52, 1, 'UPDATE_STATUS_PENGIRIMAN', 'pengiriman', 'Status pengiriman #3 diperbarui menjadi \'Tiba di Posko\'', NULL, NULL, '::1', '2026-06-20 18:35:08'),
(53, 1, 'UPDATE_STATUS_PENGIRIMAN', 'pengiriman', 'Status pengiriman #3 diperbarui menjadi \'Serah Terima Selesai\'', NULL, NULL, '::1', '2026-06-20 18:35:13');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `judul` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pesan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('info','warning','danger','success') COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `link_tujuan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id`, `user_id`, `judul`, `pesan`, `jenis`, `link_tujuan`, `is_read`, `created_at`) VALUES
(1, 1, '📋 Persetujuan Donasi Diperlukan', 'Donasi #1 menunggu persetujuan akhir dari Admin Pusat.', 'warning', '/kitabantu/modules/donasi/donasi_uang.php?id=1', 1, '2026-06-19 16:10:04'),
(2, 1, '📋 Persetujuan Donasi Diperlukan', 'Donasi #3 menunggu persetujuan akhir dari Admin Pusat.', 'warning', '/kitabantu/modules/donasi/donasi_uang.php?id=3', 1, '2026-06-19 16:32:04'),
(3, 1, '📋 Persetujuan Donasi Diperlukan', 'Donasi #6 menunggu persetujuan akhir dari Admin Pusat.', 'warning', '/kitabantu/modules/donasi/donasi_uang.php?id=6', 1, '2026-06-19 16:41:28'),
(4, 1, '📋 Persetujuan Donasi Diperlukan', 'Donasi #5 menunggu persetujuan akhir dari Admin Pusat.', 'warning', '/kitabantu/modules/donasi/donasi_uang.php?id=5', 1, '2026-06-19 16:41:47'),
(5, 1, '🚨 Permintaan Tingkat KRISIS Masuk', 'Admin Pusat (Hasan) mengirimkan permintaan logistik dengan urgensi KRISIS.', 'danger', '/kitabantu/modules/logistik/permintaan.php', 1, '2026-06-20 12:12:56'),
(6, 1, '🚨 Permintaan Tingkat KRISIS Masuk', 'Admin Pusat (Hasan) mengirimkan permintaan logistik dengan urgensi KRISIS.', 'danger', '/kitabantu/modules/logistik/permintaan.php', 1, '2026-06-20 12:16:28'),
(7, 1, '🚨 Permintaan Tingkat KRISIS Masuk', 'Admin Pusat (Hasan) mengirimkan permintaan logistik dengan urgensi KRISIS.', 'danger', '/kitabantu/modules/logistik/permintaan.php', 1, '2026-06-20 12:35:14');

-- --------------------------------------------------------

--
-- Table structure for table `pengiriman_armada`
--

CREATE TABLE `pengiriman_armada` (
  `id` int NOT NULL,
  `permintaan_id` int NOT NULL,
  `nama_supir` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_kendaraan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_armada` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kapasitas_muatan` int DEFAULT NULL COMMENT 'Kapasitas dalam kg/unit',
  `foto_serah_terima` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan_pengiriman` text COLLATE utf8mb4_unicode_ci,
  `tgl_kirim` datetime DEFAULT NULL,
  `tgl_sampai` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengiriman_armada`
--

INSERT INTO `pengiriman_armada` (`id`, `permintaan_id`, `nama_supir`, `no_kendaraan`, `jenis_armada`, `kapasitas_muatan`, `foto_serah_terima`, `catatan_pengiriman`, `tgl_kirim`, `tgl_sampai`, `created_at`) VALUES
(1, 13, 'Supir Siaga Korban Bencana', 'L 1540 VT', 'Truck Logistik', NULL, NULL, NULL, '2026-06-20 18:02:02', NULL, '2026-06-20 18:02:02'),
(2, 14, 'Supir Siaga Korban Bencana', 'L 5191 VT', 'Truck Logistik', NULL, NULL, NULL, '2026-06-20 18:03:41', '2026-06-20 18:04:26', '2026-06-20 18:03:41'),
(3, 15, 'Supir Siaga Korban Bencana', 'L 3726 AB', 'Truck Logistik Medium', NULL, NULL, NULL, '2026-06-20 18:34:49', '2026-06-20 18:35:08', '2026-06-20 18:34:49');

-- --------------------------------------------------------

--
-- Table structure for table `permintaan_logistik`
--

CREATE TABLE `permintaan_logistik` (
  `id` int NOT NULL,
  `posko_id` int DEFAULT NULL,
  `kampanye_id` int DEFAULT NULL,
  `barang_diminta` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kuantitas` int NOT NULL DEFAULT '1',
  `satuan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unit',
  `tingkat_urgensi` enum('normal','tinggi','krisis') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `status_tiket` enum('pending','diproses','dikirim','diterima','dibatalkan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `dibuat_oleh` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permintaan_logistik`
--

INSERT INTO `permintaan_logistik` (`id`, `posko_id`, `kampanye_id`, `barang_diminta`, `kuantitas`, `satuan`, `tingkat_urgensi`, `catatan`, `status_tiket`, `dibuat_oleh`, `created_at`, `updated_at`) VALUES
(2, NULL, NULL, 'Tenda darurat', 1, 'Pcs', 'normal', NULL, 'pending', 1, '2026-06-20 12:06:47', '2026-06-20 12:06:47'),
(3, NULL, NULL, 'Tenda darurat', 1, 'Pcs', 'tinggi', NULL, 'pending', 1, '2026-06-20 12:06:57', '2026-06-20 12:06:57'),
(4, 1, NULL, 'Air Mineral Galon', 1, 'galon', 'normal', NULL, 'pending', 1, '2026-06-20 12:10:03', '2026-06-20 12:10:03'),
(5, 1, NULL, 'Oralit', 2, 'sachet', 'normal', NULL, 'pending', 1, '2026-06-20 12:10:03', '2026-06-20 12:10:03'),
(6, 1, NULL, 'Pakaian Kering', 1, 'set', 'normal', NULL, 'pending', 1, '2026-06-20 12:10:03', '2026-06-20 12:10:03'),
(7, NULL, NULL, 'Tenda darurat', 1, 'Pcs', 'tinggi', NULL, 'pending', 1, '2026-06-20 12:10:56', '2026-06-20 12:10:56'),
(8, NULL, NULL, 'kamp', 1, 'Pcs', 'normal', NULL, 'pending', 1, '2026-06-20 12:10:56', '2026-06-20 12:10:56'),
(9, NULL, NULL, 'Tenda darurat', 1, 'Pcs', 'krisis', NULL, 'pending', 1, '2026-06-20 12:12:56', '2026-06-20 12:12:56'),
(10, NULL, NULL, 'baju', 1, 'Pcs', 'krisis', NULL, 'pending', 1, '2026-06-20 12:16:28', '2026-06-20 12:16:28'),
(11, NULL, NULL, 'Tenda darurat', 1, 'Pcs', 'krisis', NULL, 'pending', 1, '2026-06-20 12:35:14', '2026-06-20 12:35:14'),
(12, NULL, NULL, 'baju', 1, 'Pcs', 'krisis', NULL, 'pending', 1, '2026-06-20 12:35:14', '2026-06-20 12:35:14'),
(13, NULL, NULL, 'Selimut', 9, 'Pcs', 'normal', NULL, 'dikirim', 1, '2026-06-20 18:02:02', '2026-06-20 18:02:02'),
(14, NULL, NULL, 'Karung', 3, 'Pcs', 'tinggi', NULL, 'diterima', 1, '2026-06-20 18:03:41', '2026-06-20 18:04:26'),
(15, NULL, 41, 'Tenda darurat', 1, 'Pcs', 'tinggi', NULL, 'diterima', 1, '2026-06-20 18:34:49', '2026-06-20 18:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `posko_lapangan`
--

CREATE TABLE `posko_lapangan` (
  `id` int NOT NULL,
  `kampanye_id` int NOT NULL,
  `nama_posko` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat_posko` text COLLATE utf8mb4_unicode_ci,
  `jml_balita` int NOT NULL DEFAULT '0',
  `jml_lansia` int NOT NULL DEFAULT '0',
  `jml_ibu_hamil` int NOT NULL DEFAULT '0',
  `total_jiwa` int NOT NULL DEFAULT '0',
  `petugas_id` int DEFAULT NULL COMMENT 'Koordinator posko',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status_posko` enum('aktif','tidak_aktif') COLLATE utf8mb4_unicode_ci DEFAULT 'aktif',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posko_lapangan`
--

INSERT INTO `posko_lapangan` (`id`, `kampanye_id`, `nama_posko`, `alamat_posko`, `jml_balita`, `jml_lansia`, `jml_ibu_hamil`, `total_jiwa`, `petugas_id`, `latitude`, `longitude`, `status_posko`, `created_at`) VALUES
(1, 16, 'posko ciani', 'df', 1, 1, 1, 1, NULL, '1.0000000', '1.0000000', 'aktif', '2026-06-20 12:10:03');

-- --------------------------------------------------------

--
-- Table structure for table `relawan`
--

CREATE TABLE `relawan` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keahlian` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `status_aktif` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `relawan`
--

INSERT INTO `relawan` (`id`, `user_id`, `nama`, `no_hp`, `email`, `keahlian`, `alamat`, `status_aktif`, `created_at`) VALUES
(1, NULL, 'Lazuardi Aditya', '082140694756', 'lazuardiaditya17@gmail.com', 'Teknologi', 'Krian, Sidoarjo, Jawa Timur', 1, '2026-06-20 12:11:48');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_status_pengiriman`
--

CREATE TABLE `riwayat_status_pengiriman` (
  `id` int NOT NULL,
  `pengiriman_id` int NOT NULL,
  `status` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `dicatat_oleh` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `riwayat_status_pengiriman`
--

INSERT INTO `riwayat_status_pengiriman` (`id`, `pengiriman_id`, `status`, `catatan`, `dicatat_oleh`, `created_at`) VALUES
(1, 1, 'Dalam Perjalanan', 'Permintaan diverifikasi otomatis. Paket logistik sedang menuju lokasi posko.', 1, '2026-06-20 18:02:02'),
(2, 2, 'Dalam Perjalanan', 'Permintaan diverifikasi otomatis. Paket logistik sedang menuju lokasi posko.', 1, '2026-06-20 18:03:41'),
(3, 2, 'Tiba di Posko', NULL, 1, '2026-06-20 18:04:26'),
(4, 3, 'Dalam Perjalanan', 'Permintaan diverifikasi sistem. Paket logistik sedang dalam perjalanan menuju lokasi.', 1, '2026-06-20 18:34:49'),
(5, 3, 'Dalam Perjalanan', NULL, 1, '2026-06-20 18:35:00'),
(6, 3, 'Tiba di Posko', NULL, 1, '2026-06-20 18:35:08'),
(7, 3, 'Serah Terima Selesai', NULL, 1, '2026-06-20 18:35:13');

-- --------------------------------------------------------

--
-- Table structure for table `stok_inventaris`
--

CREATE TABLE `stok_inventaris` (
  `id` int NOT NULL,
  `gudang_id` int NOT NULL,
  `nama_barang` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` enum('sembako','obat_obatan','pakaian','tenda_sanitasi','khusus_rentan','lainnya') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lainnya',
  `kuantitas` int NOT NULL DEFAULT '0',
  `satuan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unit',
  `tgl_kedaluwarsa` date DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stok_inventaris`
--

INSERT INTO `stok_inventaris` (`id`, `gudang_id`, `nama_barang`, `kategori`, `kuantitas`, `satuan`, `tgl_kedaluwarsa`, `keterangan`, `updated_at`) VALUES
(1, 1, 'Beras', 'sembako', 100, 'kg', '2026-11-27', 'sda', '2026-06-20 10:38:56'),
(2, 1, 'Masker Medis 3-Ply', 'lainnya', 50, 'Box', NULL, NULL, '2026-06-20 18:16:19');

-- --------------------------------------------------------

--
-- Table structure for table `template_kebutuhan_bencana`
--

CREATE TABLE `template_kebutuhan_bencana` (
  `id` int NOT NULL,
  `jenis_bencana_id` int NOT NULL,
  `nama_barang` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `satuan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rasio_per_jiwa` decimal(6,3) NOT NULL DEFAULT '1.000',
  `kategori_stok` enum('sembako','obat_obatan','pakaian','tenda_sanitasi','khusus_rentan','lainnya') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lainnya'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `template_kebutuhan_bencana`
--

INSERT INTO `template_kebutuhan_bencana` (`id`, `jenis_bencana_id`, `nama_barang`, `satuan`, `rasio_per_jiwa`, `kategori_stok`) VALUES
(1, 1, 'Beras', 'kg', '0.500', 'sembako'),
(2, 1, 'Tenda Keluarga', 'unit', '0.100', 'tenda_sanitasi'),
(3, 1, 'P3K Set', 'kit', '0.050', 'obat_obatan'),
(4, 2, 'Air Mineral Galon', 'galon', '0.200', 'sembako'),
(5, 2, 'Oralit', 'sachet', '2.000', 'obat_obatan'),
(6, 2, 'Pakaian Kering', 'set', '1.000', 'pakaian');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin_pusat','admin_keuangan','admin_logistik','petugas_lapangan','donatur') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'donatur',
  `foto_profil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_aktif` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `foto_profil`, `no_hp`, `is_aktif`, `created_at`, `updated_at`) VALUES
(1, 'Admin Pusat', 'admin@kitabantu.id', 'admin123', 'admin_pusat', NULL, NULL, 1, '2026-06-17 21:19:28', '2026-06-18 05:26:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `donasi_barang`
--
ALTER TABLE `donasi_barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_donasi_barang_user` (`user_id`),
  ADD KEY `fk_donasi_barang_kampanye` (`kampanye_id`);

--
-- Indexes for table `donasi_uang`
--
ALTER TABLE `donasi_uang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_referensi` (`nomor_referensi`),
  ADD KEY `fk_donasi_user` (`user_id`),
  ADD KEY `fk_donasi_kampanye` (`kampanye_id`),
  ADD KEY `fk_donasi_pengaju` (`diajukan_oleh`),
  ADD KEY `fk_donasi_penyetuju` (`disetujui_oleh`);

--
-- Indexes for table `gudang_pusat`
--
ALTER TABLE `gudang_pusat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jadwal_piket`
--
ALTER TABLE `jadwal_piket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_piket_relawan` (`relawan_id`),
  ADD KEY `fk_piket_posko` (`posko_id`);

--
-- Indexes for table `jenis_bencana`
--
ALTER TABLE `jenis_bencana`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_jenis` (`nama_jenis`);

--
-- Indexes for table `kampanye_bencana`
--
ALTER TABLE `kampanye_bencana`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kampanye_jenis` (`jenis_bencana_id`),
  ADD KEY `fk_kampanye_creator` (`created_by`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_user` (`user_id`);

--
-- Indexes for table `pengiriman_armada`
--
ALTER TABLE `pengiriman_armada`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pengiriman_permintaan` (`permintaan_id`);

--
-- Indexes for table `permintaan_logistik`
--
ALTER TABLE `permintaan_logistik`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_permintaan_posko` (`posko_id`),
  ADD KEY `fk_permintaan_dibuat` (`dibuat_oleh`);

--
-- Indexes for table `posko_lapangan`
--
ALTER TABLE `posko_lapangan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_posko_kampanye` (`kampanye_id`),
  ADD KEY `fk_posko_petugas` (`petugas_id`);

--
-- Indexes for table `relawan`
--
ALTER TABLE `relawan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_relawan_user` (`user_id`);

--
-- Indexes for table `riwayat_status_pengiriman`
--
ALTER TABLE `riwayat_status_pengiriman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_riwayat_pengiriman` (`pengiriman_id`),
  ADD KEY `fk_riwayat_pencatat` (`dicatat_oleh`);

--
-- Indexes for table `stok_inventaris`
--
ALTER TABLE `stok_inventaris`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stok_gudang` (`gudang_id`);

--
-- Indexes for table `template_kebutuhan_bencana`
--
ALTER TABLE `template_kebutuhan_bencana`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_template_jenis` (`jenis_bencana_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `donasi_barang`
--
ALTER TABLE `donasi_barang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `donasi_uang`
--
ALTER TABLE `donasi_uang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `gudang_pusat`
--
ALTER TABLE `gudang_pusat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jadwal_piket`
--
ALTER TABLE `jadwal_piket`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jenis_bencana`
--
ALTER TABLE `jenis_bencana`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `kampanye_bencana`
--
ALTER TABLE `kampanye_bencana`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pengiriman_armada`
--
ALTER TABLE `pengiriman_armada`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `permintaan_logistik`
--
ALTER TABLE `permintaan_logistik`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `posko_lapangan`
--
ALTER TABLE `posko_lapangan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `relawan`
--
ALTER TABLE `relawan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `riwayat_status_pengiriman`
--
ALTER TABLE `riwayat_status_pengiriman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stok_inventaris`
--
ALTER TABLE `stok_inventaris`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `template_kebutuhan_bencana`
--
ALTER TABLE `template_kebutuhan_bencana`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `donasi_barang`
--
ALTER TABLE `donasi_barang`
  ADD CONSTRAINT `fk_donasi_barang_kampanye` FOREIGN KEY (`kampanye_id`) REFERENCES `kampanye_bencana` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_donasi_barang_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `donasi_uang`
--
ALTER TABLE `donasi_uang`
  ADD CONSTRAINT `fk_donasi_kampanye` FOREIGN KEY (`kampanye_id`) REFERENCES `kampanye_bencana` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_donasi_pengaju` FOREIGN KEY (`diajukan_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_donasi_penyetuju` FOREIGN KEY (`disetujui_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_donasi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `jadwal_piket`
--
ALTER TABLE `jadwal_piket`
  ADD CONSTRAINT `fk_piket_posko` FOREIGN KEY (`posko_id`) REFERENCES `posko_lapangan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_piket_relawan` FOREIGN KEY (`relawan_id`) REFERENCES `relawan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kampanye_bencana`
--
ALTER TABLE `kampanye_bencana`
  ADD CONSTRAINT `fk_kampanye_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_kampanye_jenis` FOREIGN KEY (`jenis_bencana_id`) REFERENCES `jenis_bencana` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengiriman_armada`
--
ALTER TABLE `pengiriman_armada`
  ADD CONSTRAINT `fk_pengiriman_permintaan` FOREIGN KEY (`permintaan_id`) REFERENCES `permintaan_logistik` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `permintaan_logistik`
--
ALTER TABLE `permintaan_logistik`
  ADD CONSTRAINT `fk_permintaan_dibuat` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_permintaan_posko` FOREIGN KEY (`posko_id`) REFERENCES `posko_lapangan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posko_lapangan`
--
ALTER TABLE `posko_lapangan`
  ADD CONSTRAINT `fk_posko_kampanye` FOREIGN KEY (`kampanye_id`) REFERENCES `kampanye_bencana` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_posko_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `relawan`
--
ALTER TABLE `relawan`
  ADD CONSTRAINT `fk_relawan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `riwayat_status_pengiriman`
--
ALTER TABLE `riwayat_status_pengiriman`
  ADD CONSTRAINT `fk_riwayat_pencatat` FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_riwayat_pengiriman` FOREIGN KEY (`pengiriman_id`) REFERENCES `pengiriman_armada` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stok_inventaris`
--
ALTER TABLE `stok_inventaris`
  ADD CONSTRAINT `fk_stok_gudang` FOREIGN KEY (`gudang_id`) REFERENCES `gudang_pusat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_kebutuhan_bencana`
--
ALTER TABLE `template_kebutuhan_bencana`
  ADD CONSTRAINT `fk_template_jenis` FOREIGN KEY (`jenis_bencana_id`) REFERENCES `jenis_bencana` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
