<?php

$title = 'Detail';
require 'header.php';


/*
|--------------------------------------------------------------------------
| AMBIL ID
|--------------------------------------------------------------------------
*/

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {

    echo '<div class="alert alert-danger">
            ID data tidak valid.
          </div>';

    require 'footer.php';
    exit;
}


/*
|--------------------------------------------------------------------------
| KUNCI DETAIL PER PETUGAS
|--------------------------------------------------------------------------
| Satu detail hanya boleh dibuka oleh satu akun petugas pada satu waktu.
| Kunci dianggap kedaluwarsa setelah 5 menit tanpa heartbeat.
*/
$adminLoginId = (int)($_SESSION['admin_id'] ?? 0);
$ambilKunci = mysqli_prepare(
    $conn,
    "UPDATE kunjungan
     SET lock_admin_id = ?, lock_at = NOW()
     WHERE id_kunjungan = ?
       AND (
           lock_admin_id IS NULL
           OR lock_at IS NULL
           OR lock_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
           OR lock_admin_id = ?
       )"
);

if ($ambilKunci) {
    mysqli_stmt_bind_param($ambilKunci, 'iii', $adminLoginId, $id, $adminLoginId);
    mysqli_stmt_execute($ambilKunci);
    $berhasilKunci = mysqli_stmt_affected_rows($ambilKunci) > 0;
    mysqli_stmt_close($ambilKunci);

    if (!$berhasilKunci) {
        $cekKunci = mysqli_prepare($conn, "SELECT lock_admin_id, lock_at FROM kunjungan WHERE id_kunjungan = ? LIMIT 1");
        if ($cekKunci) {
            mysqli_stmt_bind_param($cekKunci, 'i', $id);
            mysqli_stmt_execute($cekKunci);
            $kunciData = mysqli_fetch_assoc(mysqli_stmt_get_result($cekKunci));
            mysqli_stmt_close($cekKunci);

            if ($kunciData) {
                $pemilikId = (int)($kunciData['lock_admin_id'] ?? 0);
                $waktuKunci = (string)($kunciData['lock_at'] ?? '');
                $masihAktif = $pemilikId > 0 && $waktuKunci !== '' && strtotime($waktuKunci) >= time() - 300;

                if ($masihAktif && $pemilikId !== $adminLoginId) {
                    $namaPetugas = 'petugas lain';
                    $namaStmt = mysqli_prepare($conn, "SELECT nama FROM admins WHERE id_admin = ? LIMIT 1");
                    if ($namaStmt) {
                        mysqli_stmt_bind_param($namaStmt, 'i', $pemilikId);
                        mysqli_stmt_execute($namaStmt);
                        $namaRow = mysqli_fetch_assoc(mysqli_stmt_get_result($namaStmt));
                        mysqli_stmt_close($namaStmt);
                        if (!empty($namaRow['nama'])) {
                            $namaPetugas = (string)$namaRow['nama'];
                        }
                    }

                    $pesanAlert = 'Data ini sedang dibuka oleh ' . $namaPetugas . '. Silakan coba lagi setelah petugas tersebut menutup detail.';
                    echo '<script>alert(' . json_encode($pesanAlert, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . '); window.location.replace("data_pengunjung.php");</script>';
                    echo '<noscript><div class="alert alert-warning">' . htmlspecialchars($pesanAlert, ENT_QUOTES, 'UTF-8') . ' <a href="data_pengunjung.php">Kembali</a></div></noscript>';
                    exit;
                }
            }
        }
    }
} else {
    echo '<div class="alert alert-danger">Gagal memeriksa kunci detail. Silakan kembali dan coba lagi.</div>';
    require 'footer.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA KUNJUNGAN
|--------------------------------------------------------------------------
*/

$st = mysqli_prepare(
    $conn,
    "
    SELECT
        k.*,
        a.nama AS petugas_proses
    FROM kunjungan k

    LEFT JOIN admins a
        ON a.id_admin = k.id_petugas

    WHERE k.id_kunjungan = ?

    LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $st,
    'i',
    $id
);

mysqli_stmt_execute($st);

$result = mysqli_stmt_get_result($st);

$x = mysqli_fetch_assoc($result);


if (!$x) {

    echo '<div class="alert alert-danger">
            Data pengunjung tidak ditemukan.
          </div>';

    require 'footer.php';
    exit;
}


/*
|--------------------------------------------------------------------------
| DATA ADMIN / PETUGAS
|--------------------------------------------------------------------------
*/

$admins = mysqli_query(
    $conn,
    "
    SELECT
        id_admin,
        nama
    FROM admins
    WHERE aktif = 1
    ORDER BY nama
    "
);


$status = $x['status_layanan'] ?? 'belum';


/*
|--------------------------------------------------------------------------
| RIWAYAT TINDAKAN
|--------------------------------------------------------------------------
|
| Menampilkan seluruh catatan tindakan pada detail pengunjung.
| Urutan dari tindakan paling awal sampai yang terbaru.
|
*/

$riwayatQuery = mysqli_query(
    $conn,
    "
    SELECT
        ct.status_dari,
        ct.status_ke,
        ct.catatan,
        ct.created_at,
        a.nama AS nama_petugas

    FROM catatan_tindakan ct

    LEFT JOIN admins a
        ON a.id_admin = ct.id_admin

    WHERE ct.id_kunjungan = $id

    ORDER BY
        ct.created_at ASC,
        ct.id_catatan ASC
    "
);

if (!$riwayatQuery) {
    die('Query riwayat gagal: ' . mysqli_error($conn));
}

$riwayatTindakan = [];

while ($riwayat = mysqli_fetch_assoc($riwayatQuery)) {
    $riwayatTindakan[] = $riwayat;
}

function labelStatusDetail($status)
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

function classStatusDetail($status)
{
    switch ($status) {
        case 'belum':
            return 'detail-status-baru';
        case 'diproses':
            return 'detail-status-proses';
        case 'selesai':
            return 'detail-status-selesai';
        default:
            return 'detail-status-baru';
    }
}


/*
|--------------------------------------------------------------------------
| DATA KONFIRMASI KE PENGUNJUNG
|--------------------------------------------------------------------------
*/
$sumberLink = $x['sumber_link'] ?? '';
$teleponPengunjung = trim($x['telepon'] ?? '');

$waNumber = preg_replace('/\D+/', '', $teleponPengunjung);

if (strncmp($waNumber, '0', 1) === 0) {
    $waNumber = '62' . substr($waNumber, 1);
}

$isWaSource = ($sumberLink === 'wa_admin');


/*
|--------------------------------------------------------------------------
| PESAN HASIL TINDAK LANJUT
|--------------------------------------------------------------------------
*/

$errorTindak =
    $_SESSION['error_tindak_lanjut'] ?? '';

$successTindak =
    $_SESSION['success_tindak_lanjut'] ?? '';


unset(
    $_SESSION['error_tindak_lanjut'],
    $_SESSION['success_tindak_lanjut']
);

?>




<style>
.detail-progress-box,
.detail-history-box {
    border: 1px solid #e5eaf0;
    border-radius: 12px;
    padding: 16px;
    background: #fbfcff;
}

.detail-progress {
    position: relative;
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 0 18px;
}

.detail-progress-line {
    position: absolute;
    left: 45px;
    right: 45px;
    top: 15px;
    height: 3px;
    background: #dfe7f2;
    z-index: 0;
}

.detail-progress-step {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    color: #8793a4;
    text-align: center;
}

.detail-progress-dot {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9edf3;
    color: #718096;
    font-size: 12px;
    font-weight: 700;
    border: 3px solid #fff;
    box-shadow: 0 0 0 1px #d8e0ea;
}

.detail-progress-step.active {
    color: #004b93;
}

.detail-progress-step.active .detail-progress-dot {
    background: #004b93;
    color: #fff;
    box-shadow: 0 0 0 1px #004b93;
}

.detail-progress-step.done {
    color: #198754;
}

.detail-progress-step.done .detail-progress-dot {
    background: #198754;
    color: #fff;
    box-shadow: 0 0 0 1px #198754;
}

.detail-status-baru {
    background: #eaf3ff;
    color: #0d6efd;
}

.detail-status-proses {
    background: #fff4d6;
    color: #8a6200;
}

.detail-status-selesai {
    background: #e9f7ef;
    color: #198754;
}

.detail-history-box {
    background: #fff;
}

.detail-timeline {
    position: relative;
    padding-left: 26px;
}

.detail-timeline::before {
    content: '';
    position: absolute;
    left: 6px;
    top: 6px;
    bottom: 6px;
    width: 2px;
    background: #dfe7f2;
}

.detail-timeline-item {
    position: relative;
    padding-bottom: 16px;
}

.detail-timeline-item:last-child {
    padding-bottom: 0;
}

.detail-timeline-dot {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #004b93;
}

.detail-timeline-dot.detail-status-baru { background: #0d6efd; box-shadow: 0 0 0 1px #0d6efd; }
.detail-timeline-dot.detail-status-proses { background: #f0ad00; box-shadow: 0 0 0 1px #f0ad00; }
.detail-timeline-dot.detail-status-selesai { background: #198754; box-shadow: 0 0 0 1px #198754; }

.detail-timeline-content {
    border: 1px solid #e7edf5;
    border-radius: 10px;
    padding: 12px;
    background: #fbfcfe;
}

.detail-timeline-note {
    margin-top: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    background: #fff;
    border: 1px solid #e8edf4;
    line-height: 1.55;
}

.detail-primary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-bottom:4px}.detail-primary-grid>div{background:#fbfcff;border:1px solid #e7edf5;border-radius:10px;padding:10px 12px}.detail-primary-grid small{display:block;color:#7b8794;font-size:11px;margin-bottom:3px}.detail-primary-grid strong{display:block;color:#17324d;line-height:1.4}.detail-primary-grid a{text-decoration:none;color:#0d6efd}.detail-more{border:1px solid #e7edf5;border-radius:10px;padding:10px 12px;background:#fbfcfe}.detail-more summary{cursor:pointer;font-weight:600;color:#315c8d}

@media (max-width: 767px) {
    .detail-progress {
        padding: 0 4px;
    }

    .detail-progress-line {
        left: 28px;
        right: 28px;
    }

    .detail-progress-step small {
        font-size: 10px;
    }
}
</style>

<h2 class="mb-4">
    Detail Data Pengunjung
</h2>



<?php if ($errorTindak): ?>

    <div class="alert alert-danger">
        <?= htmlspecialchars($errorTindak) ?>
    </div>

<?php endif; ?>


<?php if ($successTindak): ?>

    <div class="alert alert-success">
        <?= htmlspecialchars($successTindak) ?>
    </div>

<?php endif; ?>



<div class="row g-3">


    <!-- =====================================================
         DATA PENGUNJUNG
    ====================================================== -->

    <div class="col-lg-7">

        <div class="cardbox">

            <h5 class="mb-3">
                Data Pengunjung
            </h5>


            <div class="detail-primary-grid"><div><small>Tanggal</small><strong><?= htmlspecialchars($x['tanggal']??'-') ?></strong></div><div><small>Nama</small><strong><?= htmlspecialchars($x['nama']??'-') ?></strong></div><div><small>Status</small><strong><?= htmlspecialchars(ucfirst($x['status']??'-')) ?></strong></div><div><small>NIM</small><strong><?= htmlspecialchars($x['nim']?:'-') ?></strong></div><div><small>Program Studi</small><strong><?= htmlspecialchars($x['prodi']?:'-') ?></strong></div><div><small>HP</small><?php if(!empty($waNumber)): ?><a href="https://wa.me/<?= htmlspecialchars($waNumber) ?>" target="_blank" rel="noopener noreferrer"><strong><?= htmlspecialchars($teleponPengunjung) ?></strong></a><?php else: ?><strong><?= htmlspecialchars($x['telepon']??'-') ?></strong><?php endif; ?></div><div><small>Layanan</small><strong><?= htmlspecialchars($x['layanan']??'-') ?></strong></div><div><small>Sub Layanan</small><strong><?= htmlspecialchars($x['sub_layanan']??'-') ?></strong></div></div><details class="mt-3 detail-more"><summary>Lihat data tambahan</summary><dl class="row mb-0 mt-3"><dt class="col-sm-4">Pekerjaan</dt><dd class="col-sm-8"><?= htmlspecialchars($x['pekerjaan']??'-') ?></dd><dt class="col-sm-4">Alamat</dt><dd class="col-sm-8"><?= nl2br(htmlspecialchars($x['alamat']??'-')) ?></dd><dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= htmlspecialchars($x['email']?:'-') ?></dd><dt class="col-sm-4">Waktu Input</dt><dd class="col-sm-8"><?= htmlspecialchars($x['created_at']??'-') ?></dd><dt class="col-sm-4">Keterangan Lainnya</dt><dd class="col-sm-8"><?= nl2br(htmlspecialchars($x['lainnya']?:'-')) ?></dd><dt class="col-sm-4">Sumber Link</dt><dd class="col-sm-8"><?php if(($x['sumber_link']??'')==='wa_admin'): ?><span class="badge bg-success">WhatsApp Admin</span><?php elseif(($x['sumber_link']??'')==='lokasi_universitas'): ?><span class="badge bg-primary">Langsung dari Lokasi Universitas</span><?php else: ?><span class="text-muted">-</span><?php endif; ?></dd></dl></details>


            <!-- =====================================================
                 PROGRESS LAYANAN
            ====================================================== -->

            <div class="detail-progress-box">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong>Progress Layanan</strong>
                    <span class="badge <?= htmlspecialchars(classStatusDetail($status)) ?>">
                        <?= htmlspecialchars(labelStatusDetail($status)) ?>
                    </span>
                </div>

                <div class="detail-progress">
                    <div class="detail-progress-line"></div>

                    <div class="detail-progress-step active <?= $status !== 'belum' ? 'done' : '' ?>">
                        <span class="detail-progress-dot">1</span>
                        <small>Baru</small>
                    </div>

                    <div class="detail-progress-step <?= in_array($status, ['diproses','selesai'], true) ? 'active' : '' ?> <?= $status === 'selesai' ? 'done' : '' ?>">
                        <span class="detail-progress-dot">2</span>
                        <small>Diproses</small>
                    </div>

                    <div class="detail-progress-step <?= $status === 'selesai' ? 'active done' : '' ?>">
                        <span class="detail-progress-dot">3</span>
                        <small>Selesai</small>
                    </div>
                </div>

            </div>


            <!-- =====================================================
                 TIMELINE TINDAKAN
            ====================================================== -->

            <div class="detail-history-box mt-3">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong>📝 Riwayat Tindakan</strong>
                    <a
                        href="catatan.php?id=<?= $id ?>"
                        class="btn btn-sm btn-outline-primary"
                    >
                        Lihat Semua
                    </a>
                </div>

                <?php if (empty($riwayatTindakan)): ?>

                    <div class="text-muted small py-2">
                        Belum ada catatan tindakan untuk layanan ini.
                    </div>

                <?php else: ?>

                    <div class="detail-timeline">

                        <?php foreach ($riwayatTindakan as $riwayat): ?>

                            <?php
                            $statusRiwayat = $riwayat['status_ke'] ?? '';
                            $statusDariRiwayat = $riwayat['status_dari'] ?? '';
                            ?>

                            <div class="detail-timeline-item">

                                <div class="detail-timeline-dot <?= htmlspecialchars(classStatusDetail($statusRiwayat)) ?>"></div>

                                <div class="detail-timeline-content">

                                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                        <div>
                                            <strong>
                                                <?= htmlspecialchars(labelStatusDetail($statusRiwayat)) ?>
                                            </strong>

                                            <div class="text-muted small">
                                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($riwayat['created_at']))) ?>
                                            </div>
                                        </div>

                                        <?php if ($statusDariRiwayat !== ''): ?>
                                            <span class="text-muted small">
                                                <?= htmlspecialchars(labelStatusDetail($statusDariRiwayat)) ?>
                                                →
                                                <?= htmlspecialchars(labelStatusDetail($statusRiwayat)) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="small mt-2">
                                        👤 Petugas:
                                        <strong><?= htmlspecialchars($riwayat['nama_petugas'] ?? 'Tidak tercatat') ?></strong>
                                    </div>

                                    <?php if (trim((string)($riwayat['catatan'] ?? '')) !== ''): ?>
                                        <div class="detail-timeline-note">
                                            <?= nl2br(htmlspecialchars($riwayat['catatan'], ENT_QUOTES, 'UTF-8')) ?>
                                        </div>
                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>



    <!-- =====================================================
         TINDAK LANJUT
    ====================================================== -->

    <div class="col-lg-5">

        <div class="cardbox">


            <h5 class="mb-3">
                Tindak Lanjut
            </h5>



            <!-- =================================================
                 STATUS SEKARANG
            ================================================== -->

            <div class="mb-3">

                <strong>
                    Status Saat Ini:
                </strong>


                <?php if ($status === 'belum'): ?>

                    <span class="badge bg-warning text-dark">
                        Belum Diurus
                    </span>


                <?php elseif ($status === 'diproses'): ?>

                    <span class="badge bg-primary">
                        Sedang Diproses
                    </span>


                <?php elseif ($status === 'selesai'): ?>

                    <span class="badge bg-success">
                        Selesai
                    </span>

                <?php endif; ?>

            </div>



            <!-- =================================================
                 PETUGAS YANG SEDANG MEMPROSES
            ================================================== -->

            <?php if ($status === 'diproses'): ?>

                <div class="alert alert-info">

                    <strong>
                        👤 Sedang Dikerjakan Oleh:
                    </strong>

                    <br>

                    <?= htmlspecialchars(
                        $x['petugas_proses'] ?? '-'
                    ) ?>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 JIKA SUDAH SELESAI
            ================================================== -->

            <?php if ($status === 'selesai'): ?>

                <div class="alert alert-success">

                    <strong>
                        ✅ Layanan Selesai
                    </strong>


                    <hr>


                    <div>

                        <strong>
                            Petugas Penyelesaian:
                        </strong>

                        <br>

                        <?= htmlspecialchars(
                            $x['petugas_penyelesaian'] ?? '-'
                        ) ?>

                    </div>


                    <div class="mt-2">

                        <strong>
                            Tanggal/Waktu Selesai:
                        </strong>

                        <br>

                        <?= htmlspecialchars(
                            $x['waktu_selesai'] ?? '-'
                        ) ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 KONFIRMASI KE PENGUNJUNG
            ================================================== -->

            <?php if ($status === 'selesai'): ?>

                <div class="cardbox mt-3" style="border:1px solid #e5eaf0; background:#fbfcff;">

                    <div class="d-flex align-items-start gap-2">

                        <div style="font-size:24px; line-height:1;">📲</div>

                        <div class="flex-grow-1">

                            <h5 class="mb-1">
                                Konfirmasi ke Pengunjung
                            </h5>

                            <?php if ($isWaSource): ?>

                                <div class="alert alert-warning mb-3 py-2">
                                    <strong>⚠️ Wajib:</strong> Pengunjung mendapatkan link melalui WhatsApp Admin.
                                    Setelah layanan selesai, petugas wajib menginformasikan kepada pengunjung bahwa layanannya telah selesai.
                                </div>

                            <?php else: ?>

                                <div class="alert alert-secondary mb-3 py-2">
                                    Pengunjung datang langsung dari lokasi universitas. Menghubungi pengunjung melalui WhatsApp bersifat <strong>opsional</strong>.
                                </div>

                            <?php endif; ?>

                            <div class="mb-2">
                                <small class="text-muted d-block">Nomor WhatsApp Pengunjung</small>

                                <?php if ($waNumber !== ''): ?>

                                    <a
                                        href="https://wa.me/<?= htmlspecialchars($waNumber) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="fw-semibold text-decoration-none"
                                        style="font-size:18px;"
                                    >
                                        📱 <?= htmlspecialchars($teleponPengunjung) ?>
                                    </a>

                                    <div class="small text-muted mt-1">
                                        Klik nomor untuk membuka WhatsApp pada perangkat petugas.
                                    </div>

                                <?php else: ?>

                                    <span class="text-muted">Nomor WhatsApp tidak tersedia.</span>

                                <?php endif; ?>
                            </div>

                        </div>

                    </div>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 FORM TINDAK LANJUT
            ================================================== -->

            <?php if ($status !== 'selesai'): ?>

                <div class="alert alert-info py-2">
    <i class="fas fa-lock me-1"></i> Detail ini sedang dibuka oleh akun petugas Anda. Petugas lain tidak dapat membuka detail yang sama selama kunci masih aktif.
</div>

<form
                    method="post"
                    action="tindak_lanjut.php"
                    id="formTindakLanjut"
                >
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">


                    <input
                        type="hidden"
                        name="id"
                        value="<?= $id ?>"
                    >



                    <!-- =========================================
                         STATUS
                    ========================================== -->

                    <div class="mb-3">

                        <label
                            for="statusLayanan"
                            class="form-label fw-semibold"
                        >
                            Status Layanan
                        </label>


                        <select
                            name="status_layanan"
                            class="form-select"
                            id="statusLayanan"
                            required
                        >

                            <option
                                value="belum"
                                <?= $status === 'belum'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Belum Diurus
                            </option>


                            <option
                                value="diproses"
                                <?= $status === 'diproses'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Sedang Diproses
                            </option>


                            <option
                                value="selesai"
                            >
                                Selesai
                            </option>

                        </select>

                    </div>



                    <!-- =========================================
                         PETUGAS
                    ========================================== -->

                    <div
                        id="petugasProsesBox"
                        class="mb-3"
                    >

                        <label
                            class="form-label fw-semibold"
                        >
                            Petugas yang Mengurus
                        </label>


                        <select
                            name="id_petugas"
                            class="form-select"
                        >

                            <option value="">
                                -- Otomatis menggunakan akun saya --
                            </option>


                            <?php if ($admins): ?>

                                <?php while (
                                    $admin =
                                    mysqli_fetch_assoc($admins)
                                ): ?>

                                    <option
                                        value="<?= (int)$admin['id_admin'] ?>"
                                        <?= (
                                            (int)$x['id_petugas']
                                            ===
                                            (int)$admin['id_admin']
                                        )
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        <?= htmlspecialchars(
                                            $admin['nama']
                                        ) ?>

                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>


                        <small class="text-muted">

                            Jika tidak memilih petugas,
                            sistem menggunakan akun admin yang sedang login.

                        </small>

                    </div>



                    <!-- =========================================
                         CATATAN TINDAKAN
                    ========================================== -->

                    <div class="mb-3">

                        <label
                            for="catatan"
                            class="form-label fw-semibold"
                        >
                            Catatan Tindakan
                        </label>


                        <textarea
                            name="catatan"
                            id="catatan"
                            class="form-control"
                            rows="5"
                            placeholder="Contoh: Mengirim data persetujuan ke pusat"
                            required
                        ></textarea>


                        <small class="text-muted">

                            Tuliskan tindakan atau perkembangan
                            yang dilakukan terhadap layanan ini.

                        </small>

                    </div>



                    <!-- =========================================
                         TOMBOL
                    ========================================== -->

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                        id="btnSimpan"
                    >

                        💾 Simpan Tindak Lanjut

                    </button>


                </form>

            <?php endif; ?>


        </div>

    </div>

</div>



<script>

/*
|--------------------------------------------------------------------------
| ELEMENT
|--------------------------------------------------------------------------
*/

const statusSelect =
    document.getElementById('statusLayanan');

const petugasBox =
    document.getElementById('petugasProsesBox');


/*
|--------------------------------------------------------------------------
| TAMPILKAN PETUGAS
|--------------------------------------------------------------------------
|
| Petugas tetap ditampilkan untuk Diproses maupun Selesai.
|
*/

function updatePetugasBox() {

    if (!statusSelect || !petugasBox) {

        return;

    }


    if (statusSelect.value === 'belum') {

        petugasBox.style.display = 'none';

    } else {

        petugasBox.style.display = 'block';

    }

}


if (statusSelect) {

    statusSelect.addEventListener(
        'change',
        updatePetugasBox
    );


    updatePetugasBox();

}

</script>


<script>
(function () {
    const form = document.getElementById('formTindakLanjut');
    const status = document.getElementById('statusLayanan');

    if (!form || !status) return;

    form.addEventListener('submit', function (event) {
        if (status.value === 'selesai') {
            const yakin = window.confirm(
                'Yakin layanan ini sudah selesai?\n\nPastikan catatan tindakan sudah menjelaskan penyelesaian layanan.'
            );

            if (!yakin) {
                event.preventDefault();
            }
        }
    });
})();
</script>

<script>
(function () {
    const idKunjungan = <?= (int)$id ?>;
    const csrf = <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const endpoint = 'detail_lock.php';
    let sedangSubmit = false;

    const heartbeat = window.setInterval(function () {
        const body = new URLSearchParams({
            id: String(idKunjungan),
            action: 'heartbeat',
            csrf_token: csrf
        });
        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body.toString()
        }).catch(function () {});
    }, 45000);

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function () {
            sedangSubmit = true;
        });
    }

    window.addEventListener('pagehide', function () {
        window.clearInterval(heartbeat);
        if (sedangSubmit) return;

        const body = new URLSearchParams({
            id: String(idKunjungan),
            action: 'release',
            csrf_token: csrf
        });

        if (navigator.sendBeacon) {
            navigator.sendBeacon(
                endpoint,
                new Blob([body.toString()], {type: 'application/x-www-form-urlencoded'})
            );
        } else {
            fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: true,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body.toString()
            }).catch(function () {});
        }
    });
})();
</script>

<?php require 'footer.php'; ?>