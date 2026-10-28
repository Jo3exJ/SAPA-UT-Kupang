<?php

$title = 'Laporan';

require 'header.php';

date_default_timezone_set('Asia/Makassar');


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$q = trim($_GET['q'] ?? '');

$statusPengunjung = $_GET['status'] ?? '';

$statusLayanan = $_GET['status_layanan'] ?? '';

$prodi = trim($_GET['prodi'] ?? '');

$layanan = trim($_GET['layanan'] ?? '');

$periode = $_GET['periode'] ?? '';

$awal = $_GET['awal'] ?? '';

$akhir = $_GET['akhir'] ?? '';


/*
|--------------------------------------------------------------------------
| WHERE
|--------------------------------------------------------------------------
*/

$where = [];


/*
|--------------------------------------------------------------------------
| PENCARIAN
|--------------------------------------------------------------------------
*/

if ($q !== '') {

    $qEsc = mysqli_real_escape_string(
        $conn,
        $q
    );

    $where[] = "
        (
            k.nama LIKE '%$qEsc%'
            OR k.nim LIKE '%$qEsc%'
            OR k.telepon LIKE '%$qEsc%'
        )
    ";
}


/*
|--------------------------------------------------------------------------
| STATUS PENGUNJUNG
|--------------------------------------------------------------------------
*/

if (
    in_array(
        $statusPengunjung,
        [
            'mahasiswa',
            'alumni',
            'masyarakat'
        ],
        true
    )
) {

    $sEsc = mysqli_real_escape_string(
        $conn,
        $statusPengunjung
    );

    $where[] = "
        k.status = '$sEsc'
    ";
}


/*
|--------------------------------------------------------------------------
| STATUS LAYANAN
|--------------------------------------------------------------------------
|
| Database:
| belum     = New
| diproses  = Diproses
| selesai   = Selesai
|
*/

if (
    in_array(
        $statusLayanan,
        [
            'belum',
            'diproses',
            'selesai'
        ],
        true
    )
) {

    $slEsc = mysqli_real_escape_string(
        $conn,
        $statusLayanan
    );

    $where[] = "
        k.status_layanan = '$slEsc'
    ";
}


/*
|--------------------------------------------------------------------------
| PROGRAM STUDI
|--------------------------------------------------------------------------
*/

if ($prodi !== '') {

    $pEsc = mysqli_real_escape_string(
        $conn,
        $prodi
    );

    $where[] = "
        k.prodi = '$pEsc'
    ";
}


/*
|--------------------------------------------------------------------------
| LAYANAN
|--------------------------------------------------------------------------
*/

if ($layanan !== '') {

    $lEsc = mysqli_real_escape_string(
        $conn,
        $layanan
    );

    $where[] = "
        k.layanan = '$lEsc'
    ";
}


/*
|--------------------------------------------------------------------------
| PERIODE
|--------------------------------------------------------------------------
*/

if ($periode === 'hari_ini') {

    $where[] = "
        k.tanggal = CURDATE()
    ";
}


elseif ($periode === 'minggu_ini') {

    $where[] = "
        YEARWEEK(
            k.tanggal,
            1
        )
        =
        YEARWEEK(
            CURDATE(),
            1
        )
    ";
}


elseif ($periode === 'bulan_ini') {

    $where[] = "
        YEAR(k.tanggal) = YEAR(CURDATE())
        AND
        MONTH(k.tanggal) = MONTH(CURDATE())
    ";
}


elseif ($periode === 'tahun_ini') {

    $where[] = "
        YEAR(k.tanggal) = YEAR(CURDATE())
    ";
}


elseif (
    $periode === 'custom'
    && $awal !== ''
    && $akhir !== ''
) {

    $awalEsc = mysqli_real_escape_string(
        $conn,
        $awal
    );

    $akhirEsc = mysqli_real_escape_string(
        $conn,
        $akhir
    );

    $where[] = "
        k.tanggal BETWEEN
        '$awalEsc'
        AND
        '$akhirEsc'
    ";
}


/*
|--------------------------------------------------------------------------
| WHERE SQL
|--------------------------------------------------------------------------
*/

$whereSQL = '';

if (!empty($where)) {

    $whereSQL =
        'WHERE ' .
        implode(
            ' AND ',
            $where
        );
}


