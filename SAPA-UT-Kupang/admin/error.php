<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$message = $_SESSION['system_error'] ?? 'Terjadi kesalahan yang tidak dapat diproses.';
unset($_SESSION['system_error']);
http_response_code(500);
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Terjadi Kendala</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f4f7fb;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:system-ui,-apple-system,"Segoe UI",sans-serif}.box{max-width:520px;background:#fff;border:1px solid #e3e8ef;border-radius:18px;padding:34px;box-shadow:0 10px 30px rgba(0,0,0,.07);text-align:center}.icon{font-size:48px;margin-bottom:10px}.box h1{font-size:24px;color:#073b82}.box p{color:#6c757d;line-height:1.6}</style></head><body><div class="box"><div class="icon">⚠️</div><h1>Terjadi Kendala</h1><p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a></div></body></html>
