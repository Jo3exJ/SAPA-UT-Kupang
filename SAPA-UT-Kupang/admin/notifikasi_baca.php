<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid((string)($_POST['csrf_token'] ?? ''))) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
$id=(int)($_POST['id_notifikasi'] ?? 0); $adminId=(int)($_SESSION['admin_id'] ?? 0);
if ($id>0 && $adminId>0) { $s=mysqli_prepare($conn,"INSERT IGNORE INTO notifikasi_dibaca (id_notifikasi,id_admin,dibaca_pada) VALUES (?,?,NOW())"); if($s){mysqli_stmt_bind_param($s,'ii',$id,$adminId);mysqli_stmt_execute($s);} }
echo json_encode(['ok'=>true]);
