<?php
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Permintaan tidak valid.');
}

$id = (int)($_POST['id'] ?? 0);
$adminId = (int)($_SESSION['admin_id'] ?? 0);
$action = (string)($_POST['action'] ?? '');

if ($id <= 0 || $adminId <= 0 || !in_array($action, ['heartbeat', 'release'], true)) {
    http_response_code(400);
    exit('Parameter tidak valid.');
}

if ($action === 'heartbeat') {
    $stmt = mysqli_prepare($conn, "UPDATE kunjungan SET lock_at = NOW() WHERE id_kunjungan = ? AND lock_admin_id = ?");
} else {
    $stmt = mysqli_prepare($conn, "UPDATE kunjungan SET lock_admin_id = NULL, lock_at = NULL WHERE id_kunjungan = ? AND lock_admin_id = ?");
}

if (!$stmt) {
    http_response_code(500);
    exit('Gagal menyiapkan pembaruan kunci detail.');
}

mysqli_stmt_bind_param($stmt, 'ii', $id, $adminId);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    http_response_code(500);
    exit('Gagal memperbarui kunci detail.');
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
