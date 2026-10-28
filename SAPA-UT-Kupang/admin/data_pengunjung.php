<?php

$title = 'Data Pengunjung';

require 'header.php';


/*
|--------------------------------------------------------------------------
| PENGATURAN PAGINATION
|--------------------------------------------------------------------------
*/

$perPage = 10;

$page = (int)($_GET['page'] ?? 1);

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$w = [];

$q      = trim($_GET['q'] ?? '');
$s      = $_GET['status'] ?? '';
$p      = trim($_GET['prodi'] ?? '');
$l      = trim($_GET['layanan'] ?? '');
$per    = $_GET['periode'] ?? '';
$awal   = $_GET['awal'] ?? '';
$akhir  = $_GET['akhir'] ?? '';
$sort = $_GET['sort'] ?? 'terbaru';
if (!in_array($sort, ['terbaru','terlama'], true)) { $sort = 'terbaru'; }

$terlambat = isset($_GET['terlambat']) &&
             $_GET['terlambat'] == '1';


/*
|--------------------------------------------------------------------------
| PENCARIAN
|--------------------------------------------------------------------------
*/

if ($q !== '') {

    $e = mysqli_real_escape_string(
        $conn,
        $q
    );

    $w[] = "(
        k.nama LIKE '%$e%'
        OR k.nim LIKE '%$e%'
        OR k.telepon LIKE '%$e%'
        OR k.email LIKE '%$e%'
        OR k.layanan LIKE '%$e%'
        OR k.sub_layanan LIKE '%$e%'
    )";
}


/*
|--------------------------------------------------------------------------
| STATUS LAYANAN
|--------------------------------------------------------------------------
|
| New      = belum
| Diproses = diproses
| Selesai  = selesai
|
*/

if (in_array(
    $s,
    ['belum', 'diproses', 'selesai'],
    true
)) {

    $w[] = "k.status_layanan='$s'";
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

    $w[] = "k.sub_layanan='$lEsc'";
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
    && $awal !== ''
    && $akhir !== ''
) {

    $aEsc = mysqli_real_escape_string(
        $conn,
        $awal
    );

    $bEsc = mysqli_real_escape_string(
        $conn,
        $akhir
    );

    $w[] = "
        k.tanggal BETWEEN '$aEsc' AND '$bEsc'
    ";
}


/*
|--------------------------------------------------------------------------
| MODE NOTIFIKASI
|--------------------------------------------------------------------------
|
| Menampilkan data yang:
| - belum selesai
| - sudah 3 hari atau lebih
|
*/

if ($terlambat) {

    $w[] = "
        k.status_layanan <> 'selesai'
        AND DATEDIFF(
            CURDATE(),
            DATE(k.created_at)
        ) >= 3
    ";
}


/*
|--------------------------------------------------------------------------
| WHERE
|--------------------------------------------------------------------------
*/

$orderDir = $sort === 'terbaru' ? 'DESC' : 'ASC';

$whereSQL = '';

if (!empty($w)) {

    $whereSQL =
        ' WHERE ' .
        implode(' AND ', $w);

}


/*
|--------------------------------------------------------------------------
| HITUNG TOTAL DATA
|--------------------------------------------------------------------------
*/

$countSQL = "
    SELECT COUNT(*) AS total
    FROM kunjungan k
    $whereSQL
";

$countQuery = mysqli_query(
    $conn,
    $countSQL
);

if (!$countQuery) {

    die(
        'Query count error: ' .
        mysqli_error($conn)
    );

}

$countData = mysqli_fetch_assoc(
    $countQuery
);

$totalData = (int)(
    $countData['total'] ?? 0
);


/*
|--------------------------------------------------------------------------
| TOTAL HALAMAN
|--------------------------------------------------------------------------
*/

$totalPages = max(
    1,
    (int)ceil(
        $totalData / $perPage
    )
);


if ($page > $totalPages) {

    $page = $totalPages;

    $offset =
        ($page - 1) * $perPage;

}


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        k.*,

        a.nama AS petugas,
        DATEDIFF(CURDATE(), DATE(k.created_at)) AS hari_menunggu,

        (
            SELECT ct.catatan
            FROM catatan_tindakan ct
            WHERE ct.id_kunjungan = k.id_kunjungan
            ORDER BY
                ct.created_at DESC,
                ct.id_catatan DESC
            LIMIT 1
        ) AS aksi_terakhir

    FROM kunjungan k

    LEFT JOIN admins a
        ON a.id_admin = k.id_petugas

    $whereSQL

    ORDER BY
        k.tanggal $orderDir,
        k.id_kunjungan $orderDir

    LIMIT $offset, $perPage
