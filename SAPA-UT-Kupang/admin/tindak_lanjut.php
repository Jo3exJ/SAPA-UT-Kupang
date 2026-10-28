<?php

require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Permintaan tidak valid.');
}


/*
|--------------------------------------------------------------------------
| DATA DASAR
|--------------------------------------------------------------------------
*/

$id = (int) ($_POST['id'] ?? 0);

$statusBaru = $_POST['status_layanan'] ?? 'belum';

$idPetugas = (
    ($_POST['id_petugas'] ?? '') !== ''
)
    ? (int) $_POST['id_petugas']
    : null;

$catatan = trim($_POST['catatan'] ?? '');
$aktorAdmin = (int)($_SESSION['admin_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| VALIDASI ID
|--------------------------------------------------------------------------
*/

if ($id <= 0) {

    header('Location: antrian.php');
    exit;

}


/*
|--------------------------------------------------------------------------
| VALIDASI STATUS
|--------------------------------------------------------------------------
*/

$allowed = [
    'belum',
    'diproses',
    'selesai'
];

if (!in_array($statusBaru, $allowed, true)) {

    header(
        "Location: detail_pengunjung.php?id=$id"
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| AMBIL DATA KUNJUNGAN
|--------------------------------------------------------------------------
*/

$q = mysqli_prepare(
    $conn,
    "SELECT
        status_layanan,
        id_petugas
     FROM kunjungan
     WHERE id_kunjungan = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $q,
    'i',
    $id
);

mysqli_stmt_execute($q);

$result = mysqli_stmt_get_result($q);

$data = mysqli_fetch_assoc($result);


if (!$data) {

    header('Location: antrian.php');
    exit;

}


$statusLama = $data['status_layanan'];


/*
|--------------------------------------------------------------------------
| PETUGAS
|--------------------------------------------------------------------------
|
| Jika tidak dipilih, gunakan akun admin yang sedang login.
|
*/

if (!$idPetugas) {

    $idPetugas = (int) (
        $_SESSION['admin_id'] ?? 0
    );

}


if ($idPetugas <= 0) {

    die('ID petugas tidak ditemukan.');

}

if ($aktorAdmin <= 0) {
    die('ID admin yang sedang login tidak ditemukan.');
}


/*
|--------------------------------------------------------------------------
| CATATAN WAJIB DIISI
|--------------------------------------------------------------------------
|
| Karena catatan merupakan tindakan yang dilakukan petugas,
| setiap tindak lanjut harus memiliki catatan.
|
*/

if ($catatan === '') {

    $_SESSION['error_tindak_lanjut'] =
        'Catatan tindakan wajib diisi.';

    header(
        "Location: detail_pengunjung.php?id=$id"
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| UPDATE KUNJUNGAN
|--------------------------------------------------------------------------
*/

if ($statusBaru === 'belum') {

    /*
    | Jika dikembalikan ke BELUM,
    | petugas yang menangani juga dikosongkan.
    */

    $st = mysqli_prepare(
        $conn,
        "UPDATE kunjungan
         SET
            status_layanan = 'belum',
            id_petugas = NULL,
            petugas_penyelesaian = NULL,
            waktu_selesai = NULL
         WHERE id_kunjungan = ?"
    );

    mysqli_stmt_bind_param(
        $st,
        'i',
        $id
    );

} elseif ($statusBaru === 'diproses') {

    /*
    | Status DIPROSES:
    | simpan petugas yang sedang menangani.
    */

    $st = mysqli_prepare(
        $conn,
        "UPDATE kunjungan
         SET
            status_layanan = 'diproses',
            id_petugas = ?,
            petugas_penyelesaian = NULL,
            waktu_selesai = NULL
         WHERE id_kunjungan = ?"
    );

    mysqli_stmt_bind_param(
        $st,
        'ii',
        $idPetugas,
        $id
    );

} else {

    /*
    |--------------------------------------------------------------------------
    | STATUS SELESAI
    |--------------------------------------------------------------------------
    |
    | Tidak ada lagi konfirmasi kedua.
    | Begitu disimpan, langsung menjadi SELESAI.
    |
    */

    $namaPetugas = '';

    $qPetugas = mysqli_prepare(
        $conn,
        "SELECT nama
         FROM admins
         WHERE id_admin = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $qPetugas,
        'i',
        $idPetugas
    );

    mysqli_stmt_execute($qPetugas);

    $resultPetugas =
        mysqli_stmt_get_result($qPetugas);

    $dataPetugas =
        mysqli_fetch_assoc($resultPetugas);

    if ($dataPetugas) {

        $namaPetugas =
            $dataPetugas['nama'];

    }


    /*
    | Simpan petugas penyelesaian,
    | tanggal dan waktu selesai.
    */

    $st = mysqli_prepare(
        $conn,
        "UPDATE kunjungan
         SET
            status_layanan = 'selesai',
            id_petugas = ?,
            petugas_penyelesaian = ?,
            waktu_selesai = NOW()
         WHERE id_kunjungan = ?"
    );

    mysqli_stmt_bind_param(
        $st,
        'isi',
        $idPetugas,
        $namaPetugas,
        $id
    );

}


/*
|--------------------------------------------------------------------------
| EKSEKUSI UPDATE
|--------------------------------------------------------------------------
*/

if (!mysqli_stmt_execute($st)) {

    die(
        'Gagal memperbarui data: '
        . mysqli_stmt_error($st)
    );

}


/*
|--------------------------------------------------------------------------
| SIMPAN CATATAN TINDAKAN
|--------------------------------------------------------------------------
|
| CATATAN DI SINI ADALAH ISI YANG DIKETIK PETUGAS.
|
| Contoh:
| "Mengirim data persetujuan ke pusat"
|
*/

$catat = mysqli_prepare(
    $conn,
    "INSERT INTO catatan_tindakan
        (
            id_kunjungan,
            id_admin,
            status_dari,
            status_ke,
            catatan,
            created_at
        )
     VALUES
        (?, ?, ?, ?, ?, NOW())"
);

mysqli_stmt_bind_param(
    $catat,
    'iisss',
    $id,
    $aktorAdmin,
    $statusLama,
    $statusBaru,
    $catatan
);

mysqli_stmt_execute($catat);
require_once __DIR__ . '/notifikasi_helper.php';
$namaAktor = trim((string)($_SESSION['admin_nama'] ?? 'Petugas'));
$pesanAktivitas = $namaAktor . ' mengubah status menjadi ' . strtoupper($statusBaru) . '.';
if ($catatan !== '') $pesanAktivitas .= ' Catatan: ' . mb_substr($catatan, 0, 220);
buatNotifikasi($conn, $statusBaru === 'selesai' ? 'layanan_selesai' : 'aktivitas_petugas', $statusBaru === 'selesai' ? 'Layanan selesai' : 'Aktivitas petugas', $pesanAktivitas, $id, 'detail_pengunjung.php?id=' . $id);


/*
|--------------------------------------------------------------------------
| PESAN BERHASIL
|--------------------------------------------------------------------------
*/

catatAktivitas($conn, 'Mengubah status layanan', 'Pengunjung ID '.$id.': '.$statusLama.' → '.$statusBaru.($catatan!==''?' | Catatan: '.$catatan:''));

$_SESSION['success_tindak_lanjut'] =
    'Tindak lanjut berhasil disimpan.';


/*
|--------------------------------------------------------------------------
| KEMBALI KE DETAIL
|--------------------------------------------------------------------------
*/

header(
    "Location: detail_pengunjung.php?id=$id"
);

exit;

?>