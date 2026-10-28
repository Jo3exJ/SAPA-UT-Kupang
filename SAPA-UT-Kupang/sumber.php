<?php
session_start();
require_once __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid((string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        exit('Permintaan tidak valid. Silakan buka halaman formulir dari awal.');
    }

    $sumber = $_POST['sumber'] ?? '';

    if (!in_array($sumber, ['wa_admin', 'lokasi_universitas'], true)) {
        header('Location: sumber.php');
        exit;
    }

    $_SESSION['sumber_link'] = $sumber;

    header('Location: pengunjung.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sumber Link - Layanan Universitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .source-card {
            max-width: 850px;
            margin: 30px auto;
            padding: 34px;
            background: #fff;
            border-radius: 22px;
            border: 1px solid #e9edf3;
            box-shadow: 0 12px 35px rgba(0, 0, 0, .08);
        }
        .source-title {
            font-weight: 700;
            color: #17324d;
        }
        .source-text {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        /* Kartu pilihan tetap berada di dalam kotak sumber yang sudah ada. */
        .source-option-card {
            position: relative;
            height: 100%;
            min-height: 215px;
            padding: 48px 22px 22px;
            border: 1px solid #edf0f4;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(20, 35, 55, .08);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .source-icon {
            width: 80px;
            height: 80px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: -1px;
            left: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 5px 12px rgba(0, 0, 0, .12);
        }
        .source-icon svg { width: 43px; height: 43px; display: block; }
        .source-icon-wa { color: #fff; background: #20a957; }
        .source-icon-ut { background: #0056d1; }
        .source-icon-ut img { width: 62px; height: 62px; object-fit: contain; display: block; }
        .source-option-card h3 {
            color: #172b3d;
            font-size: 17px;
            line-height: 1.35;
            font-weight: 700;
            margin: 7px 0 5px;
        }
        .source-option-card p {
            color: #9aa3ad;
            font-size: 13px;
            line-height: 1.5;
            margin: 0 0 22px;
            flex: 1;
        }
        .source-submit {
            border: 0;
            border-radius: 5px;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            width: 100%;
            max-width: 230px;
            min-height: 39px;
            padding: 10px 18px;
            transition: filter .2s ease, transform .2s ease;
        }
        .source-submit:hover { filter: brightness(.94); transform: translateY(-1px); }
        .source-submit-wa { background: #20a957; }
        .source-submit-ut { background: #0056d1; }
        .source-submit:focus-visible { outline: 3px solid rgba(13, 110, 253, .25); outline-offset: 3px; }
        @media (max-width: 600px) {
            .source-card {
                margin: 10px auto;
                padding: 22px 16px;
                border-radius: 16px;
            }
            .source-option-card {
                min-height: 205px;
                padding: 44px 16px 18px;
            }
            .source-icon {
                width: 72px;
                height: 72px;
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

    <div class="source-card">
        <h2 class="source-title">Dari mana Anda mendapatkan link ini?</h2>
        <p>.</p>
        <p>.</p>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="source-option-card">
                        <span class="source-icon source-icon-wa" aria-hidden="true">
                            <svg viewBox="0 0 32 32" role="img" aria-label="">
                                <path fill="currentColor" d="M16.02 3C8.85 3 3.02 8.78 3.02 15.9c0 2.27.6 4.49 1.74 6.45L3 29l6.86-1.78a13.1 13.1 0 0 0 6.16 1.55h.01c7.17 0 13-5.78 13-12.9C29.03 8.75 23.2 3 16.02 3zm0 23.55h-.01c-1.9 0-3.77-.51-5.4-1.47l-.39-.23-4.07 1.06 1.09-3.94-.25-.4a10.63 10.63 0 0 1-1.64-5.67c0-5.88 4.8-10.66 10.68-10.66 2.85 0 5.53 1.1 7.54 3.12a10.58 10.58 0 0 1 3.12 7.54c0 5.88-4.79 10.65-10.67 10.65zm5.86-7.98c-.32-.16-1.9-.94-2.2-1.05-.3-.11-.51-.16-.73.16-.22.32-.84 1.05-1.03 1.27-.19.21-.38.24-.7.08-.32-.16-1.36-.5-2.59-1.59-.96-.85-1.61-1.9-1.8-2.22-.19-.32-.02-.49.14-.65.14-.14.32-.38.48-.57.16-.19.21-.32.32-.54.11-.21.05-.4-.03-.56-.08-.16-.73-1.74-1-2.38-.26-.62-.53-.54-.73-.55h-.62c-.21 0-.56.08-.86.4-.3.32-1.13 1.1-1.13 2.68s1.16 3.11 1.32 3.33c.16.21 2.28 3.47 5.52 4.87.77.33 1.37.53 1.84.68.77.24 1.47.21 2.02.13.62-.09 1.9-.78 2.17-1.53.27-.75.27-1.39.19-1.53-.08-.13-.3-.21-.62-.37z"/>
                            </svg>
                        </span>
                        <h3>WA Admin</h3>
                        <p>Link formulir yang Anda terima dari admin melalui WhatsApp.</p>
                        <button type="submit" name="sumber" value="wa_admin" class="source-submit source-submit-wa">Masuk</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="source-option-card">
                        <span class="source-icon source-icon-ut" aria-hidden="true">
                            <img src="admin/assets/logo-ut.png" alt="">
                        </span>
                        <h3>Datang Langsung</h3>
                        <p>Link yang Anda gunakan saat berada langsung di lokasi Universitas Terbuka.</p>
                        <button type="submit" name="sumber" value="lokasi_universitas" class="source-submit source-submit-ut">Masuk</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
</body>
</html>