";


$data = mysqli_query(
    $conn,
    $sql
);

if (!$data) {

    die(
        'Query data error: ' .
        mysqli_error($conn)
    );

}


/*
|--------------------------------------------------------------------------
| DROPDOWN PRODI
|--------------------------------------------------------------------------
*/

$ps = mysqli_query(
    $conn,
    "
    SELECT DISTINCT prodi
    FROM kunjungan
    WHERE prodi IS NOT NULL
      AND prodi <> ''
    ORDER BY prodi
    "
);


/*
|--------------------------------------------------------------------------
| DROPDOWN LAYANAN
|--------------------------------------------------------------------------
*/

$ls = mysqli_query(
    $conn,
    "
    SELECT DISTINCT sub_layanan
    FROM kunjungan
    WHERE sub_layanan IS NOT NULL
      AND sub_layanan <> ''
    ORDER BY sub_layanan
    "
);


$quickCounts=['all'=>0,'belum'=>0,'diproses'=>0,'selesai'=>0];
$qc=mysqli_query($conn,"SELECT status_layanan,COUNT(*) total FROM kunjungan GROUP BY status_layanan");
while($qr=$qc?mysqli_fetch_assoc($qc):null){$quickCounts[$qr['status_layanan']]=(int)$qr['total'];$quickCounts['all']+=(int)$qr['total'];}

/*
|--------------------------------------------------------------------------
| URL PAGINATION
|--------------------------------------------------------------------------
*/

$params = $_GET;

unset($params['page']);


function pageUrl(
    $page,
    $params
) {

    $params['page'] = $page;

    return 'data_pengunjung.php?' .
        http_build_query($params);
}

?>

<style>

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

.filter-card {

    background: #ffffff;

    border: 1px solid #edf0f4;

    border-radius: 14px;

    box-shadow:
        0 3px 15px rgba(0,0,0,.05);

    padding: 16px;

}


/*
|--------------------------------------------------------------------------
| TABEL
|--------------------------------------------------------------------------
*/

.data-table th {

    background: #f7f9fc;

    white-space: nowrap;

    font-size: 13px;

}

.data-table td {

    font-size: 13px;

    vertical-align: middle;

}


/*
|--------------------------------------------------------------------------
| PAGINATION INFO
|--------------------------------------------------------------------------
*/

.pagination-info {

    font-size: 13px;

    color: #6c757d;

}


/*
|--------------------------------------------------------------------------
| HEADER HALAMAN
|--------------------------------------------------------------------------
*/

.page-title {

    font-weight: 700;

    color: #17324d;

}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 768px) {
    .quick-status-bar{grid-template-columns:repeat(2,minmax(0,1fr));}

    .data-table {

        min-width: 950px;

    }

}

</style>


<!-- =========================================================
     HEADER HALAMAN
========================================================= -->

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>

        <h2 class="mb-1 page-title">
            Data Pengunjung
        </h2>


        <?php if ($terlambat): ?>

            <div class="text-danger small">

                🔔 Data yang belum selesai selama
                3 hari atau lebih

            </div>

        <?php else: ?>

            <div class="text-muted small">

            </div>

        <?php endif; ?>

    </div>

        <div>

        <a
            href="laporan.php"
            class="btn btn-primary"
        >

            📊 Laporan

        </a>

    </div>

</div>


<!-- =========================================================
     FILTER
========================================================= -->

<form
    class="cardbox mb-3"
    method="get"