/*
|--------------------------------------------------------------------------
| QUERY DATA LAPORAN
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        k.*,

        ap.nama AS petugas_proses

    FROM kunjungan k

    LEFT JOIN admins ap
        ON ap.id_admin = k.id_petugas

    $whereSQL

    ORDER BY
        k.tanggal ASC,
        k.id_kunjungan ASC
";


$result = mysqli_query(
    $conn,
    $sql
);


if (!$result) {

    die(
        'Query laporan gagal: ' .
        mysqli_error($conn)
    );

}


/*
|--------------------------------------------------------------------------
| SIMPAN DATA KE ARRAY
|--------------------------------------------------------------------------
*/

$dataLaporan = [];


while (
    $row = mysqli_fetch_assoc($result)
) {

    $dataLaporan[] = $row;
}


/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/

$totalData = count(
    $dataLaporan
);

$totalBaru = 0;

$totalDiproses = 0;

$totalSelesai = 0;


foreach (
    $dataLaporan
    as $row
) {

    $status =
        $row['status_layanan']
        ?? 'belum';


    if (
        $status === 'selesai'
    ) {

        $totalSelesai++;

    }

    elseif (
        $status === 'diproses'
    ) {

        $totalDiproses++;

    }

    else {

        $totalBaru++;
    }
}


/*
|--------------------------------------------------------------------------
| DROPDOWN PRODI
|--------------------------------------------------------------------------
*/

$queryProdi = mysqli_query(
    $conn,
    "
    SELECT DISTINCT
        prodi
    FROM kunjungan
    WHERE prodi IS NOT NULL
      AND prodi <> ''
    ORDER BY prodi ASC
    "
);


/*
|--------------------------------------------------------------------------
| DROPDOWN LAYANAN
|--------------------------------------------------------------------------
*/

$queryLayanan = mysqli_query(
    $conn,
    "
    SELECT DISTINCT
        layanan
    FROM kunjungan
    WHERE layanan IS NOT NULL
      AND layanan <> ''
    ORDER BY layanan ASC
    "
);


/*
|--------------------------------------------------------------------------
| FUNGSI
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


function tanggalIndonesia(
    $tanggal
) {

    if (
        empty($tanggal)
    ) {

        return '-';
    }

    $time = strtotime(
        $tanggal
    );

    if (!$time) {

        return $tanggal;
    }

    return date(
        'd/m/Y',
        $time
    );
}


function statusLayananText(
    $status
) {

    switch ($status) {

        case 'selesai':
            return 'Selesai';

        case 'diproses':
            return 'Diproses';

        case 'belum':
        default:
            return 'New';
    }
}

?>

<style>

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.laporan-header {

    margin-bottom: 20px;

}


.laporan-header h2 {

    font-weight: 700;

    margin-bottom: 5px;

}


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

.filter-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 18px;

    margin-bottom: 20px;

}


.filter-title {

    font-weight: 700;

    margin-bottom: 15px;

}


/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/

.laporan-stat {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 16px;

    height: 100%;

}


.laporan-stat small {

    color: #6b7280;

}


.laporan-stat h3 {

    margin: 5px 0 0;

    font-weight: 700;

}


/*
|--------------------------------------------------------------------------
| CARD TABLE
|--------------------------------------------------------------------------
*/

.laporan-table-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 18px;

}


/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

.laporan-table {

    font-size: 13px;

}


.laporan-table th {

    text-align: center;

    vertical-align: middle;

    white-space: nowrap;

    background: #f8f9fa;

}


.laporan-table td {

    vertical-align: middle;

}


.laporan-table .center {

    text-align: center;

}


.laporan-table .wrap {

    white-space: normal;

    min-width: 160px;

}


.status-badge {

    font-size: 11px;

    padding: 5px 8px;

}


/*
|--------------------------------------------------------------------------
| MODAL KONFIRMASI
|--------------------------------------------------------------------------
*/

.action-confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 33, 56, .52);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 9999;
    backdrop-filter: blur(2px);
}

.action-confirm-overlay.show {
    display: flex;
}

