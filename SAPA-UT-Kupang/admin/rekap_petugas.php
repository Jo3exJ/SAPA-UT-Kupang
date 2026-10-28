<?php

date_default_timezone_set('Asia/Makassar');

$title = 'Rekap Petugas';
require 'header.php';


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
| VALIDASI TANGGAL
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
| DAFTAR PETUGAS
|--------------------------------------------------------------------------
*/

$qAdmin = mysqli_query(
    $conn,
    "
    SELECT
        id_admin,
        nama,
        username,
        aktif
    FROM admins
    ORDER BY nama ASC
    "
);

if (!$qAdmin) {

    die(
        'Gagal mengambil data petugas: '
        . mysqli_error($conn)
    );

}

$daftarPetugas = [];

while ($admin = mysqli_fetch_assoc($qAdmin)) {

    $daftarPetugas[] = $admin;

}


/*
|--------------------------------------------------------------------------
| KONDISI FILTER PETUGAS
|--------------------------------------------------------------------------
*/

$wherePetugas = '';

if ($petugasFilter > 0) {

    $wherePetugas =
        "AND k.id_petugas = $petugasFilter";

}


/*
|--------------------------------------------------------------------------
| REKAP PETUGAS
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
        'Query rekap petugas gagal: '
        . mysqli_error($conn)
    );

}


$rekap = [];


/*
|--------------------------------------------------------------------------
| TOTAL REKAP
|--------------------------------------------------------------------------
*/

$totalKasus = 0;
$totalBaru = 0;
$totalDiproses = 0;
$totalSelesai = 0;


while ($row = mysqli_fetch_assoc($q)) {

    $row['total_kasus'] =
        (int) ($row['total_kasus'] ?? 0);

    $row['kasus_baru'] =
        (int) ($row['kasus_baru'] ?? 0);

    $row['kasus_diproses'] =
        (int) ($row['kasus_diproses'] ?? 0);

    $row['kasus_selesai'] =
        (int) ($row['kasus_selesai'] ?? 0);


    $rekap[] = $row;


    $totalKasus +=
        $row['total_kasus'];

    $totalBaru +=
        $row['kasus_baru'];

    $totalDiproses +=
        $row['kasus_diproses'];

    $totalSelesai +=
        $row['kasus_selesai'];

}

?>


<style>

/* =========================================================
   HEADER
========================================================= */

.rekap-header {
    margin-bottom: 22px;
}

.rekap-header h2 {
    margin-bottom: 5px;
    color: #073b82;
}


/* =========================================================
   FILTER
========================================================= */

.rekap-filter {
    background: #ffffff;
    border: 1px solid #e5eaf0;
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 20px;
    box-shadow: 0 3px 15px rgba(0,0,0,.05);
}


/* =========================================================
   STATISTIK
========================================================= */

.rekap-stat {
    background: #ffffff;
    border: 1px solid #e5eaf0;
    border-radius: 14px;
    padding: 20px;
    height: 100%;
    box-shadow: 0 3px 15px rgba(0,0,0,.05);
}

.rekap-stat small {
    color: #718096;
    font-size: 12px;
}

.rekap-stat h3 {
    margin: 6px 0 0;
    color: #073b82;
    font-weight: 700;
}


/* =========================================================
   TABLE
========================================================= */

.rekap-card {
    background: #ffffff;
    border: 1px solid #e5eaf0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0,0,0,.05);
}

.rekap-card-header {
    padding: 18px;
    border-bottom: 1px solid #edf0f4;
}

.rekap-table {
    margin-bottom: 0;
}

.rekap-table th {
    background: #f7f9fc;
    font-size: 12px;
    white-space: nowrap;
}

.rekap-table td {
    font-size: 13px;
    vertical-align: middle;
}


/* =========================================================
   BADGE
========================================================= */

.rekap-badge {
    display: inline-flex;
    min-width: 35px;
    justify-content: center;
    padding: 5px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}


/* =========================================================
   MODAL KONFIRMASI EXCEL
========================================================= */