>

    <div class="row g-2">


        <!-- PENCARIAN -->

        <div class="col-md-3">

            <input
                class="form-control"
                name="q"
                placeholder="Cari nama/NIM/HP"
                value="<?= htmlspecialchars($q) ?>"
            >

        </div>


        <!-- STATUS LAYANAN -->

        <div class="col-md-2">

            <select
                class="form-select"
                name="status"
            >

                <option value="">
                    Semua Status
                </option>

                <option
                    value="belum"
                    <?= $s === 'belum'
                        ? 'selected'
                        : '' ?>
                >
                    New
                </option>

                <option
                    value="diproses"
                    <?= $s === 'diproses'
                        ? 'selected'
                        : '' ?>
                >
                    Diproses
                </option>

                <option
                    value="selesai"
                    <?= $s === 'selesai'
                        ? 'selected'
                        : '' ?>
                >
                    Selesai
                </option>

            </select>

        </div>


        <!-- PROGRAM STUDI -->

        <div class="col-md-2">

            <select
                class="form-select"
                name="prodi"
            >

                <option value="">
                    Semua prodi
                </option>

                <?php while (
                    $x = mysqli_fetch_assoc($ps)
                ): ?>

                    <option
                        value="<?= htmlspecialchars(
                            $x['prodi']
                        ) ?>"
                        <?= $p === $x['prodi']
                            ? 'selected'
                            : '' ?>
                    >

                        <?= htmlspecialchars(
                            $x['prodi']
                        ) ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <!-- LAYANAN -->

        <div class="col-md-2">

            <select
                class="form-select"
                name="layanan"
            >

                <option value="">
                    Semua layanan
                </option>

                <?php while (
                    $x = mysqli_fetch_assoc($ls)
                ): ?>

                    <option
                        value="<?= htmlspecialchars(
                            $x['sub_layanan']
                        ) ?>"
                        <?= $l === $x['sub_layanan']
                            ? 'selected'
                            : '' ?>
                    >

                        <?= htmlspecialchars(
                            $x['sub_layanan']
                        ) ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <!-- PERIODE -->

        <div class="col-md-3">

            <select
                class="form-select"
                name="periode"
            >

                <option value="">
                    Semua periode
                </option>

                <option
                    value="hari_ini"
                    <?= $per === 'hari_ini'
                        ? 'selected'
                        : '' ?>
                >
                    Hari ini
                </option>

                <option
                    value="minggu_ini"
                    <?= $per === 'minggu_ini'
                        ? 'selected'
                        : '' ?>
                >
                    Minggu ini
                </option>

                <option
                    value="bulan_ini"
                    <?= $per === 'bulan_ini'
                        ? 'selected'
                        : '' ?>
                >
                    Bulan ini
                </option>

                <option
                    value="tahun_ini"
                    <?= $per === 'tahun_ini'
                        ? 'selected'
                        : '' ?>
                >
                    Tahun ini
                </option>

                <option
                    value="custom"
                    <?= $per === 'custom'
                        ? 'selected'
                        : '' ?>
                >
                    Custom
                </option>

            </select>

        </div>


        <!-- TANGGAL AWAL -->

        <div class="col-md-2">

            <input
                type="date"
                name="awal"
                value="<?= htmlspecialchars(
                    $awal
                ) ?>"
                class="form-control"
            >

        </div>


        <!-- TANGGAL AKHIR -->

        <div class="col-md-2">

            <input
                type="date"
                name="akhir"
                value="<?= htmlspecialchars(
                    $akhir
                ) ?>"
                class="form-control"
            >

        </div>


        <!-- URUTAN -->
        <div class="col-md-2"><select class="form-select" name="sort"><option value="terbaru" <?= $sort==='terbaru'?'selected':'' ?>>Terbaru</option><option value="terlama" <?= $sort==='terlama'?'selected':'' ?>>Terlama</option></select></div>

        <!-- BUTTON FILTER -->

        <div class="col-md-2">

            <button
                type="submit"
                class="btn btn-primary w-100"
            >

                🔎 Filter

            </button>

        </div>


        <!-- EXCEL -->

        <div class="col-md-2">

            <button
                type="button"
                id="btnExcel"
                class="btn btn-success w-100"
            >

                📥 Excel

            </button>

        </div>


        <!-- RESET FILTER -->

        <div class="col-md-2">

            <a
                class="btn btn-outline-secondary w-100"
                href="data_pengunjung.php"
            >

                Reset

            </a>

        </div>


        <?php if ($terlambat): ?>

            <div class="col-md-2">

                <a
                    href="data_pengunjung.php"
                    class="btn btn-outline-danger w-100"
                >

                    Tampilkan Semua

                </a>

            </div>

        <?php endif; ?>


    </div>

