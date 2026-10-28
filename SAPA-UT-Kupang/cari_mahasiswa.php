<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Endpoint hanya untuk membantu pengisian formulir; data tidak ditemukan bukan error.
require_once __DIR__ . '/koneksi.php';

$nim = trim((string)($_GET['nim'] ?? ''));
if (!preg_match('/^\d{9}$/', $nim)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'NIM tidak valid.']);
    exit;
}

// Jika tabel belum dibuat/diimpor, formulir tetap bisa dipakai manual.
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'mahasiswa'");
if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
    echo json_encode(['success' => false, 'not_found' => true, 'message' => 'Database mahasiswa belum tersedia.']);
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT nim, nama, prodi, alamat FROM mahasiswa WHERE nim = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Pencarian belum tersedia.']);
    exit;
}
mysqli_stmt_bind_param($stmt, 's', $nim);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'not_found' => true, 'message' => 'Data mahasiswa tidak ditemukan.']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => [
        'nim' => (string)$row['nim'],
        'nama' => (string)$row['nama'],
        'prodi' => (string)$row['prodi'],
        'alamat' => (string)$row['alamat']
    ]
], JSON_UNESCAPED_UNICODE);
