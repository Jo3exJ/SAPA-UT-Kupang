<?php
/** Membuat notifikasi global untuk admin dan semua petugas yang login. */
function buatNotifikasi(mysqli $conn, string $jenis, string $judul, string $pesan, ?int $idKunjungan = null, ?string $url = null): void {
    $stmt = mysqli_prepare($conn, "INSERT INTO notifikasi (id_kunjungan, jenis, judul, pesan, url_tujuan, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if (!$stmt) return;
    mysqli_stmt_bind_param($stmt, 'issss', $idKunjungan, $jenis, $judul, $pesan, $url);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
