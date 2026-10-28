<?php
session_start();
require_once __DIR__ . '/csrf.php';

// Mulai alur baru: jangan gunakan sumber link dari kunjungan sebelumnya.
unset(
    $_SESSION['sumber_link'],
    $_SESSION['pengunjung']
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - Layanan Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .welcome-btn {
            min-width: 190px;
            min-height: 48px;
            font-weight: 600;
            border-radius: 12px;
        }
    </style>
</head>
<body>
<div class="page-wrap">
    <div class="container py-4 py-md-5">
        <div class="user-page-brand">
            <img src="admin/assets/logo-ut.png" alt="Universitas Terbuka">
            <div class="user-page-brand-text">
                <div class="user-page-brand-title">UNIVERSITAS TERBUKA KUPANG</div>
                <div class="user-page-brand-subtitle">Portal Layanan Pengunjung</div>
            </div>
        </div>

        <div class="hero-card">
            <h1 class="welcome-title">Selamat Datang</h1>
            <p class="welcome-text">
                Selamat datang di layanan Universitas Terbuka Kupang.
                Silakan isi formulir layanan atau keluhan agar data Anda
                dapat dicatat dan diteruskan kepada petugas.
            </p>

        
            <a href="sumber.php" class="btn btn-primary welcome-btn">Isi Formulir</a>
        </div>
    </div>
</div>
</body>
</html>
