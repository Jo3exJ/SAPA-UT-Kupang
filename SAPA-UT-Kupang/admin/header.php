<?php
require_once 'auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);

/* Notifikasi dimuat lewat polling AJAX setiap 10 detik. */
$notifCount = 0;
$notifData = [];

?>

<!doctype html>
<html lang="id">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= htmlspecialchars($title ?? 'Admin') ?>
    </title>

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
           HEADER
        ===================================================== */

        .admin-navbar {
            height: 70px;
            background: #ffffff;
            border-bottom: 1px solid #e1e6ee;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .admin-navbar-inner {
            width: 100%;
            padding: 0 25px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }


        /* Logo / Judul */

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 8px;

            color: #003b7a;
            font-size: 18px;
            font-weight: 700;

            text-decoration: none;
        }


        /* Bagian kanan */

        .admin-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        /* =====================================================
           NOTIFIKASI
        ===================================================== */

        .notification-wrapper {
            position: relative;
        }

        .notification-button {
            width: 42px;
            height: 42px;

            border: none;
            background: transparent;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            cursor: pointer;
            position: relative;

            font-size: 21px;

            transition: 0.2s;
        }

        .notification-button:hover {
            background: #f0f5fb;
        }


        /* Angka notifikasi */

        .notification-badge {
            position: absolute;

            top: -2px;
            right: -2px;

            min-width: 18px;
            height: 18px;

            padding: 0 4px;

            background: #dc3545;
            color: #ffffff;

            border-radius: 20px;

            font-size: 10px;
            font-weight: 700;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 2px solid #ffffff;
        }


        /* =====================================================
           KOTAK NOTIFIKASI
        ===================================================== */

        .notification-dropdown {
            position: absolute;

            top: 50px;
            right: 0;

            width: 360px;

            background: #ffffff;

            border: 1px solid #dfe5ee;
            border-radius: 12px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.12);

            overflow: hidden;

            display: none;

            z-index: 2000;
        }

        .notification-dropdown.show {
            display: block;
        }


        /* Header notif */

        .notification-header {
            padding: 14px 16px;

            background: #003f8f;
            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notification-header strong {
            font-size: 15px;
        }

        .notification-header small {
            font-size: 12px;
        }


        /* Isi */

        .notification-list {
            max-height: 360px;
            overflow-y: auto;
        }


        /* Item */

        .notification-item {
            display: block;

            padding: 13px 15px;

            color: #17324d;
            text-decoration: none;

            border-bottom: 1px solid #edf0f4;

            transition: 0.15s;
        }

        .notification-item.notification-unread { background: #f0f6ff; border-left: 3px solid #2563eb; }

        .notification-item:hover {
            background: #f4f8fc;
        }

        .notification-item strong {
            display: block;

            color: #003f8f;

            font-size: 14px;

            margin-bottom: 3px;
        }

        .notification-item small {
            display: block;

            color: #687789;

            font-size: 12px;
        }

        .notification-warning {
            margin-top: 5px;

            color: #a66a00;

            font-size: 12px;
            font-weight: 600;
        }


        /* Tidak ada notif */

        .notification-empty {
            padding: 25px 15px;

            text-align: center;

            color: #7a8795;

            font-size: 14px;
        }


        /* Lihat semua */

        .notification-footer {
            display: block;

            padding: 11px;

            text-align: center;

            color: #003f8f;

            font-size: 13px;
            font-weight: 600;

            text-decoration: none;

            background: #f8fafc;

            border-top: 1px solid #edf0f4;
        }

        .notification-footer:hover {
            background: #eef4fa;
        }


        /* =====================================================
           NAMA ADMIN
        ===================================================== */

.admin-profile-link { color:inherit; text-decoration:none; padding:6px 8px; border-radius:8px; }
        .admin-profile-link:hover { background:#f2f6fb; color:#003f8f; }

        .admin-name {
            color: #27364a;
            font-size: 14px;
            white-space: nowrap;
        }


        /* =====================================================
           RESPONSIVE HP
        ===================================================== */

        @media (max-width: 768px) {

            .admin-navbar {
                height: 60px;
            }

            .admin-navbar-inner {
                padding: 0 14px;
            }

            .admin-brand {
                font-size: 16px;
            }

        .admin-name {
                display: none;
            }

            .notification-dropdown {
                position: fixed;

                top: 65px;
                left: 10px;
                right: 10px;

                width: auto;

                max-width: none;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<nav class="admin-navbar">

    <div class="admin-navbar-inner">
        


        <!-- JUDUL -->

        <a href="dashboard.php" class="brand-ut">
            <img src="assets/logo-ut.png" alt="Universitas Terbuka">
        </a>


        <!-- BAGIAN KANAN -->

        <div class="admin-right">


            <!-- =================================================
                 NOTIFIKASI
            ================================================== -->

            <div class="notification-wrapper">


                <button
                    type="button"
                    class="notification-button"
                    id="notificationButton"
                    title="Notifikasi"
                >

                    🔔

                    <span class="notification-badge" id="notificationBadge" style="display:none">0</span>

                </button>


                <!-- KOTAK NOTIFIKASI -->

                <div
                    class="notification-dropdown"
                    id="notificationDropdown"
                >

                    <div class="notification-header">

                        <strong>
                            🔔 Notifikasi
                        </strong>

                        <small id="notificationCountText">Memuat...</small>

                    </div>


                    <div class="notification-list" id="notificationList">
                        <div class="notification-empty">Memuat notifikasi...</div>
                    </div>

                    <a href="data_pengunjung.php" class="notification-footer" id="notificationFooter">Lihat Data Pengunjung →</a>

                </div>

            </div>


            <!-- =================================================
                 NAMA ADMIN
            ================================================== -->

            <a href="profil.php" class="admin-name admin-profile-link" title="Profil Saya">

                👤
                <?= htmlspecialchars(
                    $_SESSION['admin_nama'] ?? 'Administrator'
                ) ?>

            </a>


            <!-- =================================================
                 KELUAR
            ================================================== -->

            <a
                href="logout.php"
                class="btn btn-sm btn-outline-danger"
            >
                Keluar
            </a>


        </div>

    </div>

</nav>


<!-- =========================================================
     LAYOUT
========================================================= -->

<div class="layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

<!-- =====================================================
     SIDEBAR
====================================================== -->

<aside class="sidebar">

    <!-- DASHBOARD -->
    <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        🏠
        <span>Dashboard</span>
    </a>


    <!-- ANTRIAN -->
    <a href="antrian.php" class="<?= $currentPage === 'antrian.php' ? 'active' : '' ?>">
        🎫
        <span>Antrian</span>
    </a>


    <!-- DATA PENGUNJUNG -->
    <a href="data_pengunjung.php" class="<?= $currentPage === 'data_pengunjung.php' ? 'active' : '' ?>">
        👥
        <span>Data Pengunjung</span>
    </a>


    <!-- CATATAN -->
    <a href="catatan.php" class="<?= $currentPage === 'catatan.php' ? 'active' : '' ?>">
        📝
        <span>Catatan</span>
    </a>


    <!-- ADMIN -->
    <a href="admin.php" class="<?= $currentPage === 'admin.php' ? 'active' : '' ?>">
        👤
        <span>Admin</span>
    </a>


    <!-- TAMBAH ADMIN -->
    <a href="tambah_admin.php" class="<?= $currentPage === 'tambah_admin.php' ? 'active' : '' ?>">
        ➕
        <span>Tambah Admin</span>
    </a>


    <!-- BACKUP DATABASE -->
    <a href="pengaturan.php" class="<?= $currentPage === 'pengaturan.php' ? 'active' : '' ?>">
        💾
        <span>Backup Database</span>
    </a>

</aside>


    <!-- =====================================================
         KONTEN
    ====================================================== -->

    <main class="content">
<input type="hidden" id="notificationCsrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">


<script>

/*
|--------------------------------------------------------------------------
| NOTIFIKASI DROPDOWN
|--------------------------------------------------------------------------
*/

const notificationButton = document.getElementById('notificationButton');
const notificationDropdown = document.getElementById('notificationDropdown');
const notificationBadge = document.getElementById('notificationBadge');
const notificationList = document.getElementById('notificationList');
const notificationCountText = document.getElementById('notificationCountText');
const notificationCsrf = document.getElementById('notificationCsrf');
let lastNotificationIds = new Set();

function escapeNotif(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}
function formatNotifTime(value) {
    if (!value) return '';
    // MySQL DATETIME dikirim sebagai YYYY-MM-DD HH:mm:ss dalam waktu lokal server.
    const normalized = String(value).replace(' ', 'T');
    const timestamp = new Date(normalized).getTime();
    if (!Number.isFinite(timestamp)) return String(value);
    const seconds = Math.max(0, Math.floor((Date.now() - timestamp) / 1000));
    if (seconds < 60) return 'Baru saja';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return minutes + ' menit lalu';
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return hours + ' jam lalu';
    const days = Math.floor(hours / 24);
    return days + ' hari lalu';
}
function renderNotifications(data) {
    const items = data.items || [];
    const count = Number(data.count || 0);
    notificationBadge.style.display = count > 0 ? 'flex' : 'none';
    notificationBadge.textContent = count > 99 ? '99+' : String(count);
    notificationCountText.textContent = count + ' belum dibaca';
    if (!items.length) {
        notificationList.innerHTML = '<div class="notification-empty">Belum ada notifikasi.</div>';
        return;
    }
    notificationList.innerHTML = items.map(item => {
        const id = String(item.id);
        const isReminder = String(item.kind) === 'reminder';
        const cls = 'notification-item' + (item.read ? '' : ' notification-unread');
        const url = escapeNotif(item.url || 'dashboard.php');
        const title = escapeNotif(item.title || 'Notifikasi');
        const message = escapeNotif(item.message || '');
        const time = escapeNotif(formatNotifTime(item.time || ''));
        const markId = isReminder ? '' : id;
        return `<a href="${url}" class="${cls}" data-notif-id="${escapeNotif(markId)}" data-notif-reminder="${isReminder ? '1':'0'}"><strong>${title}</strong><small>${message}</small><small>${time}</small></a>`;
    }).join('');
}
async function loadNotifications() {
    try {
        const response = await fetch('notifikasi_poll.php', {cache:'no-store', headers:{'X-Requested-With':'XMLHttpRequest'}});
        if (!response.ok) return;
        const data = await response.json();
        if (data.ok) renderNotifications(data);
    } catch (e) { /* jaringan sementara tidak tersedia; coba lagi pada interval berikutnya */ }
}
if (notificationButton && notificationDropdown) {
    notificationButton.addEventListener('click', event => {
        event.stopPropagation();
        notificationDropdown.classList.toggle('show');
        if (notificationDropdown.classList.contains('show')) loadNotifications();
    });
    document.addEventListener('click', event => {
        if (!notificationDropdown.contains(event.target) && !notificationButton.contains(event.target)) notificationDropdown.classList.remove('show');
    });
    notificationList?.addEventListener('click', async event => {
        const link = event.target.closest('a[data-notif-id]');
        if (!link || !link.dataset.notifId || link.dataset.notifReminder === '1') return;
        event.preventDefault();
        const targetUrl = link.href;
        const body = new URLSearchParams({id_notifikasi: link.dataset.notifId, csrf_token: notificationCsrf?.value || ''});
        try { await fetch('notifikasi_baca.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body, keepalive:true}); } catch(e) {}
        window.location.href = targetUrl;
    });
    loadNotifications();
    setInterval(loadNotifications, 10000);
}
</script>