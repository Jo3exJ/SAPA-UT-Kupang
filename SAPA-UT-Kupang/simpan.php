<?php
session_start();
require "koneksi.php";
require_once __DIR__ . "/schema_bootstrap.php";
ensureAppSchema($conn);
require "csrf.php";

if (!isset($_SESSION["pengunjung"])) {
    header("Location: index.php");
    exit;
}

if (!csrf_valid((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Permintaan tidak valid atau sesi formulir sudah kedaluwarsa. Silakan isi formulir kembali.');
}

$p = $_SESSION["pengunjung"];
$sumberLink = $p["sumber_link"] ?? ($_SESSION["sumber_link"] ?? "");

if (!in_array($sumberLink, ["wa_admin", "lokasi_universitas"], true)) {
    header("Location: sumber.php");
    exit;
}

$layanan = trim($_POST["layanan"] ?? "");
$sub = trim($_POST["sub_layanan"] ?? "");
$lainnya = trim($_POST["lainnya"] ?? "");

if ($layanan === "" || $sub === "") {
    die("Layanan/keluhan belum dipilih.");
}
if ($sub === "Lainnya" && $lainnya === "") {
    die("Silakan isi keterangan layanan/keluhan lainnya.");
}

/*
|--------------------------------------------------------------------------
| CEGAH SUBMIT GANDA
|--------------------------------------------------------------------------
| Jika data pengunjung yang sama mengirim layanan yang sama dalam
| rentang 2 menit, anggap sebagai klik/submit ganda.
|--------------------------------------------------------------------------
*/
$cekNama = trim((string)($p['nama'] ?? ''));
$cekTelepon = trim((string)($p['telepon'] ?? ''));

$cekDuplikat = mysqli_prepare(
    $conn,
    "SELECT id_kunjungan
     FROM kunjungan
     WHERE tanggal = CURDATE()
       AND nama = ?
       AND telepon = ?
       AND layanan = ?
       AND sub_layanan = ?
       AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
     ORDER BY id_kunjungan DESC
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $cekDuplikat,
    "ssss",
    $cekNama,
    $cekTelepon,
    $layanan,
    $sub
);
mysqli_stmt_execute($cekDuplikat);
$hasilDuplikat = mysqli_stmt_get_result($cekDuplikat);

if (mysqli_fetch_assoc($hasilDuplikat)) {
    $_SESSION['simpan_error'] =
        'Data yang sama baru saja dikirim. Sistem mencegah pengiriman ganda. Silakan tunggu sebentar atau kembali ke halaman utama.';
    header('Location: layanan.php');
    exit;
}

$stmt = mysqli_prepare($conn, "INSERT INTO kunjungan
(tanggal,nama,status,nim,prodi,pekerjaan,alamat,telepon,email,sumber_link,layanan,sub_layanan,lainnya)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");

mysqli_stmt_bind_param(
    $stmt, "sssssssssssss",
    $p["tanggal"], $p["nama"], $p["status"], $p["nim"], $p["prodi"],
    $p["pekerjaan"], $p["alamat"], $p["telepon"], $p["email"],
    $sumberLink, $layanan, $sub, $lainnya
);

if (!mysqli_stmt_execute($stmt)) {
    die("Data gagal disimpan: " . mysqli_error($conn));
}
$idBaru = (int)mysqli_insert_id($conn);
require_once __DIR__ . '/admin/notifikasi_helper.php';
$pesanNotif = ($sumberLink === 'wa_admin')
    ? 'Pengunjung dari WhatsApp Admin mengirim data. Data ini tidak dimasukkan ke antrean.'
    : 'Pengunjung dari lokasi universitas mengirim formulir baru.';
buatNotifikasi($conn, 'pengunjung_baru', 'Pengunjung baru: ' . $p['nama'], $pesanNotif, $idBaru, 'detail_pengunjung.php?id=' . $idBaru);

unset(
    $_SESSION["pengunjung"],
    $_SESSION["sumber_link"]
);
header("Location: berhasil.php?sumber=" . rawurlencode($sumberLink));
exit;
?>