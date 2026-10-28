<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
$adminId = (int)($_SESSION['admin_id'] ?? 0);
if ($adminId < 1) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }

// Buat pengingat persisten hanya sekali per pengunjung. Polling berikutnya tidak
// membuat notifikasi baru untuk pengingat yang sama.
$reminders = mysqli_query($conn, "SELECT k.id_kunjungan, k.nama, k.layanan, TIMESTAMPDIFF(MINUTE,k.created_at,NOW()) AS menit
    FROM kunjungan k
    WHERE k.status_layanan <> 'selesai'
      AND k.created_at <= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
      AND NOT EXISTS (SELECT 1 FROM notifikasi n WHERE n.kunci_unik = CONCAT('pengingat-', k.id_kunjungan))
    ORDER BY k.created_at ASC LIMIT 50");
if ($reminders) {
    $insert = mysqli_prepare($conn, "INSERT IGNORE INTO notifikasi (id_kunjungan,jenis,judul,pesan,url_tujuan,kunci_unik,created_at) VALUES (?,'pengingat','Perlu ditindaklanjuti',?,?,?,NOW())");
    if ($insert) {
        while ($r = mysqli_fetch_assoc($reminders)) {
            $id = (int)$r['id_kunjungan'];
            $pesan = $r['nama'].' — '.$r['layanan'].' belum selesai selama '.(int)$r['menit'].' menit.';
            $url = 'detail_pengunjung.php?id='.$id;
            $key = 'pengingat-'.$id;
            mysqli_stmt_bind_param($insert, 'isss', $id, $pesan, $url, $key);
            mysqli_stmt_execute($insert);
        }
        mysqli_stmt_close($insert);
    }
    mysqli_free_result($reminders);
}

$items = [];
$stmt = mysqli_prepare($conn, "SELECT n.id_notifikasi,n.jenis,n.judul,n.pesan,n.url_tujuan,n.created_at,
    IF(d.id IS NULL,0,1) AS sudah_dibaca,
    k.nama AS nama_pengunjung, k.layanan AS layanan_pengunjung,
    TIMESTAMPDIFF(MINUTE,k.created_at,NOW()) AS durasi_menit
    FROM notifikasi n
    LEFT JOIN notifikasi_dibaca d ON d.id_notifikasi=n.id_notifikasi AND d.id_admin=?
    LEFT JOIN kunjungan k ON k.id_kunjungan=n.id_kunjungan
    WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    ORDER BY n.id_notifikasi DESC LIMIT 30");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $adminId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $pesan = $r['pesan'];
        // Bangun ulang teks pengingat dari waktu kunjungan saat ini, termasuk notifikasi lama yang sudah tersimpan.
        if ($r['jenis'] === 'pengingat' && $r['nama_pengunjung'] !== null) {
            $menit = max(0, (int)$r['durasi_menit']);
            if ($menit < 60) {
                $durasi = $menit . ' menit';
            } elseif ($menit < 1440) {
                $jam = intdiv($menit, 60);
                $sisaMenit = $menit % 60;
                $durasi = $jam . ' jam' . ($sisaMenit ? ' ' . $sisaMenit . ' menit' : '');
            } else {
                $hari = intdiv($menit, 1440);
                $sisaJam = intdiv($menit % 1440, 60);
                $durasi = $hari . ' hari' . ($sisaJam ? ' ' . $sisaJam . ' jam' : '');
            }
            $pesan = $r['nama_pengunjung'].' — '.$r['layanan_pengunjung'].' belum selesai selama '.$durasi.'.';
        }
        $items[] = ['id'=>(int)$r['id_notifikasi'],'kind'=>$r['jenis'],'title'=>$r['judul'],'message'=>$pesan,'url'=>$r['url_tujuan'] ?: 'dashboard.php','time'=>$r['created_at'],'read'=>(bool)$r['sudah_dibaca']];
    }
    mysqli_stmt_close($stmt);
}
$unread = 0;
foreach ($items as $item) if (empty($item['read'])) $unread++;
echo json_encode(['ok'=>true,'count'=>$unread,'items'=>$items], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
