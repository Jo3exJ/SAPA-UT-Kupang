<?php
$title = 'Catatan / Riwayat Aksi';
require 'header.php';

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$idKunjungan = (int)($_GET['id'] ?? 0);
$cari = trim($_GET['q'] ?? '');
$periode = $_GET['periode'] ?? '';
$awal = $_GET['awal'] ?? '';
$akhir = $_GET['akhir'] ?? '';
$idPetugas = (int)($_GET['petugas'] ?? 0);

$where = [];

if ($idKunjungan > 0) {
    $where[] = 'k.id_kunjungan = ' . $idKunjungan;
}

if ($idPetugas > 0 && $idKunjungan <= 0) {
    $where[] = 'ct.id_admin = ' . $idPetugas;
}

if ($cari !== '') {
    $esc = mysqli_real_escape_string($conn, $cari);

    $where[] = "(
        k.nama LIKE '%$esc%'
        OR k.nim LIKE '%$esc%'
        OR k.telepon LIKE '%$esc%'
        OR ct.catatan LIKE '%$esc%'
        OR a.nama LIKE '%$esc%'
    )";
}

/*
|--------------------------------------------------------------------------
| FILTER WAKTU
|--------------------------------------------------------------------------
|
| Menggunakan waktu catatan tindakan (ct.created_at), bukan tanggal
| pengunjung, supaya yang difilter benar-benar waktu aksi petugas.
|
*/

if ($periode === 'hari_ini') {
    $where[] = "DATE(ct.created_at) = CURDATE()";
}

if ($periode === 'minggu_ini') {
    $where[] = "
        YEARWEEK(DATE(ct.created_at), 1)
        = YEARWEEK(CURDATE(), 1)
    ";
}

if ($periode === 'bulan_ini') {
    $where[] = "
        YEAR(ct.created_at) = YEAR(CURDATE())
        AND MONTH(ct.created_at) = MONTH(CURDATE())
    ";
}

if ($periode === 'tahun_ini') {
    $where[] = "YEAR(ct.created_at) = YEAR(CURDATE())";
}

if (
    $periode === 'custom'
    && $awal !== ''
    && $akhir !== ''
) {
    $awalEsc = mysqli_real_escape_string($conn, $awal);
    $akhirEsc = mysqli_real_escape_string($conn, $akhir);

    $where[] = "
        DATE(ct.created_at) BETWEEN '$awalEsc' AND '$akhirEsc'
    ";
}

$whereSQL = '';

