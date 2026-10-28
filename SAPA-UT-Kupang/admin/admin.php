<?php

$title = 'Admin & Rekap Petugas';
require 'header.php';


/*
|--------------------------------------------------------------------------
| DATA ADMIN + REKAP KASUS
|--------------------------------------------------------------------------
|
| Mengambil semua admin aktif/nonaktif.
| Rekap dihitung dari tabel kunjungan berdasarkan id_petugas.
|
*/

$q = mysqli_query(
    $conn,
    "
    SELECT
        a.id_admin,
        a.nama,
        a.username,
        a.aktif,
        a.created_at,

        COUNT(k.id_kunjungan) AS total_kasus,

        SUM(
            CASE
                WHEN k.status_layanan = 'belum'
                THEN 1
                ELSE 0
            END
        ) AS kasus_belum,

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

    GROUP BY
        a.id_admin,
        a.nama,
        a.username,
        a.aktif,
        a.created_at

    ORDER BY
        a.id_admin ASC
    "
);


if (!$q) {

    die(
        'Query error: '
        . mysqli_error($conn)
    );

}


/*
|--------------------------------------------------------------------------
| HITUNG TOTAL KESELURUHAN
|--------------------------------------------------------------------------
*/

$totalAdmin = 0;
$totalKasus = 0;
$totalBelum = 0;
$totalDiproses = 0;
$totalSelesai = 0;


/*
|--------------------------------------------------------------------------
| BACA DATA
|--------------------------------------------------------------------------
*/

$dataAdmin = [];

while ($x = mysqli_fetch_assoc($q)) {

    $dataAdmin[] = $x;

    $totalAdmin++;

    $totalKasus +=
        (int) ($x['total_kasus'] ?? 0);

    $totalBelum +=
        (int) ($x['kasus_belum'] ?? 0);

    $totalDiproses +=
        (int) ($x['kasus_diproses'] ?? 0);

    $totalSelesai +=
        (int) ($x['kasus_selesai'] ?? 0);
}

?>


<style>

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.admin-page-header {
    margin-bottom: 22px;
}

.admin-page-header h2 {
    margin-bottom: 4px;
}


/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/

.admin-stat {
    background: #ffffff;
    border-radius: 14px;
    padding: 20px;

    border: 1px solid #edf0f4;

    box-shadow:
        0 3px 15px rgba(0, 0, 0, .06);

    height: 100%;
}

.admin-stat small {
    color: #718096;
    font-size: 12px;
}

.admin-stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #003b7a;
    margin-top: 5px;
}


/*
|--------------------------------------------------------------------------
| CARD TABEL
|--------------------------------------------------------------------------
*/

.admin-table-card {
    background: #ffffff;

    border-radius: 14px;

    border: 1px solid #edf0f4;

    box-shadow:
        0 3px 15px rgba(0, 0, 0, .06);

    overflow: hidden;
}

.admin-table-header {
    padding: 18px;

    border-bottom: 1px solid #edf0f4;
}


/*
|--------------------------------------------------------------------------
| TABEL
|--------------------------------------------------------------------------
*/

.admin-table {
    margin-bottom: 0;
}

.admin-table th {
    background: #f7f9fc;

    font-size: 12px;

    white-space: nowrap;
}

.admin-table td {
    font-size: 13px;

    vertical-align: middle;
}


/*
|--------------------------------------------------------------------------
| NAMA PETUGAS
|--------------------------------------------------------------------------
*/

.petugas-nama {
    font-weight: 600;
    color: #17324d;
}

.petugas-username {
    font-size: 12px;
    color: #7b8794;
}


/*
|--------------------------------------------------------------------------
| BADGE
|--------------------------------------------------------------------------
*/

.badge-status {
    font-size: 11px;
    padding: 5px 8px;
}

.badge-aktif {
    background: #d1e7dd;
    color: #0f5132;
}

.badge-nonaktif {
    background: #f8d7da;
    color: #842029;
}

.badge-belum {
    background: #fff3cd;
    color: #856404;
}

.badge-proses {
    background: #cff4fc;
    color: #055160;
}

.badge-selesai {
    background: #d1e7dd;
    color: #0f5132;
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 768px) {

    .admin-page-header h2 {
        font-size: 22px;
    }

    .admin-table {
        min-width: 850px;
    }

}

</style>



<!-- =========================================================
     HEADER
========================================================= -->

<div class="admin-page-header">

    <div
        class="d-flex
               justify-content-between
               align-items-center
               flex-wrap
               gap-3"
    >

        <div>

            <h2>
                👥 Admin & Rekap Petugas
            </h2>

            <p class="text-muted mb-0">

                Daftar petugas dan jumlah kasus
                yang ditangani.

            </p>

        </div>


        <!-- TOMBOL REKAP PETUGAS -->

        <div>

            <a
                href="rekap_petugas.php"
                class="btn btn-primary"
            >

                📊 Rekap Petugas

            </a>

        </div>

    </div>

</div>


<!-- =========================================================
     STATISTIK KESELURUHAN
========================================================= -->

