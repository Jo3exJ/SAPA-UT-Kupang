<?php
session_start();
require_once __DIR__ . '/csrf.php';

date_default_timezone_set('Asia/Makassar');

/*
|--------------------------------------------------------------------------
| PASTIKAN PENGUNJUNG MEMILIH SUMBER LINK
|--------------------------------------------------------------------------
|
| Alur baru mengharuskan pengunjung memilih apakah link didapat
| dari WhatsApp Admin atau langsung dari lokasi universitas.
|
*/
if (!isset($_SESSION['sumber_link'])) {
    header('Location: sumber.php');
    exit;
}

require "koneksi.php";

/* Penerimaan pengunjung selalu terbuka; tidak ada pembatasan jam kerja. */

$errors = [];


/* =========================
   PROSES FORM
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_valid((string)($_POST["csrf_token"] ?? ""))) {
        $errors[] = "Sesi formulir tidak valid. Silakan buka formulir kembali dari awal.";
    } else {

        $tanggal = date("Y-m-d");

        $nama = trim($_POST["nama"] ?? "");
        $status = $_POST["status"] ?? "";
        $nim = trim($_POST["nim"] ?? "");
        $prodi = trim($_POST["prodi"] ?? "");
        $pekerjaan = $_POST["pekerjaan"] ?? "";
        $alamat = trim($_POST["alamat"] ?? "");
        $telepon = trim($_POST["telepon"] ?? "");
        $email = trim($_POST["email"] ?? "");


        /* VALIDASI */

        if ($nama === "") {
            $errors[] = "Nama wajib diisi.";
        }

        if (!in_array(
            $status,
            ["mahasiswa", "alumni", "masyarakat"],
            true
        )) {
            $errors[] = "Status pengunjung tidak valid.";
        }

        if (
            in_array($status, ["mahasiswa", "alumni"], true)
        ) {
            if ($nim === "") {
                $errors[] = "NIM wajib diisi untuk mahasiswa/alumni.";
            } elseif (!preg_match('/^\d{9}$/', $nim)) {
                $errors[] = "NIM harus 9 angka.";
            }
        }

        if (
            in_array($status, ["mahasiswa", "alumni"], true) &&
            $prodi === ""
        ) {
            $errors[] = "Program Studi wajib dipilih untuk mahasiswa/alumni.";
        }

        if ($status === "masyarakat") {
            $nim = "";
            $prodi = "";
        }

        if (!in_array(
            $pekerjaan,
            ["pekerja", "non pekerja"],
            true
        )) {
            $errors[] = "Pekerjaan wajib dipilih.";
        }

        if ($alamat === "") {
            $errors[] = "Alamat lengkap wajib diisi.";
        }

        if ($telepon === "") {
            $errors[] = "Nomor telepon/HP wajib diisi.";
        } else {
            $teleponNormalized = preg_replace('/[^0-9]/', '', $telepon);
            if (!preg_match('/^[0-9]{11,13}$/', $teleponNormalized)) {
                $errors[] = "Nomor telepon/HP harus berupa 11-13 angka.";
            } else {
                $telepon = $teleponNormalized;
            }
        }

        if (
            $email !== "" &&
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $errors[] = "Format email tidak valid.";
        }


        /* SIMPAN KE SESSION */

        if (empty($errors)) {

            $_SESSION["pengunjung"] = [
                "tanggal" => $tanggal,
                "nama" => $nama,
                "status" => $status,
                "nim" => $status === "masyarakat" ? null : $nim,
                "prodi" => $status === "masyarakat" ? null : $prodi,
                "pekerjaan" => $pekerjaan,
                "alamat" => $alamat,
                "telepon" => $telepon,
                "email" => $email,
                "sumber_link" => $_SESSION['sumber_link'] ?? null
            ];

            header("Location: layanan.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Data Pengunjung</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

    <style>
        .status-info {
            border-radius: 12px;
            padding: 15px 18px;
            margin-bottom: 22px;
        }

        .status-aktif {
            background: #eaf7ee;
            border: 1px solid #b7e4c7;
            color: #176b35;
        }

        .status-nonaktif {
            background: #fdecec;
            border: 1px solid #f5b5b5;
            color: #9b1c1c;
        }

        .status-luar-jam {
            background: #fff7df;
            border: 1px solid #f1d37a;
            color: #725500;
        }

        .status-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .status-text {
            margin: 0;
            font-size: 14px;
        }

        label {
            font-weight: 600;
            margin-bottom: 7px;
        }

        .required {
            color: red;
        }

        .optional {
            color: #777;
            font-weight: normal;
        }

        .radio-card {
            cursor: pointer;
        }

        .form-actions.single {
            margin-top: 25px;
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

<h2>Data Pengunjung</h2>




        <!-- INFORMASI FORMULIR -->
        <div class="status-info" style="background:#eef5ff;border:1px solid #c8dcff;color:#174a8b;border-radius:12px;padding:14px 18px;margin-bottom:18px;">
            <div class="status-title" style="font-weight:700;">Silahkan di isi data pengunjung</div>
        </div>


        <!-- PESAN ERROR -->

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">
                <ul class="mb-0">

                    <?php foreach ($errors as $e): ?>

                        <li><?= htmlspecialchars($e) ?></li>

                    <?php endforeach; ?>

                </ul>
            </div>

        <?php endif; ?>


        <!-- FORM -->

        <div class="user-section-title">Identitas & Kontak</div>

        <form method="post" id="visitorForm" novalidate>

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="row g-3">


                <!-- STATUS -->

                <div class="col-12">

                    <label>
                        Datang sebagai
                        <span class="required">*</span>
                    </label>

                    <div class="status-grid">

                        <?php
                        $statusOptions = [
                            "mahasiswa" => "Mahasiswa",
                            "masyarakat" => "Masyarakat",
                            "alumni" => "Alumni"
                        ];

                        foreach ($statusOptions as $value => $label):
                        ?>

                            <label class="radio-card">

                                <input
                                    type="radio"
                                    name="status"
                                    value="<?= $value ?>"
                                    <?= (($_POST["status"] ?? "") === $value) ? "checked" : "" ?>
                                    required
                                >

                                <span><?= htmlspecialchars($label) ?></span>

                            </label>

                        <?php endforeach; ?>

                    </div>
                    <div class="field-error" id="statusError">Silakan pilih status pengunjung.</div>

                </div>



                <!-- TANGGAL -->

                <div class="col-md-6">

                    <label>Tanggal</label>

                    <input
                        type="date"
                        name="tanggal"
                        class="form-control"
                        value="<?= date("Y-m-d") ?>"
                        readonly
                    >

                </div>


                <!-- NAMA -->

                <div class="col-md-6">

                    <label>
                        Nama
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="nama"
                        id="nama"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($_POST["nama"] ?? "") ?>"
                    >
                    <div class="field-error" id="namaError">Nama wajib diisi.</div>

                </div>


                <!-- NIM -->

                <div class="col-md-6 conditional-person">

                    <label>
                        NIM
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="nim"
                        id="nim"
                        class="form-control"
                        inputmode="numeric"
                        autocomplete="off"
                        minlength="9"
                        maxlength="9"
                        pattern="[0-9]{9}"
                        title="NIM harus terdiri dari tepat 9 angka"
                        value="<?= htmlspecialchars($_POST["nim"] ?? "") ?>"
                    >
                    <div class="form-text">Masukkan NIM 9 angka.</div>
                    <div class="form-text" id="nimLookupMessage" aria-live="polite"></div>
                    <div class="field-error" id="nimError">NIM harus 9 angka.</div>

                </div>


                <!-- PROGRAM STUDI -->

                <div class="col-md-6 conditional-person">

                    <label>
                        Program Studi
                        <span class="required">*</span>
                    </label>

                    <select
                        name="prodi"
                        id="prodi"
                        class="form-select"
                    >

                        <option value="">
                            -- Pilih Program Studi --
                        </option>

                        <?php
                        $daftarProdi = [
                            "D3 Perpajakan",
                            "D4 Kearsipan",
                            "D3 Teknologi Informasi",
                            "D4 Akutansi Digital Bisnis",
                            "D4 Manajemen Logistik",
                            "D3 Penyiaran",
                            "Manajemen",
                            "Ekonomi Pembangunan",
                            "Ekonomi Syairah",
                            "Akutansi keuangan publik",
                            "Akutansi",
                            "PJJ Pariwisata",
                            "Kewirausahaan",
                            "Statistika",
                            "Matematika",
                            "Biologi",
                            "Teknologi Pangan",
                            "Agribisnis",
                            "Perencanaan Wilayah dan Kota",
                            "Sistem Informasi",
                            "Sains Data",
                            "Pendidikan Bahasa dan Sastra Indonesia",
                            "Pendidikan Bahasa Inggris",
                            "Pendidikan Biologi",
                            "Pendidikan Kimia",
                            "Pendidikan Matematika",
                            "Pendidikan Ekonomi",
                            "Pendidikan Pancasila dan Kewarga Negaraan",
                            "Teknologi Pendidikan",
                            "Pendidikan Guru sekolah dasar",
                            "Pendidikan Guru Pendidikan Anak Usia Dini",
                            "Program Pendidikan Profesi Guru",
                            "Pendidikan Agama Islam",
                            "Ilmu Administrasi Negara",
                            "Ilmu Administrasi Bisnis",
                            "Ilmu Hukum",
                            "Ilmu Pemerintahan",
                            "Ilmu Komunikasi",
                            "Ilmu Perpustakaan",
                            "Sosiologi",
                            "Sastra Inggris",
                            "Perpajakan",
                            "Magister Akutansi",
                            "Magister Studi Lingkungan",
                            "Magister Manajemen Perikanan",
                            "Magister Ilmu Administrasi Publik",
                            "Magister Manajemen",
                            "Magister Pendidikan Dasar",
                            "Magister Pendidikan Matematika",
                            "Magister Pendidikan Bahasa inggris",
                            "Magister Pendidikan Anak Usia Dini",
                            "Magister Hukum",
                            "Doktor Ilmu Manajemen",
                            "Doktor Administrasi Publik"
                        ];

                        $prodiDipilih = $_POST["prodi"] ?? "";

                        foreach ($daftarProdi as $prodiItem):
                        ?>

                            <option
                                value="<?= htmlspecialchars($prodiItem) ?>"
                                <?= $prodiDipilih === $prodiItem ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($prodiItem) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                    <div class="field-error" id="prodiError">Program Studi wajib dipilih.</div>

                </div>


                <!-- PEKERJAAN -->

                <div class="col-md-6">

                    <label>
                        Pekerjaan
                        <span class="required">*</span>
                    </label>

                    <select
                        name="pekerjaan"
                        class="form-select"
                        required
                    >

                        <option value="">-- Pilih --</option>

                        <option
                            value="pekerja"
                            <?= ($_POST["pekerjaan"] ?? "") === "pekerja" ? "selected" : "" ?>
                        >
                            Pekerja
                        </option>

                        <option
                            value="non pekerja"
                            <?= ($_POST["pekerjaan"] ?? "") === "non pekerja" ? "selected" : "" ?>
                        >
                            Non Pekerja
                        </option>

                    </select>

                </div>


                <!-- ALAMAT -->

                <div class="col-12">

                    <label>
                        Alamat Lengkap
                        <span class="required">*</span>
                    </label>

                    <textarea
                        name="alamat"
                        id="alamat"
                        class="form-control"
                        rows="3"
                        required
                    ><?= htmlspecialchars($_POST["alamat"] ?? "") ?></textarea>
                    <div class="field-error" id="alamatError">Alamat lengkap wajib diisi.</div>

                </div>


                <!-- TELEPON -->

                <div class="col-md-6">

                    <label>
                        No. Telepon / HP
                        <span class="required">*</span>
                    </label>

                    <input
                        type="tel"
                        name="telepon"
                        id="telepon"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($_POST["telepon"] ?? "") ?>"
                    >
                    <div class="field-error" id="teleponError">Nomor telepon/HP harus berupa 11-13 angka.</div>

                </div>


                <!-- EMAIL -->

                <div class="col-md-6">

                    <label>
                        Email
                        <span class="optional">(opsional)</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                    >
                    <div class="field-error" id="emailError">Format email tidak valid.</div>

                </div>

            </div>


            <!-- TOMBOL -->

            <div class="form-actions single">

                <button
                    type="submit"
                    class="btn btn-primary px-4"
                >
                    Selanjutnya →
                </button>

            </div>

        </form>

    </div>


</div>


<script>

function updateConditional() {

    const status = document.querySelector(
        'input[name="status"]:checked'
    )?.value;

    const boxes = document.querySelectorAll(
        ".conditional-person"
    );

    const nim = document.getElementById("nim");
    const prodi = document.getElementById("prodi");

    const show =
        status === "mahasiswa" ||
        status === "alumni";

    boxes.forEach(function (box) {
        box.style.display = show ? "" : "none";
    });

    nim.required = show;
    prodi.required = show;

    if (!show) {
        nim.value = "";
        prodi.value = "";
    }
}


const nimInput = document.getElementById("nim");

if (nimInput) {
    nimInput.addEventListener("input", function () {
        this.value = this.value.replace(/\D/g, "").slice(0, 9);
    });
}

// Cari data mahasiswa otomatis setelah NIM lengkap 9 angka.
let nimLookupTimer = null;
let lastLookedUpNim = '';
const nimLookupMessage = document.getElementById('nimLookupMessage');

function showNimLookupMessage(message, isError = false) {
    if (!nimLookupMessage) return;
    nimLookupMessage.textContent = message;
    nimLookupMessage.style.color = isError ? '#b02a37' : '#52677d';
}

async function lookupMahasiswa(nim) {
    if (!/^\d{9}$/.test(nim) || nim === lastLookedUpNim) return;
    lastLookedUpNim = nim;
    showNimLookupMessage('Mencari data...');
    try {
        const response = await fetch('cari_mahasiswa.php?nim=' + encodeURIComponent(nim), {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });
        const result = await response.json();
        // Abaikan respons lama jika NIM sudah diganti saat pencarian berlangsung.
        if (nimInput.value.trim() !== nim) return;
        if (result.success && result.data) {
            const namaField = document.getElementById('nama');
            const alamatField = document.getElementById('alamat');
            const prodiField = document.getElementById('prodi');
            namaField.value = result.data.nama || '';
            alamatField.value = result.data.alamat || '';
            if (prodiField && result.data.prodi) {
                let option = Array.from(prodiField.options).find(opt => opt.value === result.data.prodi);
                if (!option) {
                    option = new Option(result.data.prodi, result.data.prodi, true, true);
                    prodiField.add(option);
                }
                prodiField.value = result.data.prodi;
            }
            [namaField, alamatField].forEach(field => field.dispatchEvent(new Event('input', { bubbles: true })));
            if (prodiField) prodiField.dispatchEvent(new Event('change', { bubbles: true }));
            showNimLookupMessage('Data ditemukan.');
        } else if (result.not_found) {
            showNimLookupMessage('NIM tidak ditemukan. Isi data manual.');
        } else {
            showNimLookupMessage('Pencarian gagal. Isi data manual.', true);
        }
    } catch (error) {
        if (nimInput.value.trim() === nim) showNimLookupMessage('Pencarian gagal. Isi data manual.', true);
    }
}

if (nimInput) {
    nimInput.addEventListener('input', function () {
        clearTimeout(nimLookupTimer);
        lastLookedUpNim = '';
        if (this.value.length < 9) {
            showNimLookupMessage('');
            return;
        }
        const nim = this.value.trim();
        nimLookupTimer = setTimeout(() => lookupMahasiswa(nim), 250);
    });
}


updateConditional();

const visitorForm = document.getElementById('visitorForm');

function setError(input, errorId, message, show) {
    if (input) {
        input.classList.toggle('is-invalid', show);
    }
    const box = document.getElementById(errorId);
    if (box) {
        if (message) box.textContent = message;
        box.classList.toggle('show', show);
    }
}

function validateVisitor(showAll = true) {
    let ok = true;
    let firstInvalid = null;

    const nama = document.getElementById('nama');
    const status = document.querySelector('input[name="status"]:checked')?.value || '';
    const nim = document.getElementById('nim');
    const prodi = document.getElementById('prodi');
    const pekerjaan = document.querySelector('select[name="pekerjaan"]');
    const alamat = document.getElementById('alamat');
    const telepon = document.getElementById('telepon');
    const email = document.getElementById('email');
    const statusGrid = document.querySelector('.status-grid');

    const fail = (input, errorId, message) => {
        ok = false;
        setError(input, errorId, message, true);
        if (!firstInvalid) firstInvalid = input;
    };

    if (!nama.value.trim()) fail(nama, 'namaError', 'Nama wajib diisi.');
    else setError(nama, 'namaError', '', false);

    if (!status) {
        ok = false;
        document.getElementById('statusError')?.classList.add('show');
        statusGrid?.classList.add('is-invalid-group');
        if (!firstInvalid) firstInvalid = document.querySelector('input[name="status"]');
    } else {
        document.getElementById('statusError')?.classList.remove('show');
        statusGrid?.classList.remove('is-invalid-group');
    }

    const needPerson = status === 'mahasiswa' || status === 'alumni';
    if (needPerson) {
        if (!/^\d{9}$/.test(nim.value.trim())) fail(nim, 'nimError', 'NIM harus 9 angka.');
        else setError(nim, 'nimError', '', false);

        if (!prodi.value.trim()) fail(prodi, 'prodiError', 'Program Studi wajib dipilih.');
        else setError(prodi, 'prodiError', '', false);
    } else {
        setError(nim, 'nimError', '', false);
        setError(prodi, 'prodiError', '', false);
    }

    if (!pekerjaan.value) fail(pekerjaan, 'pekerjaanError', 'Pekerjaan wajib dipilih.');
    else setError(pekerjaan, 'pekerjaanError', '', false);

    if (!alamat.value.trim()) fail(alamat, 'alamatError', 'Alamat lengkap wajib diisi.');
    else setError(alamat, 'alamatError', '', false);

    const phoneValue = telepon.value.replace(/[^0-9]/g, '');
    if (!/^[0-9]{11,13}$/.test(phoneValue)) fail(telepon, 'teleponError', 'Nomor telepon/HP harus berupa 11-13 angka.');
    else setError(telepon, 'teleponError', '', false);

    if (email.value.trim() !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) fail(email, 'emailError', 'Format email tidak valid.');
    else setError(email, 'emailError', '', false);

    if (showAll && firstInvalid) {
        firstInvalid.focus();
    }
    return ok;
}

const namaInput = document.getElementById('nama');
const alamatInput = document.getElementById('alamat');
const teleponInput = document.getElementById('telepon');
const emailInput = document.getElementById('email');
const prodiInput = document.getElementById('prodi');

if (namaInput) {
    namaInput.addEventListener('input', function() {
        setError(namaInput, 'namaError', '', !namaInput.value.trim());
    });
}

if (alamatInput) {
    alamatInput.addEventListener('input', function() {
        setError(alamatInput, 'alamatError', '', !alamatInput.value.trim());
    });
}

if (teleponInput) {
    teleponInput.addEventListener('input', function() {
        const value = teleponInput.value.replace(/[^0-9]/g, '').slice(0, 13);
        teleponInput.value = value;
        const invalid = !/^[0-9]{11,13}$/.test(value);
        setError(teleponInput, 'teleponError', 'Nomor telepon/HP harus berupa 11-13 angka.', invalid);
    });
}

if (emailInput) {
    emailInput.addEventListener('input', function() {
        const value = emailInput.value.trim();
        const invalid = value !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        setError(emailInput, 'emailError', 'Format email tidak valid.', invalid);
    });
}

if (nimInput) {
    nimInput.addEventListener('blur', function() {
        const status = document.querySelector('input[name="status"]:checked')?.value || '';
        if (status === 'mahasiswa' || status === 'alumni') {
            const invalid = !/^\d{9}$/.test(nimInput.value.trim());
            setError(nimInput, 'nimError', 'NIM harus 9 angka.', invalid);
        }
    });
}

if (prodiInput) {
    prodiInput.addEventListener('change', function() {
        setError(prodiInput, 'prodiError', 'Program Studi wajib dipilih.', !prodiInput.value.trim());
    });
}

document.querySelectorAll('input[name="status"]').forEach(function(el) {
    el.addEventListener('change', function() {
        document.getElementById('statusError')?.classList.remove('show');
        document.querySelector('.status-grid')?.classList.remove('is-invalid-group');
        updateConditional();
    });
});

const pekerjaanInput = document.querySelector('select[name="pekerjaan"]');
if (pekerjaanInput) {
    pekerjaanInput.addEventListener('change', function() {
        setError(pekerjaanInput, 'pekerjaanError', 'Pekerjaan wajib dipilih.', !pekerjaanInput.value);
    });
}

if (visitorForm) {
    visitorForm.addEventListener('submit', function (event) {
        if (!validateVisitor(true)) {
            event.preventDefault();
            return;
        }

        const btn = visitorForm.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Memproses...';
        }
    });
}

/* Simpan sementara isi form di browser selama 24 jam. */
const userVisitorDraft='layanan_universitas_visitor_draft_v2'; let allowVisitorLeave=false;
function saveVisitorDraft(){if(!visitorForm)return;const d={savedAt:Date.now()};visitorForm.querySelectorAll('input[name],select[name],textarea[name]').forEach(el=>{if(!el.name||el.name==='csrf_token'||el.name==='tanggal')return;if(el.type==='radio'){if(el.checked)d[el.name]=el.value;}else d[el.name]=el.value;});try{localStorage.setItem(userVisitorDraft,JSON.stringify(d));}catch(e){}}
function restoreVisitorDraft(){if(!visitorForm)return;try{const raw=localStorage.getItem(userVisitorDraft);if(!raw)return;const d=JSON.parse(raw);if(!d.savedAt||(Date.now()-Number(d.savedAt))>86400000){localStorage.removeItem(userVisitorDraft);return;}Object.keys(d).forEach(n=>{if(n==='savedAt')return;visitorForm.querySelectorAll('[name="'+CSS.escape(n)+'"]').forEach(el=>{if(el.type==='radio')el.checked=el.value===d[n];else if(!el.value)el.value=d[n]??'';});});updateConditional();}catch(e){}}
restoreVisitorDraft(); if(visitorForm){visitorForm.querySelectorAll('input[name],select[name],textarea[name]').forEach(el=>{if(el.name==='csrf_token'||el.name==='tanggal')return;el.addEventListener('input',saveVisitorDraft);el.addEventListener('change',saveVisitorDraft);});visitorForm.addEventListener('submit',function(){if(!validateVisitor(true))return;allowVisitorLeave=true;try{localStorage.removeItem(userVisitorDraft);}catch(e){}},true);}
window.addEventListener('beforeunload',function(e){if(!visitorForm||allowVisitorLeave)return;const has=Array.from(visitorForm.querySelectorAll('input[name],select[name],textarea[name]')).some(el=>el.name!=='csrf_token'&&el.name!=='tanggal'&&(el.type==='radio'?el.checked:String(el.value||'').trim()!==''));if(has){e.preventDefault();e.returnValue='';}});
</script>

</body>
</html>