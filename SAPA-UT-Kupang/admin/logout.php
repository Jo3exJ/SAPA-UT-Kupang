<?php
require_once __DIR__ . '/auth.php';

catatAktivitas($conn, 'Logout admin', 'Admin keluar dari sistem');

// Hapus seluruh data sesi admin dengan aman.
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();
header('Location: login.php');
exit;
?>