</form>


<!-- =========================================================
     INFORMASI DATA
========================================================= -->

<div class="d-flex justify-content-between align-items-center mb-2">

    <div class="pagination-info">

        <?php if ($totalData > 0): ?>

            Menampilkan

            <b>
                <?= $offset + 1 ?>
            </b>

            -

            <b>
                <?= min(
                    $offset + $perPage,
                    $totalData
                ) ?>
            </b>

            dari

            <b>
                <?= $totalData ?>
            </b>

            data

        <?php else: ?>

            Tidak ada data

        <?php endif; ?>

    </div>

</div>


<!-- =========================================================
     TABEL
========================================================= -->

<div class="cardbox">

    <div class="table-responsive">

        <table
            class="table table-hover
                   align-middle data-table"
        >

            <thead>

                <tr>

                    <th>
                        No
                    </th>

                    <th>
                        Tanggal
                    </th>

                    <th>
                        Sumber Link
                    </th>

                    <th>
                        Nama
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Prodi
                    </th>

                    <th>
                        Layanan
                    </th>

                    <th>
                        Status Layanan
                    </th>

                    <th>
                        Aksi
                    </th>

                    <th>
                        Petugas
                    </th>

                    <th class="sticky-detail">
                        Detail
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if (
                mysqli_num_rows($data) > 0
            ): ?>


                <?php
                $no = $offset + 1;
                ?>


                <?php while (
                    $x = mysqli_fetch_assoc($data)
                ): ?>

                    <?php $statusLayanan = $x['status_layanan'] ?? 'belum'; ?>

                    <tr class="<?= $statusLayanan === 'belum' && (int)($x['hari_menunggu'] ?? 0) >= 1 ? 'row-overdue' : ($statusLayanan === 'diproses' ? 'row-process' : ($statusLayanan === 'selesai' ? 'row-done' : '')) ?>">


                        <!-- NO -->

                        <td>

                            <?= $no++ ?>

                        </td>


                        <!-- TANGGAL -->

                        <td>

                            <?= htmlspecialchars(
                                $x['tanggal']
                            ) ?>

                        </td>


                        <!-- SUMBER LINK -->

                        <td style="min-width: 165px;">

                            <?php
                            $sumberLink = $x['sumber_link'] ?? '';
                            if ($sumberLink === 'wa_admin'):
                            ?>
                                <span class="badge bg-success">
                                    WhatsApp Admin
                                </span>
                            <?php elseif ($sumberLink === 'lokasi_universitas'): ?>
                                <span class="badge bg-primary">
                                    Lokasi Universitas
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>

                        </td>


                        <!-- NAMA -->

                        <td>

                            <a class="visitor-name-link" href="detail_pengunjung.php?id=<?= (int)$x['id_kunjungan'] ?>">
                                <?= htmlspecialchars($x['nama']) ?>
                            </a>

                        </td>


                        <!-- STATUS PENGUNJUNG -->

                        <td>

                            <?php

                            $statusPengunjung =
                                $x['status'] ?? '';

                            if (
                                $statusPengunjung
                                === 'mahasiswa'
                            ) {

                                $statusBadge =
                                    'primary';

                                $statusText =
                                    'Mahasiswa';

                            } elseif (
                                $statusPengunjung
                                === 'alumni'
                            ) {

                                $statusBadge =
                                    'success';

                                $statusText =
                                    'Alumni';

                            } else {

                                $statusBadge =
                                    'secondary';

                                $statusText =
                                    'Masyarakat';

                            }

                            ?>

                            <span
                                class="badge
                                       bg-<?= $statusBadge ?>"
                            >

                                <?= $statusText ?>

                            </span>

                        </td>


                        <!-- PRODI -->

                        <td>

                            <?= !empty(
                                $x['prodi']
                            )
                                ? htmlspecialchars(
                                    $x['prodi']
                                )
                                : '-'
                            ?>

                        </td>


                        <!-- LAYANAN -->

                        <td>

                            <?php if (
                                !empty(
                                    $x['layanan']
                                )
                            ): ?>

                                <div class="fw-semibold">

                                    <?= htmlspecialchars(
                                        $x['layanan']
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $x['sub_layanan']
                                )
                            ): ?>

                                <small
                                    class="text-muted"
                                >

                                    <?= htmlspecialchars(
                                        $x['sub_layanan']
                                    ) ?>

                                </small>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $x['lainnya']
                                )
                            ): ?>

                                <div>

                                    <small
                                        class="text-muted"
                                    >

                                        Lainnya:
                                        <?= htmlspecialchars(
                                            $x['lainnya']
                                        ) ?>

                                    </small>

                                </div>

                            <?php endif; ?>

                        </td>


                        <!-- STATUS LAYANAN -->

                        <td>

                            <?php if (
                                $statusLayanan
                                === 'selesai'
                            ): ?>

                                <span
                                    class="badge bg-success"
                                >
                                    Selesai
                                </span>


                            <?php elseif (
                                $statusLayanan
                                === 'diproses'
                            ): ?>

                                <span
                                    class="badge
                                           bg-warning
                                           text-dark"
                                >
                                    Diproses
                                </span>


                            <?php else: ?>

                                <span
                                    class="badge
                                           bg-primary"
                                >
                                    New
                                </span>

                            <?php endif; ?>

                            <?php if ($statusLayanan === 'belum' && (int)($x['hari_menunggu'] ?? 0) >= 1): ?>
                                <div class="text-danger small mt-1">Belum ditangani <?= (int)$x['hari_menunggu'] ?> hari</div>
                            <?php endif; ?>

                        </td>


                        <!-- AKSI -->

                        <td style="min-width: 230px;">

                            <?php
                            $aksi = trim(
                                $x['aksi_terakhir'] ?? ''
                            );

                            $aksiRingkas = $aksi;

                            if (function_exists('mb_strimwidth')) {
                                $aksiRingkas = mb_strimwidth(
                                    $aksi,
                                    0,
                                    85,
                                    '…',
                                    'UTF-8'
                                );
                            } elseif (strlen($aksi) > 85) {
                                $aksiRingkas = substr($aksi, 0, 82) . '…';
                            }
                            ?>

                            <?php if ($aksi !== ''): ?>

                                <div
                                    class="small mb-2"
                                    style="
                                        white-space: normal;
                                        line-height: 1.5;
                                    "
                                >
                                    📝 <?= nl2br(
                                        htmlspecialchars(
                                            $aksiRingkas,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    ) ?>
                                </div>

                            <?php else: ?>

                                <div class="text-muted small mb-2">
                                    Belum ada aksi
                                </div>

                            <?php endif; ?>

                            <a
                                href="catatan.php?id=<?= (int)$x['id_kunjungan'] ?>"
                                class="btn btn-sm btn-outline-primary"
                            >
                                📝 Lihat Riwayat
                            </a>

                        </td>


                        <!-- PETUGAS -->

                        <td>

                            <?= htmlspecialchars(
                                $x['petugas']
                                ?? '-'
                            ) ?>

                        </td>


                        <!-- DETAIL -->

                        <td class="sticky-detail">

                            <a
                                href="detail_pengunjung.php?id=<?= (int)$x['id_kunjungan'] ?>"
                                class="btn btn-sm btn-primary"
                            >

                                Detail

                            </a>

                        </td>


                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="11"
                        class="text-center
                               py-4
                               text-muted"
                    >

                        Tidak ada data ditemukan.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>


    <!-- =====================================================
         PAGINATION
    ====================================================== -->

    <?php if (
        $totalPages > 1
    ): ?>


        <div
            class="d-flex
                   justify-content-between
                   align-items-center
                   mt-3
                   flex-wrap
                   gap-2"
        >


            <div class="pagination-info">

                Halaman

                <b>
                    <?= $page ?>
                </b>

                dari

                <b>
                    <?= $totalPages ?>
                </b>

            </div>


            <nav>

                <ul
                    class="pagination
                           pagination-sm
                           mb-0"
                >


                    <!-- SEBELUMNYA -->

                    <li
                        class="page-item
                        <?= $page <= 1
                            ? 'disabled'
                            : '' ?>"
                    >

                        <a
                            class="page-link"
                            href="<?= $page > 1
                                ? pageUrl(
                                    $page - 1,
                                    $params
                                )
                                : '#' ?>"
                        >

                            ‹

                        </a>

                    </li>


                    <?php

                    $startPage =
                        max(
                            1,
                            $page - 2
                        );

                    $endPage =
                        min(
                            $totalPages,
                            $page + 2
                        );

                    for (
                        $i = $startPage;
                        $i <= $endPage;
                        $i++
                    ):

                    ?>

                        <li
                            class="page-item
                            <?= $i === $page
                                ? 'active'
                                : '' ?>"
                        >

                            <a
                                class="page-link"
                                href="<?= pageUrl(
                                    $i,
                                    $params
                                ) ?>"
                            >

                                <?= $i ?>

                            </a>

                        </li>

                    <?php endfor; ?>


                    <!-- BERIKUTNYA -->

                    <li
                        class="page-item
                        <?= $page >= $totalPages
                            ? 'disabled'
                            : '' ?>"
                    >

                        <a
                            class="page-link"
                            href="<?= $page < $totalPages
                                ? pageUrl(
                                    $page + 1,
                                    $params
                                )
                                : '#' ?>"
                        >

                            ›

                        </a>

                    </li>


                </ul>

            </nav>


        </div>


    <?php endif; ?>


