<?php
require 'auth.php';

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$w = [];

$q   = trim($_GET['q'] ?? '');
$s   = $_GET['status'] ?? '';
$sl  = $_GET['status_layanan'] ?? '';
$p   = trim($_GET['prodi'] ?? '');
$l   = trim($_GET['layanan'] ?? '');
$per = $_GET['periode'] ?? '';
$a   = $_GET['awal'] ?? '';
$b   = $_GET['akhir'] ?? '';


/*
|--------------------------------------------------------------------------
| PENCARIAN
|--------------------------------------------------------------------------
*/

if ($q !== '') {

    $e = mysqli_real_escape_string($conn, $q);

    $w[] = "(
        k.nama LIKE '%$e%'
        OR k.nim LIKE '%$e%'
        OR k.telepon LIKE '%$e%'
    )";
}


/*
|--------------------------------------------------------------------------
| STATUS PENGUNJUNG
|--------------------------------------------------------------------------
*/

if (in_array($s, [
    'mahasiswa',
    'alumni',
    'masyarakat'
], true)) {

    $sEsc = mysqli_real_escape_string($conn, $s);
    $w[] = "k.status='$sEsc'";
}

/*
|--------------------------------------------------------------------------
| STATUS LAYANAN
|--------------------------------------------------------------------------
*/

if (in_array($sl, ['belum', 'diproses', 'selesai'], true)) {
    $slEsc = mysqli_real_escape_string($conn, $sl);
    $w[] = "k.status_layanan='$slEsc'";
}


/*
|--------------------------------------------------------------------------
| PROGRAM STUDI
|--------------------------------------------------------------------------
*/

if ($p !== '') {

    $pEsc = mysqli_real_escape_string(
        $conn,
        $p
    );

    $w[] = "k.prodi='$pEsc'";
}


/*
|--------------------------------------------------------------------------
| LAYANAN
|--------------------------------------------------------------------------
*/

if ($l !== '') {

    $lEsc = mysqli_real_escape_string(
        $conn,
        $l
    );

    $w[] = "k.layanan='$lEsc'";
}


/*
|--------------------------------------------------------------------------
| PERIODE
|--------------------------------------------------------------------------
*/

if ($per === 'hari_ini') {

    $w[] = "k.tanggal = CURDATE()";
}


if ($per === 'minggu_ini') {

    $w[] = "
        YEARWEEK(k.tanggal, 1)
        =
        YEARWEEK(CURDATE(), 1)
    ";
}


if ($per === 'bulan_ini') {

    $w[] = "
        YEAR(k.tanggal) = YEAR(CURDATE())
        AND
        MONTH(k.tanggal) = MONTH(CURDATE())
    ";
}


if ($per === 'tahun_ini') {

    $w[] = "
        YEAR(k.tanggal) = YEAR(CURDATE())
    ";
}


if (
    $per === 'custom'
    && $a !== ''
    && $b !== ''
) {

    $aEsc = mysqli_real_escape_string(
        $conn,
        $a
    );

    $bEsc = mysqli_real_escape_string(
        $conn,
        $b
    );

    $w[] = "
        k.tanggal BETWEEN '$aEsc' AND '$bEsc'
    ";
}


/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        k.*,
        a.nama AS petugas_proses
    FROM kunjungan k

    LEFT JOIN admins a
        ON a.id_admin = k.id_petugas

    " .
    (
        $w
        ? " WHERE " . implode(" AND ", $w)
        : ""
    )
    . "

    ORDER BY k.tanggal ASC, k.id_kunjungan ASC
";


$r = mysqli_query($conn, $sql);


if (!$r) {

    die(
        "Query gagal: "
        . mysqli_error($conn)
    );

}


/*
|--------------------------------------------------------------------------
| NAMA FILE
|--------------------------------------------------------------------------
*/

$namaFile =
    'Buku_Besar_Layanan_'
    . date('Y-m-d')
    . '.xls';


