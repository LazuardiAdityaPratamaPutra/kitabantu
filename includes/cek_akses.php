<?php
// includes/cek_akses.php
// Panggil di baris PALING ATAS setiap file yang butuh autentikasi

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login.
 * Jika tidak, redirect ke halaman login.
 */
function cekLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /kitabantu/modules/auth/login.php');
        exit;
    }
}

/**
 * Cek apakah role user sesuai.
 * @param array $rolesDiizinkan  Contoh: ['admin_pusat', 'admin_keuangan']
 */
function cekAkses(array $rolesDiizinkan): void {
    cekLogin();
    if (!in_array($_SESSION['role'], $rolesDiizinkan, true)) {
        http_response_code(403);
        include '/kitabantu/includes/header.php';
        echo '<div class="alert alert-danger m-4">⛔ Anda tidak memiliki akses ke halaman ini.</div>';
        include '/kitabantu/includes/footer.php';
        exit;
    }
}

// ─── Shortcut role check ────────────────────────────────────────
function isAdmin(): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin_pusat','admin_keuangan','admin_logistik']);
}

function isPetugas(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'petugas_lapangan';
}

function isDonatur(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'donatur';
}