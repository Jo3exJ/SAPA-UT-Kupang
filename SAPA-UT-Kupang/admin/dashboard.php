<?php

$title = 'Dashboard';
require 'header.php';


/*
|--------------------------------------------------------------------------
| FUNGSI HITUNG DATA
|--------------------------------------------------------------------------
*/

function jumlahData($conn, $where = '1=1')
{
    $sql = "SELECT COUNT(*) AS total
            FROM kunjungan
            WHERE $where";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $data = mysqli_fetch_assoc($result);

    return (int)($data['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| DATA TOTAL PENGUNJUNG
|--------------------------------------------------------------------------
*/

$totalPengunjung = jumlahData($conn);


/*
|--------------------------------------------------------------------------
| DATA BULAN INI
|--------------------------------------------------------------------------
*/

$bulanIni = jumlahData(
    $conn,
    "YEAR(tanggal) = YEAR(CURDATE())
     AND MONTH(tanggal) = MONTH(CURDATE())"
);

$hariIni = jumlahData(
    $conn,
    "tanggal = CURDATE()"
);


/*
|--------------------------------------------------------------------------
| DATA BERDASARKAN STATUS
|--------------------------------------------------------------------------
*/

$mahasiswa = jumlahData(
    $conn,
    "status = 'mahasiswa'"
);

$alumni = jumlahData(
    $conn,
    "status = 'alumni'"
);

$masyarakat = jumlahData(
    $conn,
    "status = 'masyarakat'"
);


/*
|--------------------------------------------------------------------------
| STATUS LAYANAN
|--------------------------------------------------------------------------
*/

$tiketBaru = jumlahData(
    $conn,
    "status_layanan = 'belum'"
);

$sedangDiproses = jumlahData(
    $conn,
    "status_layanan = 'diproses'"
);

$selesai = jumlahData(
    $conn,
    "status_layanan = 'selesai'"
);

/* Ringkasan proses khusus hari ini. */
$baruHariIni = jumlahData($conn, "tanggal = CURDATE() AND status_layanan = 'belum'");
$prosesHariIni = jumlahData($conn, "tanggal = CURDATE() AND status_layanan = 'diproses'");
$selesaiHariIni = jumlahData($conn, "tanggal = CURDATE() AND status_layanan = 'selesai'");

$layananTerpopuler='-'; $layananTerpopulerTotal=0;
$qTopService=mysqli_query($conn,"SELECT sub_layanan,COUNT(*) AS total FROM kunjungan WHERE tanggal=CURDATE() AND sub_layanan IS NOT NULL AND sub_layanan<>'' GROUP BY sub_layanan ORDER BY total DESC,sub_layanan ASC LIMIT 1");
if($qTopService&&($top=mysqli_fetch_assoc($qTopService))){$layananTerpopuler=$top['sub_layanan'];$layananTerpopulerTotal=(int)$top['total'];}

/* Data 7 hari terakhir untuk grafik ringan tanpa library tambahan. */
$weeklyMap = [];
$weeklyStart = date('Y-m-d', strtotime('-6 days'));
$weeklyQ = mysqli_query($conn, "
    SELECT tanggal, COUNT(*) AS total
    FROM kunjungan
    WHERE tanggal BETWEEN '" . mysqli_real_escape_string($conn, $weeklyStart) . "' AND CURDATE()
    GROUP BY tanggal
    ORDER BY tanggal ASC
");
if ($weeklyQ) {
    while ($wr = mysqli_fetch_assoc($weeklyQ)) {
        $weeklyMap[$wr['tanggal']] = (int)$wr['total'];
    }
}
$weekly = [];
$maxWeekly = 1;
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime('-' . $i . ' days'));
    $hariNama = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    $totalHari = $weeklyMap[$tgl] ?? 0;
    $weekly[] = [
        'tanggal' => $tgl,
        'hari' => $hariNama[(int)date('w', strtotime($tgl))],
        'total' => $totalHari,
    ];
    $maxWeekly = max($maxWeekly, $totalHari);
}

?>

<!-- =========================================================
     JUDUL
========================================================= -->

<div class="dashboard-heading">

    <h2>
        Dashboard Admin
    </h2>

    <p>
        Ringkasan data layanan dan kunjungan.
    </p>

</div>


<!-- =========================================================
     TOTAL PENGUNJUNG
========================================================= -->

<div
    class="total-card"
    id="totalPengunjungCard"
    onclick="togglePengunjung()"
>

    <div class="total-card-top">

        <div>

            <div class="total-card-title">
                Total Pengunjung
            </div>

            <div class="total-card-number">
                <?= $totalPengunjung ?>
            </div>

        </div>


        <div class="total-card-icon">
            👥
        </div>

    </div>


    <!-- DETAIL -->

    <div
        class="pengunjung-detail"
        id="pengunjungDetail"
    >

        <div class="detail-item">

            <span>
                📅 Hari Ini
            </span>

            <strong>
                <?= $hariIni ?>
            </strong>

        </div>


        <div class="detail-item">

            <span>
                🗓️ Bulan Ini
            </span>

            <strong>
                <?= $bulanIni ?>
            </strong>

        </div>


        <div class="detail-item">

            <span>
                🎓 Mahasiswa
            </span>

            <strong>
                <?= $mahasiswa ?>
            </strong>

        </div>


        <div class="detail-item">

            <span>
                🧑‍🎓 Alumni
            </span>

            <strong>
                <?= $alumni ?>
            </strong>

        </div>


        <div class="detail-item">

            <span>
                👥 Masyarakat
            </span>

            <strong>
                <?= $masyarakat ?>
            </strong>

        </div>

    </div>


    <div class="total-card-hint">

        <span id="pengunjungHint">
            Klik untuk melihat rincian
        </span>

        <span id="pengunjungArrow">
            ▼
        </span>

    </div>

</div>


<!-- =========================================================
     STATUS LAYANAN
========================================================= -->

<div class="service-cards">


    <!-- TIKET BARU -->

    <div class="service-card service-baru" role="link" tabindex="0" data-target="data_pengunjung.php?status=belum">

        <div class="service-card-icon">
            🎫
        </div>

        <div class="service-card-info">

            <div class="service-card-title">
                Tiket Baru
            </div>

            <div class="service-card-number">
                <?= $tiketBaru ?>
            </div>

            <small>
                Belum diurus
            </small>

        </div>

    </div>


    <!-- DIPROSES -->

    <div class="service-card service-proses" role="link" tabindex="0" data-target="data_pengunjung.php?status=diproses">

        <div class="service-card-icon">
            🔄
        </div>

        <div class="service-card-info">

            <div class="service-card-title">
                Sedang Diproses
            </div>

            <div class="service-card-number">
                <?= $sedangDiproses ?>
            </div>

            <small>
                Sedang ditangani
            </small>

        </div>

    </div>


    <!-- SELESAI -->

    <div class="service-card service-selesai" role="link" tabindex="0" data-target="data_pengunjung.php?status=selesai">

        <div class="service-card-icon">
            ✅
        </div>

        <div class="service-card-info">

            <div class="service-card-title">
                Selesai
            </div>

            <div class="service-card-number">
                <?= $selesai ?>
            </div>

            <small>
                Layanan selesai
            </small>

        </div>

    </div>


</div>


<!-- =========================================================
     RINGKASAN & GRAFIK
========================================================= -->

<div class="dashboard-lower-grid">
    <div class="dashboard-panel">
        <div class="panel-title-row">
            <div>
                <h3>Aktivitas 7 Hari Terakhir</h3>
                <p>Jumlah pengunjung yang masuk setiap hari.</p>
            </div>
            <span class="panel-badge">Live data</span>
        </div>

        <div class="weekly-chart" aria-label="Grafik pengunjung 7 hari terakhir">
            <?php foreach ($weekly as $bar): ?>
                <?php $height = $bar['total'] > 0 ? max(12, (int)round(($bar['total'] / $maxWeekly) * 100)) : 4; ?>
                <div class="chart-col" title="<?= htmlspecialchars($bar['tanggal']) ?>: <?= $bar['total'] ?> pengunjung">
                    <div class="chart-number"><?= $bar['total'] ?></div>
                    <div class="chart-track">
                        <div class="chart-bar" style="height: <?= $height ?>%;"></div>
                    </div>
                    <div class="chart-day"><?= $bar['hari'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="dashboard-panel">
        <div class="panel-title-row">
            <div>
                <h3>Ringkasan Hari Ini</h3>
                <p>Posisi layanan pada hari berjalan.</p>
            </div>
        </div>

        <a class="today-stat" href="data_pengunjung.php?periode=hari_ini&status=belum">
            <span><b>Baru</b><small>Menunggu ditangani</small></span>
            <strong><?= $baruHariIni ?></strong>
        </a>
        <a class="today-stat" href="data_pengunjung.php?periode=hari_ini&status=diproses">
            <span><b>Sedang Diproses</b><small>Sedang ditangani petugas</small></span>
            <strong><?= $prosesHariIni ?></strong>
        </a>
        <a class="today-stat" href="data_pengunjung.php?periode=hari_ini&status=selesai">
            <span><b>Selesai</b><small>Layanan telah selesai</small></span>
            <strong><?= $selesaiHariIni ?></strong>
        </a>
    </div>
</div>


<div class="dashboard-panel mt-3"><div class="panel-title-row"><div><h3>Layanan Paling Banyak Hari Ini</h3><p>Layanan yang paling sering dipilih pengunjung pada hari ini.</p></div><a href="data_pengunjung.php?periode=hari_ini" class="btn btn-sm btn-outline-primary">Lihat Data</a></div><div class="d-flex justify-content-between align-items-center gap-3 flex-wrap" style="padding:14px 0 2px;"><div><div class="text-muted small">Layanan</div><strong style="font-size:19px;color:#17324d;"><?= htmlspecialchars($layananTerpopuler) ?></strong></div><div class="text-end"><div class="text-muted small">Jumlah Pengunjung</div><strong style="font-size:22px;color:#073b82;"><?= $layananTerpopulerTotal ?></strong></div></div></div>

<!-- =========================================================
     STYLE
========================================================= -->

<style>

/*
|--------------------------------------------------------------------------
| JUDUL
|--------------------------------------------------------------------------
*/

.dashboard-heading {
    margin-bottom: 25px;
}

.dashboard-heading h2 {
    margin: 0 0 5px;
    color: #073b82;
    font-weight: 700;
}

.dashboard-heading p {
    margin: 0;
    color: #6c757d;
}


/*
|--------------------------------------------------------------------------
| TOTAL PENGUNJUNG
|--------------------------------------------------------------------------
*/

.total-card {

    background: #ffffff;

    border-radius: 18px;

    padding: 28px 30px;

    margin-bottom: 22px;

    border: 1px solid #dfe5ec;

    border-top: 5px solid #073b82;

    box-shadow: 0 4px 15px rgba(0, 0, 0, .06);

    cursor: pointer;

    transition: .2s;

}

.total-card:hover {

    box-shadow: 0 7px 22px rgba(0, 0, 0, .09);

    transform: translateY(-2px);

}


.total-card-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.total-card-title {

    font-size: 17px;

    color: #555;

    margin-bottom: 5px;

}


.total-card-number {

    font-size: 48px;

    line-height: 1;

    font-weight: 700;

    color: #073b82;

}


.total-card-icon {

    font-size: 55px;

}


/*
|--------------------------------------------------------------------------
| DETAIL PENGUNJUNG
|--------------------------------------------------------------------------
*/

.pengunjung-detail {

    display: none;

    grid-template-columns:
        repeat(5, 1fr);

    gap: 12px;

    margin-top: 25px;

    padding-top: 20px;

    border-top: 1px solid #e5e7eb;

}


.pengunjung-detail.show {

    display: grid;

}


.detail-item {

    background: #f7f9fc;

    border-radius: 12px;

    padding: 15px;

    display: flex;

    flex-direction: column;

    gap: 7px;

}


.detail-item span {

    color: #555;

    font-size: 14px;

}


.detail-item strong {

    color: #073b82;

    font-size: 24px;

}


.total-card-hint {

    margin-top: 18px;

    font-size: 13px;

    color: #777;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


#pengunjungArrow {

    transition: .2s;

}


#pengunjungArrow.rotate {

    transform: rotate(180deg);

}


/*
|--------------------------------------------------------------------------
| SERVICE CARDS
|--------------------------------------------------------------------------
*/

.service-cards {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

}


.service-card {

    background: #ffffff;

    border-radius: 15px;

    padding: 22px;

    min-height: 135px;

    display: flex;

    align-items: center;

    gap: 18px;

    border: 1px solid #e5e7eb;

    box-shadow:
        0 3px 12px rgba(0, 0, 0, .05);

    transition: .2s;

}


.service-card:hover {

    transform: translateY(-2px);

    box-shadow:
        0 6px 18px rgba(0, 0, 0, .08);

}


.service-card-icon {

    font-size: 36px;

}


.service-card-title {

    font-size: 14px;

    color: #555;

    margin-bottom: 5px;

}


.service-card-number {

    font-size: 32px;

    line-height: 1;

    font-weight: 700;

}


.service-card small {

    color: #777;

}


.service-baru {

    border-top: 4px solid #ffc107;

}


.service-baru
.service-card-number {

    color: #856404;

}


.service-proses {

    border-top: 4px solid #0dcaf0;

}


.service-proses
.service-card-number {

    color: #087990;

}


.service-selesai {

    border-top: 4px solid #198754;

}


.service-selesai
.service-card-number {

    color: #198754;

}


/*
|--------------------------------------------------------------------------
| TABLET
|--------------------------------------------------------------------------
*/

@media (max-width: 900px) {

    .pengunjung-detail {

        grid-template-columns:
            repeat(2, 1fr);

    }


    .service-cards {

        grid-template-columns:
            repeat(3, 1fr);

    }

}


/*
|--------------------------------------------------------------------------
| HP
|--------------------------------------------------------------------------
*/

@media (max-width: 576px) {

    .dashboard-heading h2 {

        font-size: 23px;

    }


    .total-card {

        padding: 22px;

    }


    .total-card-number {

        font-size: 40px;

    }


    .total-card-icon {

        font-size: 42px;

    }


    .pengunjung-detail {

        grid-template-columns:
            1fr 1fr;

        gap: 8px;

    }


    .detail-item {

        padding: 12px;

    }


    .detail-item strong {

        font-size: 20px;

    }


    .service-cards {

        grid-template-columns: 1fr;

        gap: 12px;

    }


    .service-card {

        min-height: 100px;

    }

}

.service-card[data-target] {
    cursor: pointer;
}

.service-card[data-target]:focus {
    outline: 3px solid rgba(13, 110, 253, .25);
    outline-offset: 3px;
}

</style>
<style>

/*
|--------------------------------------------------------------------------
| DASHBOARD BAGIAN BAWAH
|--------------------------------------------------------------------------
*/
.dashboard-lower-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(300px, 1fr);
    gap: 20px;
    margin-top: 20px;
}
.dashboard-panel {
    background: #fff;
    border: 1px solid #dfe5ec;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(0,0,0,.05);
}
.panel-title-row {
    display:flex;
    justify-content:space-between;
    gap:16px;
    align-items:flex-start;
    margin-bottom:18px;
}
.panel-title-row h3 { margin:0 0 4px; font-size:18px; color:#17324d; }
.panel-title-row p { margin:0; color:#7b8794; font-size:13px; }
.panel-badge {
    font-size:11px;
    padding:5px 9px;
    border-radius:999px;
    background:#eef5ff;
    color:#0d6efd;
    font-weight:700;
    white-space:nowrap;
}
.weekly-chart {
    height: 220px;
    display:grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    align-items:end;
}
.chart-col {
    height:100%;
    display:flex;
    flex-direction:column;
    justify-content:flex-end;
    align-items:center;
    min-width:0;
}
.chart-number { font-size:11px; color:#607082; margin-bottom:5px; font-weight:700; }
.chart-track { width:100%; max-width:42px; height:145px; display:flex; align-items:flex-end; background:#f1f4f8; border-radius:10px 10px 5px 5px; overflow:hidden; }
.chart-bar { width:100%; background:#0d6efd; border-radius:10px 10px 5px 5px; min-height:4px; }
.chart-day { margin-top:8px; font-size:11px; color:#6c7784; font-weight:600; }
.today-stat {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    text-decoration:none;
    color:inherit;
    padding:13px 0;
    border-bottom:1px solid #edf0f4;
}
.today-stat:last-child { border-bottom:0; }
.today-stat:hover strong { transform:scale(1.05); }
.today-stat span { display:flex; flex-direction:column; }
.today-stat b { color:#17324d; font-size:14px; }
.today-stat small { color:#7b8794; font-size:12px; margin-top:2px; }
.today-stat strong { color:#073b82; font-size:23px; transition:.15s; }
@media (max-width: 900px) {
    .dashboard-lower-grid { grid-template-columns: 1fr; }
}
@media (max-width: 576px) {
    .dashboard-panel { padding:17px; }
    .weekly-chart { gap:6px; }
    .chart-track { max-width:30px; }
}
</style>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

function togglePengunjung() {

    const detail =
        document.getElementById(
            'pengunjungDetail'
        );

    const arrow =
        document.getElementById(
            'pengunjungArrow'
        );

    const hint =
        document.getElementById(
            'pengunjungHint'
        );


    detail.classList.toggle('show');

    arrow.classList.toggle('rotate');


    if (detail.classList.contains('show')) {

        hint.textContent =
            'Klik untuk menyembunyikan rincian';

    } else {

        hint.textContent =
            'Klik untuk melihat rincian';

    }

}

</script>


<script>
(function () {
    document.querySelectorAll('.service-card[data-target]').forEach(function (card) {
        card.addEventListener('click', function () {
            window.location.href = card.dataset.target;
        });

        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                window.location.href = card.dataset.target;
            }
        });
    });
})();
</script>

<?php require 'footer.php'; ?>