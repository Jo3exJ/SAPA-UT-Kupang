<?php
session_start();
require '../koneksi.php';
require_once __DIR__ . '/../schema_bootstrap.php';
$schemaReady = ensureAppSchema($conn);
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/log_helper.php';

set_exception_handler(function (Throwable $e) {
    if ($e instanceof mysqli_sql_exception) {
        $_SESSION['system_error'] = 'Sistem mengalami kendala saat mengakses database. Jalankan migrasi database terbaru lalu coba lagi.';
        header('Location: error.php');
        exit;
    }
    throw $e;
});

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$e = $schemaReady ? '' : 'Struktur database belum lengkap. Jalankan database_update_kjj.sql, atau perbarui database terlebih dahulu.';

$now = time();
$window = 10 * 60;
$maxAttempts = 5;

if (
    !isset($_SESSION['login_attempt_started']) ||
    ($now - (int)$_SESSION['login_attempt_started']) > $window
) {
    $_SESSION['login_attempt_started'] = $now;
    $_SESSION['login_attempts'] = 0;
}

$loginBlocked = ((int)($_SESSION['login_attempts'] ?? 0) >= $maxAttempts);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$schemaReady) {
        // Pesan sudah ditampilkan di halaman login.
    } elseif (!csrf_valid((string)($_POST['csrf_token'] ?? ''))) {
        $e = 'Sesi keamanan tidak valid. Silakan muat ulang halaman login.';
    } elseif ($loginBlocked) {
        $sisa = max(1, $window - ($now - (int)$_SESSION['login_attempt_started']));
        $menit = max(1, (int)ceil($sisa / 60));
        $e = "Terlalu banyak percobaan login. Coba lagi sekitar {$menit} menit lagi.";
    } else {

        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';

    $s = mysqli_prepare(
        $conn,
        'SELECT id_admin, nama, password
         FROM admins
         WHERE username = ?
         AND aktif = 1
         LIMIT 1'
    );

    mysqli_stmt_bind_param($s, 's', $u);
    mysqli_stmt_execute($s);

    $result = mysqli_stmt_get_result($s);
    $a = mysqli_fetch_assoc($result);

        if ($a && password_verify($p, $a['password'])) {

            session_regenerate_id(true);
            $_SESSION['login_attempts'] = 0;
            $_SESSION['login_attempt_started'] = time();

            /* Perbarui hash bila algoritma/parameter password berubah. */
            if (password_needs_rehash($a['password'], PASSWORD_DEFAULT)) {
                $hashBaru = password_hash($p, PASSWORD_DEFAULT);
                $uHash = mysqli_prepare($conn, 'UPDATE admins SET password=? WHERE id_admin=?');
                mysqli_stmt_bind_param($uHash, 'si', $hashBaru, $a['id_admin']);
                mysqli_stmt_execute($uHash);
            }

            $_SESSION['admin_id'] = $a['id_admin'];
            $_SESSION['admin_nama'] = $a['nama'];

            $loginUpdate = mysqli_prepare($conn, 'UPDATE admins SET last_login=NOW() WHERE id_admin=?');
            if ($loginUpdate) { mysqli_stmt_bind_param($loginUpdate, 'i', $a['id_admin']); @mysqli_stmt_execute($loginUpdate); @mysqli_stmt_close($loginUpdate); }
            catatAktivitas($conn, 'Login admin', 'Login berhasil sebagai ' . $a['nama']);

            header('Location: dashboard.php');
            exit;
        }

        $_SESSION['login_attempts'] = (int)($_SESSION['login_attempts'] ?? 0) + 1;
        $e = 'Username atau password salah.';
    }
}

$loginBlocked = ((int)($_SESSION['login_attempts'] ?? 0) >= $maxAttempts);
?>

