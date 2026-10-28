<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil koneksi dari folder utama project
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../schema_bootstrap.php';
if (!ensureAppSchema($conn)) {
    $_SESSION['system_error'] = 'Struktur database belum lengkap. Jalankan database_update_kjj.sql atau pastikan akun MySQL memiliki izin untuk memperbarui struktur database.';
    header('Location: error.php');
    exit;
}
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/log_helper.php';

set_exception_handler(function (Throwable $e) {
    if ($e instanceof mysqli_sql_exception) {
        $_SESSION['system_error'] = 'Sistem mengalami kendala saat mengakses database. Silakan coba lagi atau hubungi administrator.';
        header('Location: error.php');
        exit;
    }
    throw $e;
});