/*
|--------------------------------------------------------------------------
| HEADER EXCEL
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/vnd.ms-excel; charset=UTF-8'
);

header(
    'Content-Disposition: attachment; filename="' .
    $namaFile .
    '"'
);

header('Pragma: no-cache');
header('Expires: 0');


/*
|--------------------------------------------------------------------------
| FUNGSI ESCAPE
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| FUNGSI STATUS
|--------------------------------------------------------------------------
*/

function statusText($status)
{
    switch ($status) {

        case 'selesai':
            return 'Selesai';

        case 'diproses':
            return 'On Progress';

        case 'belum':
        default:
            return 'Belum Selesai';
    }
}


/*
|--------------------------------------------------------------------------
| TANGGAL
|--------------------------------------------------------------------------
*/

function tanggalIndonesia($tanggal)
{
    if (!$tanggal) {
        return '';
    }

    $time = strtotime($tanggal);

    if (!$time) {
        return $tanggal;
    }

    return date('d/m/Y', $time);
}


/*
|--------------------------------------------------------------------------
| HTML EXCEL
|--------------------------------------------------------------------------
*/

echo '
<html>
<head>

<meta charset="UTF-8">

<style>

body {
    font-family: Arial, sans-serif;
    font-size: 10pt;
}

table {
    border-collapse: collapse;
    width: 100%;
}

td, th {
    border: 1px solid #000000;
    padding: 6px;
    vertical-align: middle;
}

.title {
    font-size: 16pt;
    font-weight: bold;
    color: #c00000;
    text-align: center;
    border: none;
}

.subtitle {
    font-size: 12pt;
    font-weight: bold;
    text-align: center;
    border: none;
}

.header {
    font-weight: bold;
    text-align: center;
    vertical-align: middle;
    background: #ffffff;
}

.center {
    text-align: center;
}

.left {
    text-align: left;
}

.wrap {
    white-space: normal;
}

</style>

</head>

<body>

<table>

    <!-- JUDUL -->

    <tr>
        <td colspan="12" class="title">
            BUKU BESAR DAFTAR LAYANAN/KELUHAN
        </td>
    </tr>

    <tr>
        <td colspan="12" class="subtitle">
            UNIT : UT KUPANG
        </td>
    </tr>

    <tr>
        <td colspan="12" class="subtitle">
            MASA REGISTRASI : ' .
            e(date('Y')) .
            '
        </td>
    </tr>

    <tr>
        <td colspan="11">
            &nbsp;
        </td>
    </tr>


    <!-- HEADER TABEL -->

    <tr class="header">

        <th rowspan="2">
            No
        </th>

        <th rowspan="2">
            Tanggal
        </th>

        <th rowspan="2">
            Sumber Link
        </th>

        <th rowspan="2">
            NAC/NIM/Instansi<br>
            Mahasiswa
        </th>

        <th rowspan="2">
            Permintaan<br>
            Pelayanan
        </th>

        <th rowspan="2">
            Keluhan
        </th>

        <th colspan="4">
            Proses Penyelesaian
        </th>

        <th rowspan="2">
            Status<br>
            Selesai/Tidak Selesai/<br>
            On Progress
        </th>

    </tr>


    <tr class="header">

        <th>
            Penyelesaian
        </th>

        <th>
            Tgl. Selesai
        </th>

        <th>
            Nama Mahasiswa atau<br>
            yang mewakili
        </th>

        <th>
            Petugas
        </th>

    </tr>
';


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$i = 1;

