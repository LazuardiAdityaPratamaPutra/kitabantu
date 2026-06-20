<?php
// koneksi.php

// Mencegah PHP menampilkan error mentah-mentah ke layar server production
error_reporting(0);
ini_set('display_errors', 0);

$host     = getenv('MYSQLHOST') ?: 'localhost';
$user     = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$dbname   = getenv('MYSQLDATABASE') ?: 'kitabantu';
$port     = getenv('MYSQLPORT') ?: '3306';

// Menggunakan @ untuk meredam fatal error jika database belum siap/tidak ada di Railway
$koneksi = @mysqli_connect($host, $user, $password, $dbname, $port);

// Jika gagal konek, biarkan aplikasi tetap berjalan (hanya query data yang kosong)
if (!$koneksi) {
    // Anda bisa membuat log atau membiarkannya agar halaman login/dashboard web tetap bisa dirender strukturnya
    $db_error = true; 
}