</div>


<?php require 'footer.php'; ?>

<!-- =========================================================
     MODAL KONFIRMASI EXCEL
========================================================= -->

<div id="modalKonfirmasiExcel" class="excel-confirm-overlay" aria-hidden="true">

    <div class="excel-confirm-box" role="dialog" aria-modal="true" aria-labelledby="judulKonfirmasiExcel">

        <div class="excel-confirm-icon">📥</div>

        <h4 id="judulKonfirmasiExcel">Konfirmasi Export Excel</h4>

        <p>
            Yakin ingin mengekspor data pengunjung ke Excel?
            <br>
            Data yang diekspor akan mengikuti filter yang sedang aktif.
        </p>

        <div class="excel-confirm-actions">

            <button type="button" id="btnBatalExcel" class="btn btn-outline-secondary">
                Batal
            </button>

            <button type="button" id="btnLanjutExcel" class="btn btn-success">
                Ya, Export Excel
            </button>

        </div>

    </div>

</div>

<style>
.excel-confirm-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, .55);
}

.excel-confirm-overlay.show {
    display: flex !important;
}

.excel-confirm-box {
    width: 100%;
    max-width: 430px;
    background: #fff;
    border-radius: 16px;
    padding: 28px;
    text-align: center;
    box-shadow: 0 18px 45px rgba(0,0,0,.25);
    animation: excelModalIn .18s ease-out;
}

