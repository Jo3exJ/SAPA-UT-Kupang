<?php
session_start();
require_once __DIR__ . '/csrf.php';

/*
|--------------------------------------------------------------------------
| CEK DATA PENGUNJUNG
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION["pengunjung"])) {
    header("Location: pengunjung.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA LAYANAN
|--------------------------------------------------------------------------
| Daftar layanan mengikuti data yang sudah digunakan pada sistem.
|--------------------------------------------------------------------------
*/

$kategori = [

    "Akreditasi Kampus" => [
        "Permintaan Layanan cetak Sertifikat akreditasi UT"
    ],

    "Alih Kredit/RPL" => [
        "Pengaduan Kendala RPL Nilai Belum Grading pada LKAM",
        "Layanan Sanggah Hasil RPL MABA",
        "Pengaduan Kendala Pendaftaran Jalur RPL masih terkendala/stuck"
    ],

    "Bahan Ajar" => [
        "Pengaduan Bahan Ajar yang belum tiba di alamat mahasiswa",
        "Pengambilan Bahan Ajar Mahasiswa",
        "Pengaduan BA yang diterima kurang lengkap",
        "Layanan Informasi Pembelian Bahan Ajar oleh masyarakat umum"
    ],

    "Bahan Ajar Digital" => [
        "Pengaduan Tidak bisa akses BA Digital"
    ],

    "Beasiswa" => [
        "Layanan Informasi Ketersediaan Beasiswa di UT"
    ],

    "DS" => [
        "Permohonan Validasi Berkas DS Mahasiswa",
        "Layanan Informasi dan Unggah Berkas Mahasiswa DS",
        "Layanan Informasi Berkas Mahasiswa DS"
    ],

    "MYUT" => [
        "Permintaan Layanan Reset Password Akun MY UT",
        "Permintaan Layanan Create Akun MY UT"
    ],

    "Pendaftaran Calon Mahasiswa Baru" => [
        "Permintaan Layanan Informasi Jadwal Pendaftaran",
        "Permintaan Layanan Bantuan Daftar Calon Mahasiswa Baru",
        "Layanan bantuan konsultasi gagal validasi CAMA",
        "Layanan bantuan konsultasi Pemberkasan belum divalidasi oleh UT Pusat",
        "Layanan Permintaan Hapus NAC MABA"
    ],

    "LPKBJJ Mahasiswa Baru" => [
        "Permintaan Layanan Informasi Jadwal LPKBJJ Mahasiswa Baru",
        "Layanan Pengaduan Kendala Mahasiswa tidak bisa akses Courses LPKBJJ",
        "Layanan pengaduan Mahasiswa tidak bisa akses Sertifikat LPKBJJ",
        "Layanan pengaduan kendala mahasiswa Nilai Courses tidak muncul",
        "Permintaan Layanan Informasi Jadwal WT dan KU"
    ],

    "Tutorial Online" => [
        "Permintaan Layanan Informasi Jadwal Kuliah (Tuton/Tuweb)",
        "Kendala Akses Laman Tutorial Mahasiswa",
        "Layanan Bantuan Aktivasi Tuton",
        "Layanan Bantuan Reset Password Tuton",
        "Pengaduan mata Kuliah belum muncul di Dashboard Elearning (Tuton)",
        "Pengaduan Tugas belum diberi penilaian oleh Tutor",
        "Permintaan Layanan Informasi Mekanisme dan Prosedur TUTON"
    ],

    "Laman Praktek/Praktikum" => [
        "Layanan Bantuan Reset Pass Akun Praktek/Praktikum"
    ],

    "Informasi" => [
        "Informasi Penerimaan Mahasiswa Baru",
        "Informasi Registrasi dan Administrasi",
        "Informasi Akademik dan Pembelajaran",
        "Informasi Nilai",
        "Informasi Kelulusan dan Wisuda",
        "Informasi Program Pascasarjana",
        "Informasi Layanan dan Organisasi Kemahasiswaan",
        "Informasi Alumni",
        "Informasi SALUT",
        "Informasi Beasiswa",
        "Informasi Atribut Kuliah (Jas Almamater, dll)",
        "Informasi Umum UT & Layanan Masyarakat"
    ],

    "Ijazah" => [
        "Layanan pengambilan Surat Keterangan Ralat Ijazah Alumni",
        "Konsultasi Permohonan Surat Keterangan Ralat Ijazah",
        "Permintaan Layanan Cek posisi Ijazah Asli Alumni",
        "Permintaan Layanan Scan Ijazah Asli Alumni",
        "Permintaan Cek Data Ijazah pada Situs SIVIL dan PD DIKTI"
    ],

    "Kartu Mahasiswa" => [
        "Layanan Permohonan Cetak Kartu Mahasiswa",
        "Layanan Permohonan Ganti Foto KTM"
    ],

    "Legalisir (Ijazah, Laporan Pendidikan, dll)" => [
        "Permintaan Layanan Legalisir Laporan Perkembangan Pendidikan di Luar Kedinasan",
        "Permintaan Layanan Legalisir Ijazah",
        "Layanan Pengambilan Legalisir Ijazah yang sudah diproses"
    ],

    "Nilai" => [
        "Pengaduan Kendala Nilai Tidak Keluar",
        "Pengaduan Kendala IPK di LKAM berbeda dengan di laman AKSI",
        "Permintaan Layanan Cetak LKAM",
        "Pengaduan Nilai UAS E"
    ],

    "Pengunduran Diri" => [
        "Permintaan Layanan Pengunduran Diri Mahasiswa",
        "Layanan Bantuan Pengajuan Pengunduran Diri Mahasiswa"
    ],

    "Perubahan Data Pribadi" => [
        "Permintaan diaktifkan kembali status DP mahasiswa",
        "Perubahan Data Pribadi Perbaikan penulisan Data Mahasiswa (Nama, NIK, TTL, dll)",
        "Perubahan data Pindah Program Studi (Khusus Mahasiswa Baru belum registrasi)",
        "Pindah UT Daerah :(Pindah ke)"
    ],

    "PDDIKTI" => [
        "Layanan Pengaduan dan Proses Perubahan Kesalahan Penulisan Data pada PDDIKTI",
        "Pengaduan Nama Alumni tidak terdata di PD DIKTI",
        "Pengaduan Kendala status Mahasiswa Non Aktif pada laman DIKTI",
        "Pengaduan Data Tidak sesuai di PD DIKTI"
    ],

    "Praktik/Berpraktikum" => [
        "Layanan Konsultasi terkait Praktikum"
    ],

    "Proposal BTR" => [
        "Pengaduan kendala mahasiswa tidak bisa unggah proposal"
    ],

    "Registrasi Paket Tertinggal" => [
        "Permintaan Layanan Registrasi Paket Tertinggal",
        "Validasi LIP Paket Tertinggal yang telah dilunasi"
    ],

    "Registrasi" => [
        "Permintaan Layanan Registrasi Mata Kuliah",
        "Pengaduan LIP Tagihan Registrasi mata kuliah belum tervalidasi LUNAS",
        "Permintaan Layanan Aktifkan billing Pending/Antrian Registrasi Mata Kuliah",
        "Permintaan Layanan Registrasi TTM/TUWEB Atpem",
        "Permintaan Layanan Perubahan Jenis Layanan Studi"
    ],

    "Tugas Akhir Program Sarjana" => [
        "Permintaan Layanan Pengaduan belum bisa registrasi Tugas Akhir Program Sarjana"
    ],

    "Surat Keterangan" => [
        "Permintaan Layanan Surat Keterangan"
    ],

    "UAS (Ujian Akhir Semester)" => [
        "Permintaan Layanan Cetak Kartu Tanda Peserta Ujian",
        "Permintaan Layanan Numpang Ujian Akhir Mahasiswa",
        "Layanan Informasi Jadwal Ujian Akhir Semester (UAS)",
        "Layanan Permintaan Reschedule Jadwal Ujian Online"
    ],

    "Wisuda" => [
        "Layanan Pendaftaran Kuota Wisuda UT Pusat",
        "Layanan Pendaftaran Wisuda Daerah",
        "Layanan Informasi Cara Daftar Wisuda Daerah",
        "Layanan Informasi Jadwal Wisuda Daerah UT Kupang",
        "Layanan Informasi Permohonan Numpang Wisuda",
        "Layanan Informasi Pengisian Tracer Study (sila.ut.ac.id)",
        "Layanan Cetak LIP Pendaftaran Wisuda belum dikirimkan",
        "Layanan Informasi Alumni Melewatkan batas akhir pembayaran LIP Pendaftaran Wisuda Daerah",
        "Layanan Informasi Tahapan setelah pembayaran LIP Pendaftaran wisuda"
    ],

    "Yudisium" => [
        "Permintaan Layanan Informasi Jadwal Pengumuman SK Yudisium/Kelulusan",
        "Layanan Unggah Pas Foto Ijazah",
        "Layanan Unggah Surat Pernyataan Alumni pada laman AKSI",
        "Layanan Pengaduan Alumni tidak bisa akses laman aksi.ut.ac.id/alumni",
        "Layanan Konsultasi terkait Pas foto ijazah dan Laman Akses unggah Pas Foto",
        "Layanan Konsultasi terkait Pengaduan belum validasi surat pernyataan di laman AKSI",
        "Pengaduan Kendala Belum terjaring Yudisium",
        "Pengaduan kendala gagal melakukan pembayaran LIP IKA",
        "Layanan Informasi Pengisian Survey pada laman AKSI"
    ],

    "Silayar" => [
        "Layanan Bantuan Reset Pass Silayar",
        "Pengaduan Mata Kuliah belum muncul di Laman Silayar"
    ]
];