<div class="row g-3 mb-4">


    <!-- TOTAL ADMIN -->

    <div class="col-6 col-xl-3">

        <div class="admin-stat">

            <small>
                TOTAL PETUGAS
            </small>

            <div class="admin-stat-number">

                <?= $totalAdmin ?>

            </div>

        </div>

    </div>


    <!-- TOTAL KASUS -->

    <div class="col-6 col-xl-3">

        <div class="admin-stat">

            <small>
                TOTAL KASUS
            </small>

            <div class="admin-stat-number">

                <?= $totalKasus ?>

            </div>

        </div>

    </div>


    <!-- DIPROSES -->

    <div class="col-6 col-xl-3">

        <div class="admin-stat">

            <small>
                SEDANG DIPROSES
            </small>

            <div class="admin-stat-number">

                <?= $totalDiproses ?>

            </div>

        </div>

    </div>


    <!-- SELESAI -->

    <div class="col-6 col-xl-3">

        <div class="admin-stat">

            <small>
                SELESAI
            </small>

            <div class="admin-stat-number">

                <?= $totalSelesai ?>

            </div>

        </div>

    </div>


</div>



<!-- =========================================================
     TABEL ADMIN
========================================================= -->

<div class="admin-table-card">


    <!-- HEADER -->

    <div class="admin-table-header">

        <div
            class="d-flex
                   justify-content-between
                   align-items-center
                   flex-wrap
                   gap-2"
        >

            <div>

                <strong>
                    Rekap Kasus Petugas
                </strong>

                <br>

                <small class="text-muted">

                    Jumlah kasus berdasarkan
                    petugas yang menangani.

                </small>

            </div>


            <span class="badge bg-primary">

                <?= $totalAdmin ?>
                Petugas

            </span>

        </div>

    </div>



    <!-- TABEL -->

    <div class="table-responsive">

        <table
            class="table table-hover
                   align-middle admin-table"
        >

            <thead>

                <tr>

                    <th class="text-center">
                        No
                    </th>

                    <th>
                        Petugas
                    </th>

                    <th>
                        Status Akun
                    </th>

                    <th class="text-center">
                        Total Kasus
                    </th>

                    <th class="text-center">
                        Belum
                    </th>

                    <th class="text-center">
                        Diproses
                    </th>

                    <th class="text-center">
                        Selesai
                    </th>

                    <th>
                        Akun Dibuat
                    </th>

                </tr>

            </thead>


            <tbody>


                <?php if (empty($dataAdmin)): ?>

                    <tr>

                        <td
                            colspan="8"
                            class="text-center py-5"
                        >

                            <div
                                class="text-muted"
                            >

                                Belum ada data
                                admin/petugas.

                            </div>

                        </td>

                    </tr>


                <?php else: ?>


                    <?php $no = 1; ?>


                    <?php foreach (
                        $dataAdmin
                        as $x
                    ): ?>


                        <tr>


                            <!-- NO -->

                            <td class="text-center">

                                <?= $no++ ?>

                            </td>


                            <!-- PETUGAS -->

                            <td>

                                <div class="petugas-nama">

                                    <?= htmlspecialchars(
                                        $x['nama']
                                    ) ?>

                                </div>

                                <div class="petugas-username">

                                    @<?= htmlspecialchars(
                                        $x['username']
                                    ) ?>

                                </div>

                            </td>


                            <!-- STATUS AKUN -->

                            <td>

                                <?php if (
                                    (int)$x['aktif'] === 1
                                ): ?>

                                    <span
                                        class="
                                            badge
                                            badge-status
                                            badge-aktif
                                        "
                                    >

                                        Aktif

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="
                                            badge
                                            badge-status
                                            badge-nonaktif
                                        "
                                    >

                                        Nonaktif

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- TOTAL -->

                            <td class="text-center">

                                <strong>

                                    <?= (int)(
                                        $x['total_kasus']
                                        ?? 0
                                    ) ?>

                                </strong>

                            </td>


                            <!-- BELUM -->

                            <td class="text-center">

                                <span
                                    class="
                                        badge
                                        badge-status
                                        badge-belum
                                    "
                                >

                                    <?= (int)(
                                        $x['kasus_belum']
                                        ?? 0
                                    ) ?>

                                </span>

                            </td>


                            <!-- DIPROSES -->

                            <td class="text-center">

                                <span
                                    class="
                                        badge
                                        badge-status
                                        badge-proses
                                    "
                                >

                                    <?= (int)(
                                        $x['kasus_diproses']
                                        ?? 0
                                    ) ?>

                                </span>

                            </td>


                            <!-- SELESAI -->

                            <td class="text-center">

                                <span
                                    class="
                                        badge
                                        badge-status
                                        badge-selesai
                                    "
                                >

                                    <?= (int)(
                                        $x['kasus_selesai']
                                        ?? 0
                                    ) ?>

                                </span>

                            </td>


                            <!-- TANGGAL DIBUAT -->

                            <td>

                                <?= htmlspecialchars(
                                    $x['created_at']
                                    ?? '-'
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>



<?php require 'footer.php'; ?>