<?php

date_default_timezone_set('Asia/Makassar');

require_once '../auth.php';


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$tanggalAwal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggalAkhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

$petugasFilter = (int) ($_GET['petugas'] ?? 0);


/*
|--------------------------------------------------------------------------
| VALIDASI
|--------------------------------------------------------------------------
*/

if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAwal)
) {

    $tanggalAwal = date('Y-m-01');

}

if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAkhir)
) {

    $tanggalAkhir = date('Y-m-d');

}


if ($tanggalAwal > $tanggalAkhir) {

    $tmp = $tanggalAwal;
    $tanggalAwal = $tanggalAkhir;
    $tanggalAkhir = $tmp;

}


/*
|--------------------------------------------------------------------------
| FILTER PETUGAS
|--------------------------------------------------------------------------
*/

$wherePetugas = '';

if ($petugasFilter > 0) {

    $wherePetugas =
        "AND k.id_petugas = $petugasFilter";

}


/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        a.id_admin,
        a.nama,
        a.username,
        a.aktif,

        COUNT(k.id_kunjungan) AS total_kasus,

        SUM(
            CASE
                WHEN k.status_layanan = 'belum'
                THEN 1
                ELSE 0
            END
        ) AS kasus_baru,

        SUM(
            CASE
                WHEN k.status_layanan = 'diproses'
                THEN 1
                ELSE 0
            END
        ) AS kasus_diproses,

        SUM(
            CASE
                WHEN k.status_layanan = 'selesai'
                THEN 1
                ELSE 0
            END
        ) AS kasus_selesai

    FROM admins a

    LEFT JOIN kunjungan k
        ON k.id_petugas = a.id_admin
        AND k.tanggal BETWEEN '$tanggalAwal'
        AND '$tanggalAkhir'
        $wherePetugas

    GROUP BY
        a.id_admin,
        a.nama,
        a.username,
        a.aktif

    ORDER BY
        total_kasus DESC,
        a.nama ASC
";


$q = mysqli_query($conn, $sql);


if (!$q) {

    die(
        'Query export gagal: '
        . mysqli_error($conn)
    );

}


/*
|--------------------------------------------------------------------------
| NAMA FILE
|--------------------------------------------------------------------------
*/

$namaFile =
    'rekap_petugas_'
    . date(
        'Ymd',
        strtotime($tanggalAwal)
    )
    . '_'
    . date(
        'Ymd',
        strtotime($tanggalAkhir)
    )
    . '.xls';


/*
|--------------------------------------------------------------------------
| HEADER EXCEL
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/vnd.ms-excel; charset=utf-8'
);

header(
    'Content-Disposition: attachment; filename="' .
    $namaFile .
    '"'
);

header(
    'Cache-Control: max-age=0'
);

?>


<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <style>

        body {
            font-family: Arial, sans-serif;
        }

        .judul {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
        }

        .subjudul {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }

        .periode {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #073b82;
            color: white;
            font-weight: bold;
            text-align: center;
        }

        th,
        td {
            border: 1px solid #000000;
            padding: 7px;
        }

        td.center {
            text-align: center;
        }

        .total {
            font-weight: bold;
        }

    </style>

</head>


<body>


<!-- =========================================================
     JUDUL
========================================================= -->

<div class="judul">

    REKAP KASUS PETUGAS

</div>


<div class="subjudul">

    UNIT : UT KUPANG

</div>


<div class="periode">

    Periode:
    <?= date(
        'd-m-Y',
        strtotime($tanggalAwal)
    ) ?>

    s/d

    <?= date(
        'd-m-Y',
        strtotime($tanggalAkhir)
    ) ?>

</div>


<br>


<!-- =========================================================
     TABEL
========================================================= -->

<table>

    <tr>

        <th>
            No
        </th>

        <th>
            Nama Petugas
        </th>

        <th>
            Username
        </th>

        <th>
            Status Akun
        </th>

        <th>
            Total Kasus
        </th>

        <th>
            Baru
        </th>

        <th>
            Diproses
        </th>

        <th>
            Selesai
        </th>

    </tr>


    <?php

    $no = 1;

    $grandTotal = 0;
    $grandBaru = 0;
    $grandProses = 0;
    $grandSelesai = 0;

    ?>


    <?php while (
        $row = mysqli_fetch_assoc($q)
    ): ?>


        <?php

        $total =
            (int) (
                $row['total_kasus'] ?? 0
            );

        $baru =
            (int) (
                $row['kasus_baru'] ?? 0
            );

        $proses =
            (int) (
                $row['kasus_diproses'] ?? 0
            );

        $selesai =
            (int) (
                $row['kasus_selesai'] ?? 0
            );


        $grandTotal += $total;
        $grandBaru += $baru;
        $grandProses += $proses;
        $grandSelesai += $selesai;

        ?>


        <tr>

            <td class="center">
                <?= $no++ ?>
            </td>

            <td>
                <?= htmlspecialchars(
                    $row['nama']
                ) ?>
            </td>

            <td>
                <?= htmlspecialchars(
                    $row['username']
                ) ?>
            </td>

            <td class="center">

                <?= (
                    (int)$row['aktif'] === 1
                )
                    ? 'Aktif'
                    : 'Nonaktif'
                ?>

            </td>

            <td class="center">
                <?= $total ?>
            </td>

            <td class="center">
                <?= $baru ?>
            </td>

            <td class="center">
                <?= $proses ?>
            </td>

            <td class="center">
                <?= $selesai ?>
            </td>

        </tr>


    <?php endwhile; ?>


    <!-- TOTAL -->

    <tr class="total">

        <td
            colspan="4"
            class="center"
        >
            TOTAL
        </td>

        <td class="center">
            <?= $grandTotal ?>
        </td>

        <td class="center">
            <?= $grandBaru ?>
        </td>

        <td class="center">
            <?= $grandProses ?>
        </td>

        <td class="center">
            <?= $grandSelesai ?>
        </td>

    </tr>


</table>


</body>

</html>