/*
|--------------------------------------------------------------------------
| TAMBAHKAN PILIHAN "LAINNYA"
|--------------------------------------------------------------------------
*/
foreach ($kategori as $namaKategori => $daftarLayanan) {
    $kategori[$namaKategori][] = "Lainnya";
}

/*
|--------------------------------------------------------------------------
| HITUNG JUMLAH
|--------------------------------------------------------------------------
*/
$totalKategori = count($kategori);

$totalLayanan = 0;

foreach ($kategori as $items) {
    $totalLayanan += count($items);
}

$pengunjung = $_SESSION["pengunjung"] ?? [];
$simpanError = $_SESSION['simpan_error'] ?? '';
unset($_SESSION['simpan_error']);
$statusLabels = [
    "mahasiswa" => "Mahasiswa",
    "alumni" => "Alumni",
    "masyarakat" => "Masyarakat"
];

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pilih Layanan - SALUT</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Style utama -->
    <link
        rel="stylesheet"
        href="assets/style.css"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .form-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,.06);
        }

        .step {
            display: inline-block;
            background: #e9f2ff;
            color: #0d6efd;
            font-size: 12px;
            font-weight: 700;
            padding: 7px 12px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        .page-title {
            margin-bottom: 8px;
            font-weight: 700;
        }

        .page-description {
            margin-bottom: 20px;
        }

        /* =========================
           INFO JUMLAH
        ========================= */

        .service-info {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .info-badge {
            background: #f1f3f5;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13px;
            color: #495057;
        }

        /* =========================
           SEARCH
        ========================= */

        .search-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .search-wrapper .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 17px;
            pointer-events: none;
        }

        #searchService {
            border-radius: 12px;
            padding: 12px 15px 12px 42px;
        }

        /* =========================
           KATEGORI
        ========================= */

        .service-category {
            background: #fff;
            border: 1px solid #e1e5ea;
            border-radius: 14px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: .2s;
        }

        .service-category:hover {
            border-color: #cbd3dc;
        }

        .category-btn {
            width: 100%;
            border: none;
            background: #fff;
            padding: 16px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-align: left;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }

        .category-btn:hover {
            background: #f8faff;
        }

        .category-btn.active {
            background: #f0f5ff;
        }

        .category-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .category-icon {
            width: 34px;
            height: 34px;
            min-width: 34px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #eef4ff;
            border-radius: 9px;
            font-size: 16px;
        }

        .category-name {
            line-height: 1.35;
        }

        .category-count {
            display: block;
            font-size: 11px;
            color: #6c757d;
            font-weight: 400;
            margin-top: 2px;
        }

        .category-arrow {
            transition: .2s;
            font-size: 14px;
            margin-left: 10px;
        }

        .category-btn.active .category-arrow {
            transform: rotate(180deg);
        }

        /* =========================
           SUB LAYANAN
        ========================= */

        .sub-options {
            display: none;
            padding: 4px 12px 14px;
            background: #fafbfc;
        }

        .sub-options.open {
            display: block;
        }

        .sub-btn {
            display: block;
            width: 100%;
            border: 1px solid #e1e5ea;
            background: #fff;
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 8px;
            text-align: left;
            font-size: 14px;
            line-height: 1.5;
            cursor: pointer;
            transition: .2s;
        }

        .sub-btn:hover {
            background: #f0f5ff;
            border-color: #9db5ff;
        }

        .sub-btn.selected {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .sub-btn.other-option {
            border-style: dashed;
        }

        /* =========================
           PILIHAN TERPILIH
        ========================= */

        .selected-box {
            margin-top: 20px;
            padding: 16px 18px;
            border-radius: 12px;
            background: #eef5ff;
            border: 1px solid #cfe0ff;
        }

        .selected-title {
            font-size: 11px;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .selected-text {
            font-weight: 600;
            line-height: 1.5;
        }

        /* =========================
           LAINNYA
        ========================= */

        #otherBox {
            padding: 16px;
            border-radius: 12px;
            background: #fff9e8;
            border: 1px solid #ffe08a;
        }

        #otherBox label {
            font-weight: 600;
            margin-bottom: 8px;
        }

        #lainnya {
            resize: vertical;
            min-height: 100px;
        }

        /* =========================
           TOMBOL
        ========================= */

        .form-actions {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .form-actions .btn {
            min-width: 120px;
            border-radius: 10px;
            padding: 10px 18px;
        }

        /* =========================
           HASIL PENCARIAN
        ========================= */

        .no-result {
            display: none;
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .no-result-icon {
            font-size: 38px;
            margin-bottom: 10px;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 576px) {

            .form-card {
                padding: 20px 15px;
                border-radius: 15px;
            }

            .page-title {
                font-size: 22px;
            }

            .category-btn {
                padding: 14px;
                font-size: 14px;
            }

            .category-icon {
                width: 30px;
                height: 30px;
                min-width: 30px;
            }

            .sub-btn {
                font-size: 13px;
                padding: 11px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<div class="container py-4 py-md-5">

        <div class="user-page-brand">
            <img src="admin/assets/logo-ut.png" alt="Universitas Terbuka">
            <div class="user-page-brand-text">
                <div class="user-page-brand-title">UNIVERSITAS TERBUKA KUPANG</div>
                <div class="user-page-brand-subtitle">Portal Layanan Pengunjung</div>
            </div>
        </div>


    <div class="form-card">

        <!-- =========================
             HEADER
        ========================= -->

<h2 class="page-title">
            Pilih Layanan / Keluhan
        </h2>

        <p class="text-muted page-description">
              
        </p>
        <p class="text-muted page-description">
              
        </p>

        <?php if ($simpanError !== ''): ?>
            <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                <span aria-hidden="true">⚠️</span>
                <div><?= htmlspecialchars($simpanError, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>


        <!-- =========================
             FORM
        ========================= -->

        <form
            action="simpan.php"
            method="post"
            id="serviceForm"
        >

            <!-- Hidden data -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"
            >

            <input
                type="hidden"
                name="layanan"
                id="layanan"
            >

            <input
                type="hidden"
                name="sub_layanan"
                id="sub_layanan"
            >


            <!-- =========================
                 SEARCH
            ========================= -->

            <div class="search-wrapper">

                <span class="search-icon">🔍</span>

                <input
                    type="text"
                    id="searchService"
                    class="form-control"
                    placeholder="Cari layanan atau keluhan..."
                    autocomplete="off"
                >

            </div>


            <!-- =========================
                 DAFTAR KATEGORI
            ========================= -->

            <div id="serviceList">

                <?php foreach ($kategori as $kat => $items): ?>

                    <?php
                        $idKategori = 'sub_' . md5($kat);
                        $jumlahPilihan = count($items);
                    ?>

                    <div
                        class="service-category"
                        data-category="<?= htmlspecialchars(strtolower($kat), ENT_QUOTES, 'UTF-8') ?>"
                    >

                        <!-- HEADER KATEGORI -->

                        <button
                            type="button"
                            class="category-btn"
                            data-target="<?= $idKategori ?>"
                        >

                            <span class="category-left">

                                <span class="category-icon">
                                    📁
                                </span>

                                <span class="category-name">

                                    <?= htmlspecialchars($kat, ENT_QUOTES, 'UTF-8') ?>

                                    <span class="category-count">
                                        <?= $jumlahPilihan ?> pilihan
                                    </span>

                                </span>

                            </span>

                            <span class="category-arrow">
                                ▼
                            </span>

                        </button>


                        <!-- DAFTAR SUB LAYANAN -->

                        <div
                            class="sub-options"
                            id="<?= $idKategori ?>"
                        >

                            <?php foreach ($items as $item): ?>

                                <?php
                                    $isOther = ($item === "Lainnya");
                                ?>

                                <button
                                    type="button"
                                    class="sub-btn <?= $isOther ? 'other-option' : '' ?>"
                                    data-category="<?= htmlspecialchars($kat, ENT_QUOTES, 'UTF-8') ?>"
                                    data-service="<?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>"
                                >

                                    <?= $isOther ? "✏️ " : "› " ?>

                                    <?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>

                                </button>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- =========================
                 TIDAK ADA HASIL
            ========================= -->

            <div
                id="noResult"
                class="no-result"
            >

                <div class="no-result-icon">
                    🔍
                </div>

                <div>
                    Layanan yang dicari tidak ditemukan.
                </div>

                <small>
                    Coba gunakan kata kunci lain.
                </small>

            </div>


            <!-- =========================
                 PILIHAN TERPILIH
            ========================= -->

            <div
                id="selectedBox"
                class="selected-box d-none"
            >

                <div class="selected-title">
                    LAYANAN YANG DIPILIH
                </div>

                <div
                    id="selectedText"
                    class="selected-text"
                ></div>

            </div>
            <div id="serviceError" class="service-error">Silakan pilih layanan terlebih dahulu.</div>


            <!-- =========================
                 LAINNYA
            ========================= -->

            <div
                id="otherBox"
                class="mt-3 d-none"
            >

                <label for="lainnya">

                    Jelaskan layanan / keluhan lainnya

                    <span class="text-danger">
                        *
                    </span>

                </label>

                <textarea
                    name="lainnya"
                    id="lainnya"
                    class="form-control"
                    rows="4"
                    placeholder="Tuliskan kebutuhan atau keluhan Anda..."
                ></textarea>

                <small class="text-muted d-block mt-2">
                    Jelaskan secara singkat agar petugas dapat membantu
                    Anda dengan lebih tepat.
                </small>
                <div id="otherError" class="field-error">Keterangan lainnya wajib diisi.</div>

            </div>


            <!-- =========================
                 RINGKASAN SEBELUM KIRIM
            ========================= -->

            <div id="reviewBox" class="review-box d-none">
                <div class="review-head">
                    <div>
                        <h3 class="review-title">Periksa Data Sebelum Kirim</h3>
                        <p class="review-subtitle">Pastikan data pengunjung dan layanan sudah benar.</p>
                    </div>
                </div>

                <div class="review-grid">
                    <div class="review-item">
                        <span class="review-label">Nama</span>
                        <div class="review-value"><?= htmlspecialchars($pengunjung['nama'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="review-item">
                        <span class="review-label">Status</span>
                        <div class="review-value"><?= htmlspecialchars($statusLabels[$pengunjung['status'] ?? ''] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <?php if (($pengunjung['nim'] ?? '') !== ''): ?>
                    <div class="review-item">
                        <span class="review-label">NIM</span>
                        <div class="review-value"><?= htmlspecialchars($pengunjung['nim'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (($pengunjung['prodi'] ?? '') !== ''): ?>
                    <div class="review-item">
                        <span class="review-label">Program Studi</span>
                        <div class="review-value"><?= htmlspecialchars($pengunjung['prodi'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="review-item">
                        <span class="review-label">Pekerjaan</span>
                        <div class="review-value"><?= htmlspecialchars(ucwords($pengunjung['pekerjaan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="review-item">
                        <span class="review-label">Telepon / HP</span>
                        <div class="review-value"><?= htmlspecialchars($pengunjung['telepon'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <?php if (($pengunjung['email'] ?? '') !== ''): ?>
                    <div class="review-item">
                        <span class="review-label">Email</span>
                        <div class="review-value"><?= htmlspecialchars($pengunjung['email'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="review-item">
                        <span class="review-label">Alamat</span>
                        <div class="review-value"><?= htmlspecialchars($pengunjung['alamat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>

                <div class="review-service">
                    <div class="review-label">Layanan / Keluhan</div>
                    <div id="reviewServiceText" class="review-value">Belum dipilih.</div>
                </div>

                <div class="review-hint">Setelah tombol kirim ditekan, Anda masih akan mendapat konfirmasi terakhir sebelum data disimpan.</div>
            </div>


            <!-- =========================
                 BUTTON
            ========================= -->

            <div class="form-actions">

                <a
                    href="pengunjung.php"
                    class="btn btn-outline-secondary"
                >
                    ← Kembali
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="submitBtn"
                    disabled
                >
                    Kirim →
                </button>

            </div>

        </form>

    </div>


</div>



<div id="confirmModal" class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div class="confirm-dialog">
        <h3 id="confirmTitle">Konfirmasi Pengiriman</h3>
        <p>Periksa sekali lagi data berikut sebelum dikirim ke petugas.</p>

        <div class="review-grid">
            <div class="review-item">
                <span class="review-label">Nama</span>
                <div class="review-value"><?= htmlspecialchars($pengunjung['nama'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="review-item">
                <span class="review-label">Status</span>
                <div class="review-value"><?= htmlspecialchars($statusLabels[$pengunjung['status'] ?? ''] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="review-service">
            <div class="review-label">Layanan / Keluhan</div>
            <div id="modalServiceText" class="review-value">Belum dipilih.</div>
        </div>

        <div class="confirm-actions">
            <button type="button" class="btn btn-outline-secondary" id="cancelConfirm">Kembali Periksa</button>
            <button type="button" class="btn btn-primary" id="confirmSubmit">Ya, Kirim Formulir</button>
        </div>
    </div>
</div>


<script>

/*
|--------------------------------------------------------------------------
| ELEMENT
|--------------------------------------------------------------------------
*/

const serviceForm = document.getElementById('serviceForm');

const layananInput = document.getElementById('layanan');

const subLayananInput = document.getElementById('sub_layanan');

const submitBtn = document.getElementById('submitBtn');

const selectedBox = document.getElementById('selectedBox');

const selectedText = document.getElementById('selectedText');

const otherBox = document.getElementById('otherBox');

const lainnyaInput = document.getElementById('lainnya');

const searchInput = document.getElementById('searchService');

const noResult = document.getElementById('noResult');

const categories = document.querySelectorAll('.service-category');


/*
|--------------------------------------------------------------------------
| BUKA / TUTUP KATEGORI
|--------------------------------------------------------------------------
*/

document.querySelectorAll('.category-btn').forEach(function(button) {

    button.addEventListener('click', function() {

        const targetId = button.dataset.target;

        const target = document.getElementById(targetId);

        if (!target) {
            return;
        }

        target.classList.toggle('open');

        button.classList.toggle('active');

    });

});


/*
|--------------------------------------------------------------------------
| PILIH LAYANAN
|--------------------------------------------------------------------------
*/

document.querySelectorAll('.sub-btn').forEach(function(button) {

    button.addEventListener('click', function() {

        /*
        | Hapus pilihan sebelumnya
        */
        document.querySelectorAll('.sub-btn').forEach(function(item) {

            item.classList.remove('selected');

        });


        /*
        | Tandai pilihan baru
        */
        button.classList.add('selected');


        /*
        | Simpan ke hidden input
        */
        layananInput.value = button.dataset.category;

        subLayananInput.value = button.dataset.service;


        /*
        | Tampilkan pilihan
        */
        selectedText.textContent =
            button.dataset.category +
            " — " +
            button.dataset.service;

        selectedBox.classList.remove('d-none');
        serviceError.classList.remove('show');
        updateReview();


        /*
        | Aktifkan tombol kirim
        */
        submitBtn.disabled = false;


        /*
        | CEK "LAINNYA"
        */
        const isOtherService = button.dataset.service === 'Lainnya';

        if (isOtherService) {

            otherBox.classList.remove('d-none');

            lainnyaInput.required = true;

            setTimeout(function() {
                lainnyaInput.focus();
            }, 100);

        } else {

            otherBox.classList.add('d-none');

            lainnyaInput.required = false;

            lainnyaInput.value = '';

        }

        /* Setelah pilihan dibuat, arahkan otomatis ke bagian bawah
           agar pengunjung langsung melihat lanjutan form dan tombol kirim. */
        setTimeout(function() {
            const target = isOtherService ? otherBox : reviewBox;
            if (target && !target.classList.contains('d-none')) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }, 160);

    });

});


/*
|--------------------------------------------------------------------------
| PENCARIAN
|--------------------------------------------------------------------------
*/

searchInput.addEventListener('input', function() {

    const keyword =
        searchInput.value
            .toLowerCase()
            .trim();

    let found = false;


    categories.forEach(function(category) {

        const categoryName =
            category.dataset.category;

        const buttons =
            category.querySelectorAll('.sub-btn');

        const subOptions =
            category.querySelector('.sub-options');

        const categoryButton =
            category.querySelector('.category-btn');

        let categoryFound = false;


        buttons.forEach(function(button) {

            const serviceText =
                button.textContent
                    .toLowerCase()
                    .trim();

            const cocok =
                keyword === '' ||
                serviceText.includes(keyword) ||
                categoryName.includes(keyword);


            if (cocok) {

                button.style.display = 'block';

                categoryFound = true;

            } else {

                button.style.display = 'none';

            }

        });


        if (categoryFound) {

            category.style.display = 'block';

            found = true;


            /*
            | Saat mencari, buka kategori
            */
            if (keyword !== '') {

                subOptions.classList.add('open');

                categoryButton.classList.add('active');

            }

        } else {

            category.style.display = 'none';

        }

    });


    /*
    |--------------------------------------------------------------------------
    | SEARCH DIKOSONGKAN
    |--------------------------------------------------------------------------
    */

    if (keyword === '') {

        categories.forEach(function(category) {

            category.style.display = 'block';

            category
                .querySelectorAll('.sub-btn')
                .forEach(function(button) {

                    button.style.display = 'block';

                });

        });

        noResult.style.display = 'none';

    } else {

        noResult.style.display =
            found ? 'none' : 'block';

    }

});


/*
|--------------------------------------------------------------------------
| VALIDASI FORM SEBELUM KIRIM
|--------------------------------------------------------------------------
*/

let allowServiceSubmit = false;
const reviewBox = document.getElementById('reviewBox');
const reviewServiceText = document.getElementById('reviewServiceText');
const serviceError = document.getElementById('serviceError');
const otherError = document.getElementById('otherError');
const confirmModal = document.getElementById('confirmModal');
const modalServiceText = document.getElementById('modalServiceText');
const cancelConfirm = document.getElementById('cancelConfirm');
const confirmSubmit = document.getElementById('confirmSubmit');

function updateReview() {
    const layanan = layananInput.value.trim();
    const subLayanan = subLayananInput.value.trim();
    const lainnya = lainnyaInput.value.trim();

    if (!layanan || !subLayanan) {
        reviewBox.classList.add('d-none');
        reviewServiceText.textContent = 'Belum dipilih.';
        modalServiceText.textContent = 'Belum dipilih.';
        return;
    }

    let text = layanan + ' — ' + subLayanan;
    if (subLayanan === 'Lainnya' && lainnya) {
        text += ' — ' + lainnya;
    }

    reviewServiceText.textContent = text;
    modalServiceText.textContent = text;
    reviewBox.classList.remove('d-none');
}

function openConfirmModal() {
    confirmModal.classList.add('show');
    document.body.classList.add('modal-open-user');
}

function closeConfirmModal() {
    confirmModal.classList.remove('show');
    document.body.classList.remove('modal-open-user');
}

serviceForm.addEventListener('submit', function(event) {
    const layanan = layananInput.value.trim();
    const subLayanan = subLayananInput.value.trim();
    const lainnya = lainnyaInput.value.trim();

    if (allowServiceSubmit) {
        return;
    }

    event.preventDefault();

    if (layanan === '' || subLayanan === '') {
        serviceError.textContent = 'Silakan pilih layanan terlebih dahulu.';
        serviceError.classList.add('show');
        return;
    }
    serviceError.classList.remove('show');

    if (subLayanan === 'Lainnya' && lainnya === '') {
        otherError.classList.add('show');
        lainnyaInput.classList.add('is-invalid');
        lainnyaInput.focus();
        return;
    }

    otherError.classList.remove('show');
    lainnyaInput.classList.remove('is-invalid');
    updateReview();
    openConfirmModal();
});

confirmSubmit.addEventListener('click', function() {
    allowServiceSubmit = true;
    confirmSubmit.disabled = true;
    confirmSubmit.textContent = 'Mengirim...';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Mengirim...';
    closeConfirmModal();
    serviceForm.submit();
});

cancelConfirm.addEventListener('click', closeConfirmModal);
confirmModal.addEventListener('click', function(event) {
    if (event.target === confirmModal) closeConfirmModal();
});
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && confirmModal.classList.contains('show')) closeConfirmModal();
});



/*
|--------------------------------------------------------------------------
| JIKA USER MENGETIK "LAINNYA"
|--------------------------------------------------------------------------
*/

lainnyaInput.addEventListener('input', function() {

    if (subLayananInput.value === 'Lainnya') {
        const empty = lainnyaInput.value.trim() === '';
        submitBtn.disabled = empty;
        otherError.classList.toggle('show', empty);
        lainnyaInput.classList.toggle('is-invalid', empty);
    }

    updateReview();
});

/* Simpan sementara pilihan layanan selama 24 jam. */
const userServiceDraft='layanan_universitas_service_draft_v2'; let allowServiceLeave=false;
function saveServiceDraft(){try{localStorage.setItem(userServiceDraft,JSON.stringify({layanan:layananInput.value,sub_layanan:subLayananInput.value,lainnya:lainnyaInput.value,savedAt:Date.now()}));}catch(e){}}
function restoreServiceDraft(){try{const raw=localStorage.getItem(userServiceDraft);if(!raw)return;const d=JSON.parse(raw);if(!d.savedAt||(Date.now()-Number(d.savedAt))>86400000){localStorage.removeItem(userServiceDraft);return;}if(d.layanan&&d.sub_layanan){const btn=Array.from(document.querySelectorAll('.sub-btn')).find(function(el){return el.dataset.category===d.layanan&&el.dataset.service===d.sub_layanan;});if(btn){btn.click();}else{layananInput.value=d.layanan;subLayananInput.value=d.sub_layanan;}}if(d.lainnya)lainnyaInput.value=d.lainnya;updateReview();}catch(e){}}
restoreServiceDraft(); serviceForm.addEventListener('input',saveServiceDraft); serviceForm.addEventListener('change',saveServiceDraft); confirmSubmit.addEventListener('click',function(){allowServiceLeave=true;try{localStorage.removeItem(userServiceDraft);}catch(e){}}); window.addEventListener('beforeunload',function(e){if(allowServiceLeave)return;const has=layananInput.value.trim()||subLayananInput.value.trim()||lainnyaInput.value.trim();if(has){e.preventDefault();e.returnValue='';}});
</script>

</body>
</html>