.excel-confirm-icon {
    width: 62px;
    height: 62px;
    margin: 0 auto 14px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9f7ef;
    font-size: 30px;
}

.excel-confirm-box h4 {
    margin: 0 0 10px;
    font-weight: 700;
}

.excel-confirm-box p {
    margin: 0;
    color: #6c757d;
    line-height: 1.6;
}

.excel-confirm-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 22px;
}

.excel-confirm-actions .btn {
    min-width: 145px;
}

@keyframes excelModalIn {
    from { opacity: 0; transform: translateY(-8px) scale(.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 480px) {
    .excel-confirm-box {
        padding: 22px 18px;
    }

    .excel-confirm-actions {
        flex-direction: column-reverse;
    }

    .excel-confirm-actions .btn {
        width: 100%;
        min-width: 0;
    }
}
</style>

<script>
(function () {
    const initExcelConfirm = function () {
        const btnExcel = document.getElementById('btnExcel');
        const modal = document.getElementById('modalKonfirmasiExcel');
        const btnBatal = document.getElementById('btnBatalExcel');
        const btnLanjut = document.getElementById('btnLanjutExcel');

        if (!btnExcel || !modal || !btnBatal || !btnLanjut) return;

        const urlExportExcel = 'export_excel.php?<?= http_build_query($_GET) ?>';

        function bukaModal() {
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function tutupModal() {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        btnExcel.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            bukaModal();
        });

        btnBatal.addEventListener('click', function () {
            tutupModal();
        });

        btnLanjut.addEventListener('click', function () {
            window.location.href = urlExportExcel;
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                tutupModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('show')) {
                tutupModal();
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExcelConfirm);
    } else {
        initExcelConfirm();
    }
})();
</script>