.rekap-confirm-overlay {
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

.rekap-confirm-overlay.show {
    display: flex;
}

.rekap-confirm-box {
    width: 100%;
    max-width: 430px;
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 18px 50px rgba(0, 0, 0, .18);
    border: 1px solid #e5eaf0;
    animation: rekapConfirmIn .16s ease-out;
}

.rekap-confirm-icon {
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

.rekap-confirm-title {
    margin: 0 0 7px;
    font-size: 19px;
    font-weight: 700;
    color: #17324d;
}

.rekap-confirm-text {
    margin: 0;
    color: #718096;
    line-height: 1.55;
    font-size: 14px;
}

.rekap-confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 20px;
}

@keyframes rekapConfirmIn {
    from {
        opacity: 0;
        transform: translateY(8px) scale(.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.rekap-baru {
    background: #fff3cd;
    color: #856404;
}

.rekap-proses {
    background: #cff4fc;
    color: #055160;
}

.rekap-selesai {
    background: #d1e7dd;
    color: #0f5132;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

    .rekap-table {
        min-width: 800px;
    }

    .rekap-header h2 {
        font-size: 22px;
    }

}

</style>


<!-- =========================================================
     HEADER
========================================================= -->

<div class="rekap-header">

    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">

        <div>

            <h2>
                👤 Rekap Petugas
            </h2>

            <p class="text-muted mb-0">
                Rekap jumlah kasus yang ditangani oleh setiap petugas.
            </p>

        </div>


        <div>

            <button
                type="button"
                class="btn btn-success"
                onclick="bukaKonfirmasiRekapExcel()"
            >
                📥 Export Excel
            </button>

        </div>

    </div>

</div>


<!-- =========================================================
     FILTER
========================================================= -->

<div class="rekap-filter">

    <form method="get">

        <div class="row g-3 align-items-end">


            <!-- TANGGAL AWAL -->

            <div class="col-md-3">

                <label class="form-label fw-semibold">
                    Tanggal Awal
                </label>

                <input
                    type="date"
                    name="tanggal_awal"
                    class="form-control"
                    value="<?= htmlspecialchars($tanggalAwal) ?>"
                >

            </div>


            <!-- TANGGAL AKHIR -->

            <div class="col-md-3">

                <label class="form-label fw-semibold">
                    Tanggal Akhir
                </label>

                <input
                    type="date"
                    name="tanggal_akhir"
                    class="form-control"
                    value="<?= htmlspecialchars($tanggalAkhir) ?>"
                >

            </div>


            <!-- PETUGAS -->

            <div class="col-md-3">

                <label class="form-label fw-semibold">
                    Petugas
                </label>

                <select
                    name="petugas"
                    class="form-select"
                >

                    <option value="0">
                        Semua Petugas
                    </option>


                    <?php foreach (
                        $daftarPetugas
                        as $admin
                    ): ?>

                        <option
                            value="<?= (int)$admin['id_admin'] ?>"
                            <?= $petugasFilter === (int)$admin['id_admin']
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars(
                                $admin['nama']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- BUTTON -->

            <div class="col-md-3">

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        🔎 Tampilkan
                    </button>


                    <a
                        href="rekap_petugas.php"
                        class="btn btn-outline-secondary"
                    >
                        Reset
                    </a>

                </div>

            </div>


        </div>

    </form>

</div>


<!-- =========================================================
     PERIODE
========================================================= -->

<div class="alert alert-light border">

    <strong>
        Periode:
    </strong>

    <?= date(
        'd-m-Y',
        strtotime($tanggalAwal)
    ) ?>

    sampai

    <?= date(
        'd-m-Y',
        strtotime($tanggalAkhir)
    ) ?>

</div>


<!-- =========================================================
     STATISTIK
========================================================= -->

<div class="row g-3 mb-4">


    <div class="col-6 col-xl-3">

        <div class="rekap-stat">

            <small>
                TOTAL KASUS
            </small>

            <h3>
                <?= $totalKasus ?>
            </h3>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="rekap-stat">

            <small>
                TIKET BARU
            </small>

            <h3>
                <?= $totalBaru ?>
            </h3>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="rekap-stat">

            <small>
                SEDANG DIPROSES
            </small>

            <h3>
                <?= $totalDiproses ?>
            </h3>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="rekap-stat">

            <small>
                SELESAI
            </small>

            <h3>
                <?= $totalSelesai ?>
            </h3>

        </div>

    </div>


</div>


<!-- =========================================================
     TABEL REKAP
========================================================= -->

<div class="rekap-card">


    <div class="rekap-card-header">

        <div>

            <strong>
                Rekap Kasus Petugas
            </strong>

            <br>

            <small class="text-muted">

                Data berdasarkan periode
                yang dipilih.

            </small>

        </div>

    </div>


    <div class="table-responsive">

        <table class="table table-hover align-middle rekap-table">

            <thead>

                <tr>

                    <th class="text-center">
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

                    <th class="text-center">
                        Total Kasus
                    </th>

                    <th class="text-center">
                        Baru
                    </th>

                    <th class="text-center">
                        Diproses
                    </th>

                    <th class="text-center">
                        Selesai
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if (empty($rekap)): ?>

                    <tr>

                        <td
                            colspan="8"
                            class="text-center py-5"
                        >

                            Belum ada data petugas.

                        </td>

                    </tr>

                <?php else: ?>


                    <?php $no = 1; ?>


                    <?php foreach (
                        $rekap
                        as $row
                    ): ?>


                        <tr>


                            <!-- NO -->

                            <td class="text-center">

                                <?= $no++ ?>

                            </td>


                            <!-- NAMA -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row['nama']
                                    ) ?>

                                </strong>

                            </td>


                            <!-- USERNAME -->

                            <td>

                                @<?= htmlspecialchars(
                                    $row['username']
                                ) ?>

                            </td>


                            <!-- STATUS AKUN -->

                            <td>

                                <?php if (
                                    (int)$row['aktif'] === 1
                                ): ?>

                                    <span
                                        class="badge bg-success"
                                    >
                                        Aktif
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="badge bg-secondary"
                                    >
                                        Nonaktif
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- TOTAL -->

                            <td class="text-center">

                                <strong>
                                    <?= $row['total_kasus'] ?>
                                </strong>

                            </td>


                            <!-- BARU -->

                            <td class="text-center">

                                <span
                                    class="
                                        rekap-badge
                                        rekap-baru
                                    "
                                >

                                    <?= $row['kasus_baru'] ?>

                                </span>

                            </td>


                            <!-- DIPROSES -->

                            <td class="text-center">

                                <span
                                    class="
                                        rekap-badge
                                        rekap-proses
                                    "
                                >

                                    <?= $row['kasus_diproses'] ?>

                                </span>

                            </td>


                            <!-- SELESAI -->

                            <td class="text-center">

                                <span
                                    class="
                                        rekap-badge
                                        rekap-selesai
                                    "
                                >

                                    <?= $row['kasus_selesai'] ?>

                                </span>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>


<!-- =========================================================
     MODAL KONFIRMASI EXCEL
========================================================= -->

<div
    id="rekapConfirmOverlay"
    class="rekap-confirm-overlay"
    aria-hidden="true"
>

    <div
        class="rekap-confirm-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rekapConfirmTitle"
    >

        <div class="rekap-confirm-icon">
            📥
        </div>

        <h3 class="rekap-confirm-title" id="rekapConfirmTitle">
            Konfirmasi Export Excel
        </h3>

        <p class="rekap-confirm-text">
            Yakin ingin mengekspor rekap petugas ke Excel? Data yang diekspor akan mengikuti periode dan petugas yang sedang dipilih.
        </p>

        <div class="rekap-confirm-actions">

            <button
                type="button"
                class="btn btn-outline-secondary"
                onclick="tutupKonfirmasiRekapExcel()"
            >
                Batal
            </button>

            <button
                type="button"
                class="btn btn-success"
                onclick="lanjutkanExportRekapExcel()"
            >
                Ya, Export Excel
            </button>

        </div>

    </div>

</div>


<script>

function bukaKonfirmasiRekapExcel()
{
    const overlay = document.getElementById('rekapConfirmOverlay');

    if (!overlay) return;

    overlay.classList.add('show');
    overlay.setAttribute('aria-hidden', 'false');
}

function tutupKonfirmasiRekapExcel()
{
    const overlay = document.getElementById('rekapConfirmOverlay');

    if (!overlay) return;

    overlay.classList.remove('show');
    overlay.setAttribute('aria-hidden', 'true');
}

function lanjutkanExportRekapExcel()
{
    window.location.href = 'export/export_rekap_petugas.php?<?= http_build_query($_GET) ?>';
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        tutupKonfirmasiRekapExcel();
    }
});

document.getElementById('rekapConfirmOverlay')?.addEventListener('click', function(event) {
    if (event.target === this) {
        tutupKonfirmasiRekapExcel();
    }
});

</script>
<?php require 'footer.php'; ?>