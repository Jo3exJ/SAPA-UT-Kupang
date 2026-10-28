<?php

$title = 'Tambah Admin';
require 'header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid((string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Sesi keamanan tidak valid. Silakan muat ulang halaman lalu coba lagi.';
    }

    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if ($error !== '') {
        // Jangan lanjutkan proses database jika token tidak valid.
    } elseif ($nama === '' || $username === '' || $password === '') {

        $error = 'Semua data wajib diisi.';

    } elseif ($password !== $konfirmasi) {

        $error = 'Konfirmasi password tidak sama.';

    } elseif (strlen($password) < 6) {

        $error = 'Password minimal 6 karakter.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | CEK USERNAME
        |--------------------------------------------------------------------------
        */

        $cek = mysqli_prepare(
            $conn,
            "SELECT id_admin
             FROM admins
             WHERE username = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $cek,
            's',
            $username
        );

        mysqli_stmt_execute($cek);

        $hasilCek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasilCek) > 0) {

            $error = 'Username tersebut sudah digunakan.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | HASH PASSWORD
            |--------------------------------------------------------------------------
            */

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /*
            |--------------------------------------------------------------------------
            | SIMPAN ADMIN
            |--------------------------------------------------------------------------
            */

            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO admins
                    (
                        nama,
                        username,
                        password,
                        aktif,
                        created_at
                    )
                 VALUES
                    (?, ?, ?, 1, NOW())"
            );

            mysqli_stmt_bind_param(
                $insert,
                'sss',
                $nama,
                $username,
                $passwordHash
            );


            if (mysqli_stmt_execute($insert)) {

                catatAktivitas($conn,'Menambah admin','Menambahkan akun petugas: '.$nama.' (@'.$username.')');

                $success =
                    'Admin berhasil ditambahkan.';

                /*
                | Kosongkan input setelah berhasil
                */

                $nama = '';
                $username = '';

            } else {

                $error =
                    'Admin gagal ditambahkan: '
                    . mysqli_error($conn);
            }
        }
    }
}

?>

<style>

/* =========================================================
   TAMBAH ADMIN
========================================================= */

.admin-form-card {
    max-width: 650px;
    background: #ffffff;
    border: 1px solid #e5eaf0;
    border-radius: 14px;
    padding: 25px;
    box-shadow: 0 4px 18px rgba(0,0,0,.05);
}

.admin-form-title {
    margin-bottom: 5px;
    color: #003b7a;
}

.admin-form-description {
    color: #718096;
    margin-bottom: 25px;
}

.password-info {
    font-size: 12px;
    color: #718096;
    margin-top: 5px;
}

</style>


<!-- =========================================================
     HEADER
========================================================= -->

<div class="mb-4">

    <h2>
        ➕ Tambah Admin / Petugas
    </h2>

    <p class="text-muted mb-0">
        Tambahkan akun petugas baru untuk mengakses sistem.
    </p>

</div>


<!-- =========================================================
     NOTIFIKASI
========================================================= -->

<?php if ($error !== ''): ?>

    <div class="alert alert-danger">
        ⚠️
        <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>


<?php if ($success !== ''): ?>

    <div class="alert alert-success">
        ✅
        <?= htmlspecialchars($success) ?>
    </div>

<?php endif; ?>


<!-- =========================================================
     FORM
========================================================= -->

<div class="admin-form-card">

    <h5 class="admin-form-title">
        Data Akun
    </h5>

    <p class="admin-form-description">
        Isi data akun petugas yang akan digunakan untuk login.
    </p>


    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">


        <!-- NAMA -->

        <div class="mb-3">

            <label class="form-label">
                Nama Petugas
            </label>

            <input
                type="text"
                name="nama"
                class="form-control"
                value="<?= htmlspecialchars($nama ?? '') ?>"
                placeholder="Masukkan nama petugas"
                required
            >

        </div>


        <!-- USERNAME -->

        <div class="mb-3">

            <label class="form-label">
                Username
            </label>

            <input
                type="text"
                name="username"
                class="form-control"
                value="<?= htmlspecialchars($username ?? '') ?>"
                placeholder="Masukkan username"
                required
            >

        </div>


        <!-- PASSWORD -->

        <div class="mb-3">

            <label class="form-label">
                Password
            </label>

            <input
                type="password"
                name="password"
                id="password"
                class="form-control"
                placeholder="Masukkan password"
                minlength="6"
                required
            >

            <div class="password-info">
                Password minimal 6 karakter.
            </div>

        </div>


        <!-- KONFIRMASI PASSWORD -->

        <div class="mb-4">

            <label class="form-label">
                Konfirmasi Password
            </label>

            <input
                type="password"
                name="konfirmasi_password"
                id="konfirmasi_password"
                class="form-control"
                placeholder="Ulangi password"
                minlength="6"
                required
            >

        </div>


        <!-- TOMBOL -->

        <div class="d-flex gap-2">

            <button
                type="submit"
                class="btn btn-primary"
            >
                ➕ Tambahkan Admin
            </button>

            <a
                href="admin.php"
                class="btn btn-outline-secondary"
            >
                Batal
            </a>

        </div>

    </form>

</div>


<script>

const password =
    document.getElementById('password');

const konfirmasi =
    document.getElementById('konfirmasi_password');

function cekPassword() {

    if (
        konfirmasi.value !== '' &&
        password.value !== konfirmasi.value
    ) {

        konfirmasi.setCustomValidity(
            'Konfirmasi password tidak sama.'
        );

    } else {

        konfirmasi.setCustomValidity('');

    }

}

password.addEventListener(
    'input',
    cekPassword
);

konfirmasi.addEventListener(
    'input',
    cekPassword
);

</script>


<?php require 'footer.php'; ?>