if (!empty($where)) {
    $whereSQL = ' WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| DATA RIWAYAT AKSI
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        ct.id_catatan,
        ct.id_kunjungan,
        ct.status_dari,
        ct.status_ke,
        ct.catatan,
        ct.created_at,

        k.nama AS nama_pengunjung,
        k.nim,
        k.telepon,
        k.status_layanan,

        a.nama AS nama_petugas

    FROM catatan_tindakan ct

    INNER JOIN kunjungan k
        ON k.id_kunjungan = ct.id_kunjungan

    LEFT JOIN admins a
        ON a.id_admin = ct.id_admin

    $whereSQL

    ORDER BY
        ct.created_at DESC,
        ct.id_catatan DESC
";

$data = mysqli_query($conn, $sql);

if (!$data) {
    die('Query catatan gagal: ' . mysqli_error($conn));
}

/*
|--------------------------------------------------------------------------
| DAFTAR PETUGAS UNTUK FILTER
|--------------------------------------------------------------------------
*/

$petugasQuery = mysqli_query(
    $conn,
    "
    SELECT id_admin, nama
    FROM admins
    ORDER BY nama ASC
    "
);

if (!$petugasQuery) {
    die('Query petugas gagal: ' . mysqli_error($conn));
}

/*
|--------------------------------------------------------------------------
| FUNGSI TAMPILAN
|--------------------------------------------------------------------------
*/

function eCatatan($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function statusLabelCatatan($status)
{
    switch ($status) {
        case 'belum':
            return 'Baru';
        case 'diproses':
            return 'Sedang Diproses';
        case 'selesai':
            return 'Selesai';
        default:
            return ucfirst((string)$status);
    }
}

function statusClassCatatan($status)
{
    switch ($status) {
        case 'belum':
            return 'status-baru';
        case 'diproses':
            return 'status-diproses';
        case 'selesai':
            return 'status-selesai';
        default:
            return 'status-default';
    }
}

function formatWaktuCatatan($datetime)
{
    if (empty($datetime)) {
        return '-';
    }

    $time = strtotime($datetime);

    if (!$time) {
        return $datetime;
    }

    return date('d/m/Y H:i', $time);
}

$jumlahCatatan = mysqli_num_rows($data);

?>

<style>
/* =========================================================
   HEADER
========================================================= */

.catatan-header {
    margin-bottom: 20px;
}

.catatan-header h2 {
    margin-bottom: 4px;
    font-weight: 700;
}

/* =========================================================
   FILTER
========================================================= */

.catatan-filter {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 20px;
    box-shadow: 0 3px 15px rgba(0,0,0,.05);
}

.tanggal-catatan-custom {
    display: none;
}

.tanggal-catatan-custom.show {
    display: block;
}

/* =========================================================
   TIMELINE
========================================================= */

.riwayat-card {
    background: #fff;
    border: 1px solid #edf0f4;
    border-radius: 14px;
    box-shadow: 0 3px 15px rgba(0,0,0,.05);
    overflow: hidden;
}

.riwayat-card-header {
    padding: 18px;
    border-bottom: 1px solid #edf0f4;
}

.riwayat-body {
    padding: 20px;
}

.timeline {
    position: relative;
    margin: 0;
    padding: 0 0 0 38px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 14px;
    top: 8px;
    bottom: 8px;
    width: 2px;
    background: #dfe7f2;
}

.timeline-item {
    position: relative;
    margin-bottom: 22px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-dot {
    position: absolute;
    left: -32px;
    top: 4px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #004b93;
    background: #004b93;
}

.timeline-item.status-selesai .timeline-dot {
    background: #198754;
    box-shadow: 0 0 0 2px #198754;
}

.timeline-item.status-diproses .timeline-dot {
    background: #f0ad00;
    box-shadow: 0 0 0 2px #f0ad00;
}

.timeline-item.status-baru .timeline-dot {
    background: #0d6efd;
    box-shadow: 0 0 0 2px #0d6efd;
}

.timeline-panel {
    border: 1px solid #e7edf5;
    border-radius: 12px;
    padding: 16px;
    background: #fbfcfe;
}

.timeline-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
}

.timeline-time {
    font-size: 12px;
    color: #718096;
}

.timeline-meta {
    margin-top: 8px;
    font-size: 12px;
    color: #718096;
}

.timeline-note {
    margin-top: 12px;
    background: #fff;
    border: 1px solid #e8edf4;
    border-radius: 10px;
    padding: 12px;
    line-height: 1.6;
    white-space: normal;
}

.status-badge-catatan {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge-catatan.status-baru {
    background: #eaf3ff;
    color: #0d6efd;
}

.status-badge-catatan.status-diproses {
    background: #fff4d6;
    color: #8a6200;
}

.status-badge-catatan.status-selesai {
    background: #e9f7ef;
    color: #198754;
}

.status-badge-catatan.status-default {
    background: #eef1f5;
    color: #5f6b7a;
}

.visitor-box {
    background: #f7f9fc;
    border: 1px solid #e6ebf2;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 18px;
}

.visitor-name {
    font-size: 17px;
    font-weight: 700;
    color: #17324d;
}

.visitor-meta {
    font-size: 12px;
    color: #718096;
    margin-top: 4px;
}

.empty-state {
    text-align: center;
    padding: 55px 20px;
    color: #718096;
}

.empty-icon {
    font-size: 42px;
    margin-bottom: 10px;
}

/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 767px) {
    .catatan-header h2 {
        font-size: 22px;
    }

    .riwayat-body {
        padding: 14px;
    }

    .timeline {
        padding-left: 31px;
    }

    .timeline::before {
        left: 11px;
    }

    .timeline-dot {
        left: -27px;
        width: 13px;
        height: 13px;
    }
}
</style>

<!-- =========================================================
     HEADER
========================================================= -->

<div class="catatan-header">

    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">

        <div>
            <h2>📝 Riwayat Aksi Petugas</h2>

            <p class="text-muted mb-0">
                Riwayat perubahan status dan catatan tindakan pada setiap layanan.
            </p>
        </div>

        <?php if ($idKunjungan > 0): ?>
            <a href="catatan.php" class="btn btn-outline-secondary">
                ← Semua Riwayat
            </a>
        <?php endif; ?>

    </div>

</div>

<!-- =========================================================
     FILTER PENCARIAN
========================================================= -->

<?php if ($idKunjungan <= 0): ?>

<div class="catatan-filter">

    <form method="get">

        <div class="row g-2">

            <div class="col-lg-5 col-md-7">
                <label class="form-label mb-1">Cari</label>
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Nama, NIM, HP, petugas, atau catatan..."
                    value="<?= eCatatan($cari) ?>"
                >
            </div>

            <div class="col-lg-3 col-md-5">
                <label class="form-label mb-1">Waktu</label>
                <select
                    name="periode"
                    id="periodeCatatan"
                    class="form-select"
                    onchange="toggleTanggalCatatan()"
                >
                    <option value="">Semua Waktu</option>
                    <option value="hari_ini" <?= $periode === 'hari_ini' ? 'selected' : '' ?>>Hari Ini</option>
                    <option value="minggu_ini" <?= $periode === 'minggu_ini' ? 'selected' : '' ?>>Minggu Ini</option>
                    <option value="bulan_ini" <?= $periode === 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
                    <option value="tahun_ini" <?= $periode === 'tahun_ini' ? 'selected' : '' ?>>Tahun Ini</option>
                    <option value="custom" <?= $periode === 'custom' ? 'selected' : '' ?>>Custom</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-5">
                <label class="form-label mb-1">Petugas</label>
                <select
                    name="petugas"
                    class="form-select"
                >
                    <option value="0">Semua Petugas</option>
                    <?php while ($p = mysqli_fetch_assoc($petugasQuery)): ?>
                        <option
                            value="<?= (int)$p['id_admin'] ?>"
                            <?= $idPetugas === (int)$p['id_admin'] ? 'selected' : '' ?>
                        >
                            <?= eCatatan($p['nama']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-lg-2 col-md-6 tanggal-catatan-custom">
                <label class="form-label mb-1">Dari</label>
                <input
                    type="date"
                    name="awal"
                    class="form-control"
                    value="<?= eCatatan($awal) ?>"
                >
            </div>

            <div class="col-lg-2 col-md-6 tanggal-catatan-custom">
                <label class="form-label mb-1">Sampai</label>
                <input
                    type="date"
                    name="akhir"
                    class="form-control"
                    value="<?= eCatatan($akhir) ?>"
                >
            </div>

            <div class="col-lg-2 col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    🔎 Filter
                </button>
            </div>

            <div class="col-lg-2 col-md-6 d-flex align-items-end">
                <a href="catatan.php<?= $idKunjungan > 0 ? '?id=' . (int)$idKunjungan : '' ?>" class="btn btn-outline-secondary w-100">
                    Reset
                </a>
            </div>

        </div>

    </form>

</div>

<?php endif; ?>

<!-- =========================================================
     RIWAYAT
========================================================= -->

<div class="riwayat-card">

    <div class="riwayat-card-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>
                    <?= $idKunjungan > 0 ? 'Detail Riwayat Layanan' : 'Semua Riwayat Aksi' ?>
                </strong>
                <div class="text-muted small mt-1">
                    <?= $jumlahCatatan ?> catatan ditemukan
                </div>
            </div>
        </div>

    </div>

    <div class="riwayat-body">

        <?php if ($jumlahCatatan <= 0): ?>

            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <div class="fw-semibold mb-1">Belum ada riwayat aksi</div>
                <div class="small">
                    Catatan yang dibuat melalui Tindak Lanjut akan muncul di sini.
                </div>
            </div>

        <?php else: ?>

            <?php
            $lastVisitorId = null;
            ?>

            <div class="timeline">

                <?php while ($x = mysqli_fetch_assoc($data)): ?>

                    <?php
                    $statusKe = $x['status_ke'] ?? '';
                    $statusDari = $x['status_dari'] ?? '';
                    $kelasStatus = statusClassCatatan($statusKe);
                    ?>

                    <?php if ($idKunjungan <= 0 && $lastVisitorId !== (int)$x['id_kunjungan']): ?>

                        <?php $lastVisitorId = (int)$x['id_kunjungan']; ?>

                        <div class="visitor-box">
                            <div class="visitor-name">
                                <?= eCatatan($x['nama_pengunjung']) ?>
                            </div>

                            <div class="visitor-meta">
                                <?php if (!empty($x['nim'])): ?>
                                    NIM: <?= eCatatan($x['nim']) ?>
                                <?php endif; ?>

                                <?php if (!empty($x['telepon'])): ?>
                                    <?= !empty($x['nim']) ? ' • ' : '' ?>
                                    HP: <?= eCatatan($x['telepon']) ?>
                                <?php endif; ?>
                            </div>

                            <div class="mt-2">
                                <a
                                    href="catatan.php?id=<?= (int)$x['id_kunjungan'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Lihat Riwayat Lengkap
                                </a>
                            </div>
                        </div>

                    <?php endif; ?>

                    <div class="timeline-item <?= eCatatan($kelasStatus) ?>">

                        <div class="timeline-dot"></div>

                        <div class="timeline-panel">

                            <div class="timeline-top">

                                <div>
                                    <div class="fw-semibold">
                                        <?= eCatatan(statusLabelCatatan($statusKe)) ?>
                                    </div>

                                    <div class="timeline-time">
                                        <?= eCatatan(formatWaktuCatatan($x['created_at'])) ?>
                                    </div>
                                </div>

                                <span class="status-badge-catatan <?= eCatatan($kelasStatus) ?>">
                                    <?= eCatatan(statusLabelCatatan($statusKe)) ?>
                                </span>

                            </div>

                            <?php if ($statusDari !== ''): ?>
                                <div class="timeline-meta">
                                    Perubahan status:
                                    <strong><?= eCatatan(statusLabelCatatan($statusDari)) ?></strong>
                                    →
                                    <strong><?= eCatatan(statusLabelCatatan($statusKe)) ?></strong>
                                </div>
                            <?php endif; ?>

                            <div class="timeline-meta">
                                Petugas:
                                <strong>
                                    <?= eCatatan($x['nama_petugas'] ?? 'Tidak tercatat') ?>
                                </strong>
                            </div>

                            <?php if (trim((string)($x['catatan'] ?? '')) !== ''): ?>
                                <div class="timeline-note">
                                    <strong>📝 Catatan:</strong>
                                    <div class="mt-1">
                                        <?= nl2br(eCatatan($x['catatan'])) ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="timeline-note text-muted">
                                    Tidak ada catatan tambahan.
                                </div>
                            <?php endif; ?>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php endif; ?>

    </div>

</div>

<script>
function toggleTanggalCatatan() {
    const periode = document.getElementById('periodeCatatan');
    const fields = document.querySelectorAll('.tanggal-catatan-custom');

    if (!periode) return;

    fields.forEach(function(field) {
        if (periode.value === 'custom') {
            field.classList.add('show');
        } else {
            field.classList.remove('show');
        }
    });
}

document.addEventListener('DOMContentLoaded', toggleTanggalCatatan);
</script>

<?php require 'footer.php'; ?>
