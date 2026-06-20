<?php
// config/koneksi.php
// Koneksi PDO dengan error handling yang proper

define('DB_HOST', 'localhost');
define('DB_NAME', 'kitabantu');
define('DB_USER', 'root');
define('DB_PASS', '');           // Ganti dengan password MySQL kamu
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,   // Wajib false untuk keamanan
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Di production: jangan tampilkan error detail ke user
    error_log("DB Connection Error: " . $e->getMessage());
    die(json_encode(['status' => 'error', 'pesan' => 'Koneksi database gagal.']));
}

