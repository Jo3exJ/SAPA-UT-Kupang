<?php
function catatAktivitas(mysqli $conn, string $aktivitas, string $detail = ''): void
{
    $idAdmin = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = @mysqli_prepare($conn, 'INSERT INTO log_aktivitas (id_admin, aktivitas, detail, ip_address) VALUES (?, ?, ?, ?)');
    if (!$stmt) return;
    mysqli_stmt_bind_param($stmt, 'isss', $idAdmin, $aktivitas, $detail, $ip);
    @mysqli_stmt_execute($stmt);
    @mysqli_stmt_close($stmt);
}