while ($x = mysqli_fetch_assoc($r)) {

    /*
    | NIM + PRODI
    |
    | Dibuat dalam satu kolom seperti
    | contoh buku besar.
    */

    $identitas = '';

    if (!empty($x['nim'])) {

        $identitas .=
            e($x['nim']);

    }

    if (!empty($x['prodi'])) {

        if ($identitas !== '') {
            $identitas .= '<br>';
        }

        $identitas .=
            e($x['prodi']);

    }

    if ($identitas === '') {

        $identitas = '-';

    }
    /*
    |--------------------------------------------------------------------------
    | PERMINTAAN PELAYANAN
    |--------------------------------------------------------------------------
    | Mengambil nama LAYANAN utama yang dipilih pengunjung.
    */

    $permintaan = trim($x['layanan'] ?? '');

    if ($permintaan === '') {
        $permintaan = '-';
    }


    /*
    |--------------------------------------------------------------------------
    | KELUHAN
    |--------------------------------------------------------------------------
    | Jika memilih sub layanan biasa:
    |   Keluhan = sub_layanan
    |
    | Jika memilih "Lainnya":
    |   Keluhan = LAINNYA - isi masalah yang diketik.
    */

    $subLayanan = trim($x['sub_layanan'] ?? '');
    $lainnya    = trim($x['lainnya'] ?? '');

    if (strtolower($subLayanan) === 'lainnya') {

        if ($lainnya !== '') {
            $keluhan = 'LAINNYA - ' . $lainnya;
        } else {
            $keluhan = 'LAINNYA';
        }

    } elseif ($subLayanan !== '') {

        $keluhan = $subLayanan;

    } else {

        $keluhan = '-';

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS

    */

    $status =
        $x['status_layanan']
        ?? 'belum';


    /*
    | PENYELESAIAN
    |
    | Menggunakan status layanan
    | karena database belum mempunyai
    | kolom khusus "penyelesaian".
    */

    if ($status === 'selesai') {

        $penyelesaian =
            'Selesai';

    } elseif ($status === 'diproses') {

        $penyelesaian =
            'Sedang Diproses';

    } else {

        $penyelesaian =
            'Belum Diurus';

    }


    /*
    | TANGGAL SELESAI
    */

    $tglSelesai = '-';

    if (
        $status === 'selesai'
        && !empty($x['waktu_selesai'])
    ) {

        $tglSelesai =
            tanggalIndonesia(
                $x['waktu_selesai']
            );

    }


    /*
    | NAMA PENGUNJUNG
    */

    $nama =
        $x['nama'] ?? '-';


    /*
    | PETUGAS
    |
    | Jika selesai:
    | gunakan petugas penyelesaian.
    |
    | Jika masih diproses:
    | gunakan petugas proses.
    */

    $petugas = '-';

    if (
        $status === 'selesai'
        && !empty($x['petugas_penyelesaian'])
    ) {

        $petugas =
            $x['petugas_penyelesaian'];

    } elseif (
        $status === 'diproses'
        && !empty($x['petugas_proses'])
    ) {

        $petugas =
            $x['petugas_proses'];

    }


    /*
    |--------------------------------------------------------------------------
    | BARIS
    |--------------------------------------------------------------------------
    */

    echo '

    <tr>

        <td class="center">
            ' . $i++ . '
        </td>

        <td class="center">
            ' . e(
                tanggalIndonesia(
                    $x['tanggal']
                )
            ) . '
        </td>

        <td class="left">
            ' . e(
                ($x['sumber_link'] ?? '') === 'wa_admin'
                    ? 'WhatsApp Admin'
                    : (($x['sumber_link'] ?? '') === 'lokasi_universitas'
                        ? 'Langsung dari Lokasi Universitas'
                        : '-')
            ) . '
        </td>

        <td class="center">
            ' . $identitas . '
        </td>

        <td class="left">
            ' . e($permintaan) . '
        </td>

        <td class="left wrap">
            ' . e($keluhan) . '
        </td>

        <td class="center">
            ' . e($penyelesaian) . '
        </td>

        <td class="center">
            ' . e($tglSelesai) . '
        </td>

        <td class="left">
            ' . e($nama) . '
        </td>

        <td class="left">
            ' . e($petugas) . '
        </td>

        <td class="center">
            ' . e(
                statusText($status)
            ) . '
        </td>

    </tr>

    ';
}


echo '

</table>

</body>
</html>

';

exit;
?>