<!doctype html>
<html lang="id">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Login Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/admin.css"
    >

    <style>

        /* =====================================================
           LOGIN ADMIN
        ===================================================== */

        body.login {
            margin: 0;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #f3f6fb;

            font-family:
                system-ui,
                -apple-system,
                "Segoe UI",
                sans-serif;
        }


        /* Kotak login */

        .loginbox {
            width: 100%;
            max-width: 420px;

            background: #ffffff;

            padding: 35px;

            border-radius: 16px;

            border: 1px solid #e1e6ef;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.08);
        }


        /* Logo / judul */

        .loginbox .login-logo {
            width: 75px;
            height: 75px;

            object-fit: contain;

            display: block;

            margin: 0 auto 15px;
        }


        .loginbox h3 {
            text-align: center;

            color: #063b8f;

            font-weight: 700;

            margin-bottom: 6px;
        }


        .loginbox .subtitle {
            text-align: center;

            color: #6c757d;

            margin-bottom: 28px;
        }


        /* Label */

        .loginbox label {
            display: block;

            font-weight: 600;

            color: #183b68;

            margin-bottom: 7px;
        }


        /* Input */

        .loginbox .form-control {
            min-height: 46px;

            border-radius: 8px;

            border: 1px solid #d6dce5;
        }


        .loginbox .form-control:focus {
            border-color: #0d6efd;

            box-shadow:
                0 0 0 3px rgba(13, 110, 253, 0.12);
        }


        /* Tombol */

        .loginbox .btn-login {
            width: 100%;

            min-height: 46px;

            border: none;

            border-radius: 8px;

            background: #063b8f;

            color: #ffffff;

            font-weight: 600;

            margin-top: 8px;

            transition: 0.2s;
        }


        .loginbox .btn-login:hover {
            background: #052f72;
        }


        /* Garis kuning UT */

        .login-line {
            width: 55px;
            height: 4px;

            background: #ffc400;

            border-radius: 10px;

            margin: 0 auto 25px;
        }


        /* Error */

        .loginbox .alert {
            border-radius: 8px;

            font-size: 14px;
        }


        /* =====================================================
           RESPONSIVE HP
        ===================================================== */

        @media (max-width: 576px) {

            body.login {
                padding: 20px;
            }

            .loginbox {
                max-width: 100%;

                padding: 28px 22px;

                border-radius: 14px;
            }

            .loginbox h3 {
                font-size: 24px;
            }

        }

    </style>

</head>


<body class="login">


    <div class="loginbox">


        <!-- LOGO -->
        <div class="text-center">

            <!--
                Kalau nanti logo UT sudah ada,
                ganti src dengan lokasi logo kamu.
            -->

            <div
                style="
                    font-size: 52px;
                    line-height: 1;
                    margin-bottom: 12px;
                "
            >
                🎓
            </div>

        </div>


        <!-- JUDUL -->

        <h3>
            Login Admin
        </h3>

        <p class="subtitle">
            Panel Layanan Universitas
        </p>

        <div class="login-line"></div>


        <!-- ERROR -->

        <?php if ($e): ?>

            <div class="alert alert-danger">

                <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <form method="post">

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

            <!-- USERNAME -->

            <div class="mb-3">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    autocomplete="username"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="mb-3">

                <label for="password">
                    Password
                </label>

                <div class="input-group">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password" aria-label="Tampilkan password" aria-pressed="false">Tampilkan</button>
                </div>

            </div>


            <!-- TOMBOL -->

            <button
                type="submit"
                class="btn-login"
                <?= $loginBlocked ? 'disabled' : '' ?>
            >
                <?= $loginBlocked ? 'Login sementara dikunci' : 'Masuk' ?>
            </button>


        </form>


    </div>

<script>
document.querySelectorAll('.toggle-password').forEach(function(button) {
    button.addEventListener('click', function() {
        const input = document.getElementById(button.dataset.target);
        const tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        button.textContent = tampil ? 'Sembunyikan' : 'Tampilkan';
        button.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
        button.setAttribute('aria-pressed', tampil ? 'true' : 'false');
    });
});
</script>

</body>

</html>