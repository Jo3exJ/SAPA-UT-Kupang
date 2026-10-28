<?php
$sumberLink = $_GET['sumber'] ?? '';
if (!in_array($sumberLink, ['wa_admin', 'lokasi_universitas'], true)) {
    $sumberLink = 'lokasi_universitas';
}

$pesanKetiga = ($sumberLink === 'wa_admin')
    ? 'Silakan tunggu follow up dari petugas atau admin'
    : 'Silakan menunggu hingga nama Anda dipanggil petugas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Berhasil</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container py-4 py-md-5">

        <div class="user-page-brand">
            <img src="admin/assets/logo-ut.png" alt="Universitas Terbuka">
            <div class="user-page-brand-text">
                <div class="user-page-brand-title">UNIVERSITAS TERBUKA KUPANG</div>
                <div class="user-page-brand-subtitle">Portal Layanan Pengunjung</div>
            </div>
        </div>


    <div class="success-card">

<div class="success-icon">✓</div>
        <h3>Permohonan Layanan Atau Keluhan Anda Berhasil Dikirim</h3>
        <p class="text-muted">Silahkan Tunggu Follow Up Dari petugas.</p>

        <a href="index.php" class="btn btn-primary">Kembali ke Halaman Utama</a>
    </div>
</div>
</body>
</html>