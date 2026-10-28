<?php
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pengaturan.php');
    exit;
}

if (!csrf_valid((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Permintaan tidak valid.');
}

date_default_timezone_set('Asia/Makassar');

function backupQuoteName(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function backupSqlValue($value, int $type, mysqli $conn): string
{
    if ($value === null) {
        return 'NULL';
    }

    $numericTypes = [
        MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG,
        MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_INT24, MYSQLI_TYPE_DECIMAL,
        MYSQLI_TYPE_NEWDECIMAL, MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE,
        MYSQLI_TYPE_YEAR
    ];

    if (in_array($type, $numericTypes, true) && is_numeric($value)) {
        return (string)$value;
    }

    return "'" . mysqli_real_escape_string($conn, (string)$value) . "'";
}

$tablesQ = mysqli_query($conn, 'SHOW TABLES');
if (!$tablesQ) {
    http_response_code(500);
    exit('Tidak dapat membaca daftar tabel database.');
}

$lines = [];
$lines[] = '-- Backup database layanan_universitas';
$lines[] = '-- Dibuat: ' . date('Y-m-d H:i:s') . ' Asia/Makassar';
$lines[] = '';
$lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
$lines[] = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";';
$lines[] = '';

while ($tableRow = mysqli_fetch_row($tablesQ)) {
    $table = (string)$tableRow[0];

    $createQ = mysqli_query($conn, 'SHOW CREATE TABLE ' . backupQuoteName($table));
    if (!$createQ) {
        continue;
    }
    $createRow = mysqli_fetch_assoc($createQ);
    $createSql = $createRow['Create Table'] ?? ($createRow['Create View'] ?? '');

    $lines[] = '-- -----------------------------------------------';
    $lines[] = '-- TABEL: ' . $table;
    $lines[] = '-- -----------------------------------------------';
    $lines[] = 'DROP TABLE IF EXISTS ' . backupQuoteName($table) . ';';
    $lines[] = ($createSql !== '' ? $createSql . ';' : '');
    $lines[] = '';

    $dataQ = mysqli_query($conn, 'SELECT * FROM ' . backupQuoteName($table));
    if (!$dataQ) {
        continue;
    }

    $fields = mysqli_fetch_fields($dataQ);
    $columnNames = [];
    foreach ($fields as $field) {
        $columnNames[] = backupQuoteName($field->name);
    }

    while ($row = mysqli_fetch_row($dataQ)) {
        $values = [];
        foreach ($row as $idx => $value) {
            $values[] = backupSqlValue($value, (int)$fields[$idx]->type, $conn);
        }
        $lines[] = 'INSERT INTO ' . backupQuoteName($table) . ' (' . implode(', ', $columnNames) . ') VALUES (' . implode(', ', $values) . ');';
    }
    $lines[] = '';
}

$lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
$lines[] = '';

catatAktivitas($conn,'Backup database','Membuat backup database pada '.date('Y-m-d H:i:s'));

$filename = 'backup_layanan_universitas_' . date('Y-m-d_H-i-s') . '.sql';
$content = implode("\r\n", $lines);

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

echo $content;
exit;
?>