.action-confirm-box {
    width: 100%;
    max-width: 430px;
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 18px 50px rgba(0, 0, 0, .18);
    border: 1px solid #e5eaf0;
    animation: actionConfirmIn .16s ease-out;
}

.action-confirm-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #eaf3ff;
    color: #004b93;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 14px;
}

.action-confirm-title {
    margin: 0 0 7px;
    font-size: 19px;
    font-weight: 700;
    color: #17324d;
}

.action-confirm-text {
    margin: 0;
    color: #718096;
    line-height: 1.55;
    font-size: 14px;
}

.action-confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 20px;
}

@keyframes actionConfirmIn {
    from {
        opacity: 0;
        transform: translateY(8px) scale(.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/*
|--------------------------------------------------------------------------
| PRINT
|--------------------------------------------------------------------------
*/

.print-only {

    display: none;

}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (
    max-width: 768px
) {

    .filter-card {

        padding: 14px;

    }


    .laporan-table-card {

        padding: 10px;

    }


    .laporan-header h2 {

        font-size: 22px;

    }

}


/*
|--------------------------------------------------------------------------
| CETAK
|--------------------------------------------------------------------------
*/

@media print {

    body {

        background: #ffffff !important;

        font-size: 10pt;

    }


    .navbar,
    .sidebar,
    .filter-card,
    .laporan-header,
    .no-print {

        display: none !important;

    }


    .layout {

        display: block !important;

    }


    .content {

        margin: 0 !important;

        padding: 0 !important;

        width: 100% !important;

    }


    .laporan-table-card {

        border: none !important;

        padding: 0 !important;

        margin: 0 !important;

    }


    .print-only {

        display: block !important;

    }


    .print-title {

        text-align: center;

        margin-bottom: 18px;

    }


    .print-title h2 {

        font-size: 16pt;

        font-weight: bold;

        margin: 0;

    }


    .print-title h3 {

        font-size: 12pt;

        margin: 4px 0;

    }


    .print-title p {

        margin: 3px 0;

        font-size: 10pt;

    }


    .laporan-table {

        width: 100% !important;

        border-collapse: collapse !important;

        font-size: 8pt !important;

    }


    .laporan-table th,
    .laporan-table td {

        border: 1px solid #000 !important;

        padding: 4px !important;

    }


    .laporan-table th {

        background: #ffffff !important;

    }


    @page {

        size: landscape;

        margin: 10mm;

    }

}

</style>


<!-- =========================================================
     HEADER
========================================================= -->

<div class="laporan-header">

    <div
        class="d-flex
               justify-content-between
               align-items-center
               gap-2"
    >

        <div>

            <h2>
                📊 Laporan
            </h2>

            <p class="text-muted mb-0">
                Rekap data layanan dan keluhan pengunjung.
            </p>

        </div>


        <div class="no-print">

            <button
                type="button"
                class="btn btn-primary"
                onclick="bukaKonfirmasiAksi('print')"
            >

                🖨️ Cetak Laporan

            </button>

        </div>

    </div>

</div>


<!-- =========================================================
     FILTER
========================================================= -->

<div class="filter-card no-print">

    <div class="filter-title">

        🔎 Filter Laporan

    </div>


    <form method="get">

        <div class="row g-2">


            <!-- CARI -->

            <div class="col-lg-3 col-md-6">

                <label class="form-label">
                    Cari
                </label>

                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Nama / NIM / HP"
                    value="<?= e($q) ?>"
                >

            </div>


            <!-- STATUS PENGUNJUNG -->

            <div class="col-lg-2 col-md-6">

                <label class="form-label">
                    Status Pengunjung
                </label>

                <select
                    name="status"
                    class="form-select"
                >

                    <option value="">
                        Semua
                    </option>

                    <option
                        value="mahasiswa"
                        <?= $statusPengunjung === 'mahasiswa'
                            ? 'selected'
                            : '' ?>
                    >
                        Mahasiswa
                    </option>

                    <option
                        value="alumni"
                        <?= $statusPengunjung === 'alumni'
                            ? 'selected'
                            : '' ?>
                    >
                        Alumni
                    </option>

                    <option
                        value="masyarakat"
                        <?= $statusPengunjung === 'masyarakat'
                            ? 'selected'
                            : '' ?>
                    >
                        Masyarakat
                    </option>

                </select>

            </div>


            <!-- STATUS LAYANAN -->

            <div class="col-lg-2 col-md-6">

                <label class="form-label">
                    Status Layanan
                </label>

                <select
                    name="status_layanan"
                    class="form-select"
                >

                    <option value="">
                        Semua
                    </option>

                    <option
                        value="belum"
                        <?= $statusLayanan === 'belum'
                            ? 'selected'
                            : '' ?>
                    >
                        New
                    </option>

                    <option
                        value="diproses"
                        <?= $statusLayanan === 'diproses'
                            ? 'selected'
                            : '' ?>
                    >
                        Diproses
                    </option>

                    <option
                        value="selesai"
                        <?= $statusLayanan === 'selesai'
                            ? 'selected'
                            : '' ?>
                    >
                        Selesai
                    </option>

                </select>

            </div>


            <!-- PRODI -->

            <div class="col-lg-2 col-md-6">

                <label class="form-label">
                    Program Studi
                </label>

                <select
                    name="prodi"
                    class="form-select"
                >

                    <option value="">
                        Semua Prodi
                    </option>

                    <?php if ($queryProdi): ?>

                        <?php while (
                            $rowProdi =
                            mysqli_fetch_assoc(
                                $queryProdi
                            )
                        ): ?>

                            <option
                                value="<?= e(
                                    $rowProdi['prodi']
                                ) ?>"
                                <?= $prodi ===
                                    $rowProdi['prodi']
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e(
                                    $rowProdi['prodi']
                                ) ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

            </div>


            <!-- LAYANAN -->

            <div class="col-lg-3 col-md-6">

                <label class="form-label">
                    Layanan
                </label>

                <select
                    name="layanan"
                    class="form-select"
                >

                    <option value="">
                        Semua Layanan
                    </option>

                    <?php if ($queryLayanan): ?>

                        <?php while (
                            $rowLayanan =
                            mysqli_fetch_assoc(
                                $queryLayanan
                            )
                        ): ?>

                            <option
                                value="<?= e(
                                    $rowLayanan['layanan']
                                ) ?>"
                                <?= $layanan ===
                                    $rowLayanan['layanan']
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e(
                                    $rowLayanan['layanan']
                                ) ?>

                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

            </div>


            <!-- PERIODE -->

            <div class="col-lg-3 col-md-6">

                <label class="form-label">
                    Periode
                </label>

                <select
                    name="periode"
                    id="periode"
                    class="form-select"
                    onchange="toggleCustomDate()"
                >

                    <option value="">
                        Semua Periode
                    </option>

                    <option
                        value="hari_ini"
                        <?= $periode === 'hari_ini'
                            ? 'selected'
                            : '' ?>
                    >
                        Hari Ini
                    </option>

                    <option
                        value="minggu_ini"
                        <?= $periode === 'minggu_ini'
                            ? 'selected'
                            : '' ?>
                    >
                        Minggu Ini
                    </option>

                    <option
                        value="bulan_ini"
                        <?= $periode === 'bulan_ini'
                            ? 'selected'
                            : '' ?>
                    >
                        Bulan Ini
                    </option>

                    <option
                        value="tahun_ini"
                        <?= $periode === 'tahun_ini'
                            ? 'selected'
                            : '' ?>
                    >
                        Tahun Ini
                    </option>

                    <option
                        value="custom"
                        <?= $periode === 'custom'
                            ? 'selected'
                            : '' ?>
                    >
                        Custom
                    </option>

                </select>

            </div>


            <!-- DARI -->

            <div
                class="col-lg-2 col-md-6 custom-date"
            >

                <label class="form-label">
                    Dari
                </label>

                <input
                    type="date"
                    name="awal"
                    class="form-control"
                    value="<?= e($awal) ?>"
                >

            </div>


            <!-- SAMPAI -->

            <div
                class="col-lg-2 col-md-6 custom-date"
            >

                <label class="form-label">
                    Sampai
                </label>

                <input
                    type="date"
                    name="akhir"
                    class="form-control"
                    value="<?= e($akhir) ?>"
                >

            </div>


            <!-- TAMPILKAN -->

            <div class="col-lg-2 col-md-6">

                <label class="form-label d-block">
                    &nbsp;
                </label>

                <button
                    type="submit"
                    class="btn btn-primary w-100"
                >

                    🔎 Tampilkan

                </button>

            </div>


            <!-- RESET -->

            <div class="col-lg-2 col-md-6">

                <label class="form-label d-block">
                    &nbsp;
                </label>

                <a
                    href="laporan.php"
                    class="btn btn-outline-secondary w-100"
                >

                    Reset

                </a>

            </div>


            <!-- EXCEL -->

            <div class="col-lg-2 col-md-6">

                <label class="form-label d-block">
                    &nbsp;
                </label>

                <button
                    type="button"
                    class="btn btn-success w-100"
                    onclick="bukaKonfirmasiAksi('excel')"
                >

                    📥 Excel

                </button>

            </div>


        </div>

    </form>

</div>


<!-- =========================================================
     STATISTIK
========================================================= -->

<div class="row g-3 mb-4 no-print">


    <!-- TOTAL -->

    <div class="col-md-3">

        <div class="laporan-stat">

            <small>
                Total Data
            </small>

            <h3>
                <?= $totalData ?>
            </h3>

        </div>

    </div>


    <!-- BARU -->

    <div class="col-md-3">

        <div class="laporan-stat">

            <small>
                New
            </small>

            <h3>
                <?= $totalBaru ?>
            </h3>

        </div>

    </div>


    <!-- PROSES -->

    <div class="col-md-3">

        <div class="laporan-stat">

            <small>
                Diproses
            </small>

            <h3>
                <?= $totalDiproses ?>
            </h3>

        </div>

    </div>


    <!-- SELESAI -->

    <div class="col-md-3">

        <div class="laporan-stat">

            <small>
                Selesai
            </small>

            <h3>
                <?= $totalSelesai ?>
            </h3>

        </div>

    </div>


</div>


<!-- =========================================================
     TABEL
========================================================= -->

<div class="laporan-table-card">


    <!-- JUDUL SAAT PRINT -->

    <div class="print-only print-title">

        <h2>
            BUKU BESAR DAFTAR LAYANAN/KELUHAN
        </h2>

        <h3>
            UNIT : UT KUPANG
        </h3>

        <p>
            MASA REGISTRASI : <?= date('Y') ?>
        </p>

        <?php if ($periode === 'hari_ini'): ?>

            <p>
                Periode: Hari Ini
            </p>

        <?php elseif (
            $periode === 'minggu_ini'
        ): ?>

            <p>
                Periode: Minggu Ini
            </p>

        <?php elseif (
            $periode === 'bulan_ini'
        ): ?>

            <p>
                Periode: Bulan Ini
            </p>

        <?php elseif (
            $periode === 'tahun_ini'
        ): ?>

            <p>
                Periode: Tahun Ini
            </p>

        <?php elseif (
            $periode === 'custom'
            && $awal !== ''
            && $akhir !== ''
        ): ?>

            <p>
                Periode:
                <?= e($awal) ?>
                s/d
                <?= e($akhir) ?>
            </p>

        <?php endif; ?>

    </div>


    <!-- JUDUL NORMAL -->

    <div
        class="d-flex
               justify-content-between
               align-items-center
               mb-3
               no-print"
    >

        <div>

            <h5 class="mb-1">
                Daftar Laporan
            </h5>

            <small class="text-muted">

                <?= $totalData ?>
                data ditemukan

            </small>

        </div>

    </div>


    <!-- TABLE -->

    <div class="table-responsive">

        <table
            class="table
                   table-bordered
                   laporan-table"
        >

            <thead>

                <tr>

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


                <tr>

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

            </thead>


            <tbody>


            <?php if (
                !empty($dataLaporan)
            ): ?>


                <?php
                $i = 1;
                ?>


                <?php foreach (
                    $dataLaporan
                    as $x
                ): ?>


                    <?php

                    /*
                    | IDENTITAS
                    */

                    $identitas = '';

                    if (
                        !empty(
                            $x['nim']
                        )
                    ) {

                        $identitas .=
                            e($x['nim']);
                    }


                    if (
                        !empty(
                            $x['prodi']
                        )
                    ) {

                        if (
                            $identitas !== ''
                        ) {

                            $identitas .=
                                '<br>';
                        }

                        $identitas .=
                            e($x['prodi']);
                    }


                    if (
                        $identitas === ''
                    ) {

                        $identitas = '-';
                    }


                    /*
                    | PERMINTAAN
                    */

                    $permintaan =
                        trim(
                            $x['layanan']
                            ?? ''
                        );


                    if (
                        $permintaan === ''
                    ) {

                        $permintaan =
                            '-';
                    }


                    /*
                    | KELUHAN
                    */

                    $subLayanan =
                        trim(
                            $x['sub_layanan']
                            ?? ''
                        );

                    $lainnya =
                        trim(
                            $x['lainnya']
                            ?? ''
                        );


                    if (
                        strtolower(
                            $subLayanan
                        ) === 'lainnya'
                    ) {

                        if (
                            $lainnya !== ''
                        ) {

                            $keluhan =
                                'LAINNYA - ' .
                                $lainnya;

                        } else {

                            $keluhan =
                                'LAINNYA';
                        }

                    }

                    elseif (
                        $subLayanan !== ''
                    ) {

                        $keluhan =
                            $subLayanan;

                    }

                    else {

                        $keluhan = '-';
                    }


                    /*
                    | STATUS
                    */

                    $status =
                        $x[
                            'status_layanan'
                        ]
                        ?? 'belum';


                    /*
                    | PENYELESAIAN
                    */

                    $penyelesaian =
                        statusLayananText(
                            $status
                        );


                    /*
                    | TGL SELESAI
                    */

                    $tglSelesai = '-';


                    if (
                        $status ===
                            'selesai'
                        &&
                        !empty(
                            $x[
                                'waktu_selesai'
                            ]
                        )
                    ) {

                        $tglSelesai =
                            tanggalIndonesia(
                                $x[
                                    'waktu_selesai'
                                ]
                            );
                    }


                    /*
                    | NAMA
                    */

                    $nama =
                        $x['nama']
                        ?? '-';


                    /*
                    | PETUGAS
                    */

                    $petugas = '-';


                    if (
                        $status ===
                            'selesai'
                        &&
                        !empty(
                            $x[
                                'petugas_penyelesaian'
                            ]
                        )
                    ) {

                        $petugas =
                            $x[
                                'petugas_penyelesaian'
                            ];

                    }

                    elseif (
                        $status ===
                            'diproses'
                        &&
                        !empty(
                            $x[
                                'petugas_proses'
                            ]
                        )
                    ) {

                        $petugas =
                            $x[
                                'petugas_proses'
                            ];
                    }

                    ?>


                    <tr>


                        <td class="center">

                            <?= $i++ ?>

                        </td>


                        <td class="center">

                            <?= e(
                                tanggalIndonesia(
                                    $x['tanggal']
                                )
                            ) ?>

                        </td>


                        <td class="center">

                            <?php
                            $sumberLink = $x['sumber_link'] ?? '';
                            if ($sumberLink === 'wa_admin'):
                            ?>
                                <span class="badge bg-success status-badge">
                                    WhatsApp Admin
                                </span>
                            <?php elseif ($sumberLink === 'lokasi_universitas'): ?>
                                <span class="badge bg-primary status-badge">
                                    Lokasi Universitas
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>

                        </td>


                        <td class="center">

                            <?= $identitas ?>

                        </td>


                        <td class="wrap">

                            <?= e(
                                $permintaan
                            ) ?>

                        </td>


                        <td class="wrap">

                            <?= e(
                                $keluhan
                            ) ?>

                        </td>


                        <td class="center">

                            <?= e(
                                $penyelesaian
                            ) ?>

                        </td>


                        <td class="center">

                            <?= e(
                                $tglSelesai
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                $nama
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                $petugas
                            ) ?>

                        </td>


                        <td class="center">

                            <?php if (
                                $status ===
                                'selesai'
                            ): ?>

                                <span
                                    class="badge
                                           bg-success
                                           status-badge"
                                >
                                    Selesai
                                </span>

                            <?php elseif (
                                $status ===
                                'diproses'
                            ): ?>

                                <span
                                    class="badge
                                           bg-info
                                           text-dark
                                           status-badge"
                                >
                                    On Progress
                                </span>

                            <?php else: ?>

                                <span
                                    class="badge
                                           bg-warning
                                           text-dark
                                           status-badge"
                                >
                                    Belum Selesai
                                </span>

                            <?php endif; ?>

                        </td>


                    </tr>


                <?php endforeach; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="11"
                        class="text-center py-4"
                    >

                        Tidak ada data
                        sesuai filter.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>


<!-- =========================================================
     MODAL KONFIRMASI AKSI
========================================================= -->

<div
    id="actionConfirmOverlay"
    class="action-confirm-overlay"
    aria-hidden="true"
>

    <div
        class="action-confirm-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="actionConfirmTitle"
    >

        <div class="action-confirm-icon" id="actionConfirmIcon">
            ⚠️
        </div>

        <h3 class="action-confirm-title" id="actionConfirmTitle">
            Konfirmasi
        </h3>

        <p class="action-confirm-text" id="actionConfirmText">
            Yakin ingin melanjutkan?
        </p>

        <div class="action-confirm-actions">

            <button
                type="button"
                class="btn btn-outline-secondary"
                onclick="tutupKonfirmasiAksi()"
            >
                Batal
            </button>

            <button
                type="button"
                class="btn btn-primary"
                id="actionConfirmButton"
                onclick="lanjutkanAksi()"
            >
                Ya, Lanjutkan
            </button>

        </div>

    </div>

</div>


<script>

let aksiKonfirmasi = null;

function bukaKonfirmasiAksi(jenis)
{
    const overlay = document.getElementById('actionConfirmOverlay');
    const title = document.getElementById('actionConfirmTitle');
    const text = document.getElementById('actionConfirmText');
    const icon = document.getElementById('actionConfirmIcon');
    const button = document.getElementById('actionConfirmButton');

    if (!overlay) return;

    if (jenis === 'excel') {
        aksiKonfirmasi = 'excel';
        icon.textContent = '📥';
        title.textContent = 'Konfirmasi Export Excel';
        text.textContent = 'Yakin ingin mengekspor data laporan ke Excel? Data yang diekspor akan mengikuti filter yang sedang aktif.';
        button.textContent = 'Ya, Export Excel';
        button.className = 'btn btn-success';
    } else {
        aksiKonfirmasi = 'print';
        icon.textContent = '🖨️';
        title.textContent = 'Konfirmasi Cetak';
        text.textContent = 'Yakin ingin mencetak laporan yang sedang ditampilkan? Hasil cetak akan mengikuti filter yang sedang aktif.';
        button.textContent = 'Ya, Cetak';
        button.className = 'btn btn-primary';
    }

    overlay.classList.add('show');
    overlay.setAttribute('aria-hidden', 'false');
}

function tutupKonfirmasiAksi()
{
    const overlay = document.getElementById('actionConfirmOverlay');
    if (!overlay) return;

    overlay.classList.remove('show');
    overlay.setAttribute('aria-hidden', 'true');
    aksiKonfirmasi = null;
}

function lanjutkanAksi()
{
    if (aksiKonfirmasi === 'print') {
        tutupKonfirmasiAksi();
        setTimeout(function() {
            window.print();
        }, 80);
        return;
    }

    if (aksiKonfirmasi === 'excel') {
        const url = 'export_excel.php?<?= http_build_query($_GET) ?>';
        window.location.href = url;
        return;
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        tutupKonfirmasiAksi();
    }
});

document.getElementById('actionConfirmOverlay')?.addEventListener('click', function(event) {
    if (event.target === this) {
        tutupKonfirmasiAksi();
    }
});

</script>


<script>

/*
|--------------------------------------------------------------------------
| CUSTOM DATE
|--------------------------------------------------------------------------
*/

function toggleCustomDate()
{

    const periode =
        document.getElementById(
            'periode'
        ).value;


    const boxes =
        document.querySelectorAll(
            '.custom-date'
        );


    boxes.forEach(
        function(box)
        {

            if (
                periode === 'custom'
            ) {

                box.style.display =
                    '';

            }

            else {

                box.style.display =
                    'none';
            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| JALANKAN
|--------------------------------------------------------------------------
*/

toggleCustomDate();

</script>


<?php require 'footer.php'; ?>