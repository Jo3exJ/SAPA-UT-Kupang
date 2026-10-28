<?php
$title = 'Antrian';
require 'header.php';

date_default_timezone_set('Asia/Makassar');

$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

/* Total data antrian hari ini. Tidak ada nomor antrean. */
$qTotal = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM kunjungan
    WHERE tanggal = CURDATE()
      AND status_layanan IN ('belum', 'diproses')
      AND (sumber_link IS NULL OR sumber_link = 'lokasi_universitas')
");
$totalAntrian = 0;
if ($qTotal) {
    $totalAntrian = (int)(mysqli_fetch_assoc($qTotal)['total'] ?? 0);
}

$totalPages = max(1, (int)ceil($totalAntrian / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT
        k.id_kunjungan,
        k.nama,
        k.status,
        k.layanan,
        k.sub_layanan,
        k.status_layanan,
        k.created_at,
        a.nama AS petugas_proses,
        CASE
            WHEN k.created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) THEN 1
            ELSE 0
        END AS is_baru
    FROM kunjungan k
    LEFT JOIN admins a ON a.id_admin = k.id_petugas
    WHERE k.tanggal = CURDATE()
      AND k.status_layanan IN ('belum', 'diproses')
      AND (k.sumber_link IS NULL OR k.sumber_link = 'lokasi_universitas')
    ORDER BY k.created_at ASC, k.id_kunjungan ASC
    LIMIT $perPage OFFSET $offset
";

$query = mysqli_query($conn, $sql);
if (!$query) {
    die('Gagal mengambil data antrian: ' . mysqli_error($conn));
}

?>

<style>
.antrian-header { margin-bottom: 20px; }
.antrian-header h2 { margin-bottom: 4px; }
.antrian-summary,
.antrian-card {
    border: 1px solid #e6ebf1;
    border-radius: 14px;
    box-shadow: 0 3px 14px rgba(20, 50, 90, .05);
}
.antrian-summary .angka {
    font-size: 30px;
    line-height: 1;
    font-weight: 700;
    color: #073b82;
}
.antrian-card { overflow: hidden; }
.antrian-card .card-header {
    background: #fff;
    border-bottom: 1px solid #edf0f4;
    padding: 16px 18px;
}
.antrian-table { margin-bottom: 0; }
.antrian-table th {
    font-size: 12px;
    white-space: nowrap;
    background: #f8fafc;
    color: #536275;
    font-weight: 700;
}
.antrian-table td { font-size: 13px; vertical-align: middle; }
.status-baru { background: #eaf3ff; color: #0d6efd; }
.status-proses { background: #fff4d6; color: #8a6200; }
.badge-baru {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-left: 6px;
    font-size: 10px;
    padding: 4px 7px;
    background: #eef3ff;
    color: #285bb5;
    border: 1px solid #d9e4fb;
}
.nama-pengunjung {
    color: #1f3c5b;
    font-weight: 700;
}
.auto-refresh-info { font-size: 12px; color: #7a8795; }
@media (max-width: 768px) {
    .antrian-header h2 { font-size: 22px; }
    .antrian-table { min-width: 900px; }
}
</style>

<div class="antrian-header">
    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
            <h2>👥 Antrian Hari Ini</h2>
            <p class="text-muted mb-0">Daftar pengunjung yang datang langsung ke lokasi dan perlu ditangani petugas hari ini.</p>
        </div>
        <div class="text-md-end">
            <strong><?= date('d-m-Y') ?></strong><br>
            
        </div>
    </div>
</div>

<div class="alert alert-info py-2">
    <strong>Informasi:</strong> Daftar ini hanya berisi pengunjung hari ini yang berstatus Baru atau Sedang Diproses. Data selesai tidak ditampilkan di sini.
</div>

<div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="card antrian-summary">
            <div class="card-body">
                <div class="text-muted small">PENGUNJUNG PERLU DITANGANI</div>
                <div class="angka mt-1"><?= $totalAntrian ?></div>
                <div class="auto-refresh-info mt-2">Daftar diperbarui otomatis setiap 15 detik.</div>
            </div>
        </div>
    </div>
</div>

<div class="card antrian-card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <strong>Daftar Pengunjung</strong>
            <div class="d-flex align-items-center gap-2">
                <small class="text-muted"><?= $totalAntrian ?> data</small>
                <button type="button" class="btn btn-sm btn-outline-primary" id="refreshAntrian">↻ Perbarui</button>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if ($totalAntrian === 0): ?>
            <div class="text-center py-5">
                <div style="font-size:42px;">✓</div>
                <h5 class="mt-3">Tidak Ada Pengunjung yang Menunggu</h5>
                <p class="text-muted mb-0">Belum ada layanan yang perlu ditangani hari ini.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle antrian-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th>Layanan</th>
                            <th>Sub Layanan</th>
                            <th>Petugas</th>
                            <th class="text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
                        <?php $status = $row['status_layanan'] ?? 'belum'; ?>
                        <tr>
                            <td><?= htmlspecialchars(date('H:i', strtotime($row['created_at']))) ?></td>
                            <td>
                                <span class="nama-pengunjung"><?= htmlspecialchars($row['nama']) ?></span>
                                <?php if ((int)($row['is_baru'] ?? 0) === 1): ?>
                                    <span class="badge rounded-pill badge-baru">BARU</span>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars(ucfirst($row['status'] ?? '-')) ?></small>
                            </td>
                            <td>
                                <?php if ($status === 'diproses'): ?>
                                    <span class="badge status-proses">Sedang Diproses</span>
                                <?php else: ?>
                                    <span class="badge status-baru">Baru</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['layanan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['sub_layanan'] ?? '-') ?></td>
                            <td>
                                <?php if ($status === 'diproses'): ?>
                                    <span class="text-primary fw-semibold">👤 <?= htmlspecialchars($row['petugas_proses'] ?? '-') ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Belum ada</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="detail_pengunjung.php?id=<?= (int)$row['id_kunjungan'] ?>" class="btn btn-sm btn-primary">Detail</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white">
            <nav aria-label="Navigasi antrian">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="antrian.php?page=<?= $page - 1 ?>">‹</a></li>
                    <?php endif; ?>
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="antrian.php?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="antrian.php?page=<?= $page + 1 ?>">›</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    const refreshBtn = document.getElementById('refreshAntrian');
    if (refreshBtn) refreshBtn.addEventListener('click', function () { window.location.reload(); });

    setTimeout(function () {
        if (!document.hidden) window.location.reload();
    }, 15000);
})();
</script>

<?php require 'footer.php'; ?>
