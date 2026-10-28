<?php
/**
 * Memastikan struktur tambahan aplikasi tersedia.
 * Aman dipanggil berulang kali pada XAMPP/MariaDB.
 */
function ensureAppSchema(mysqli $conn): bool
{
    static $done = false;
    static $ok = true;

    if ($done) {
        return $ok;
    }

    $done = true;

    try {
        $requiredColumns = [
            'kunjungan' => [
                'sumber_link' => "ALTER TABLE kunjungan ADD COLUMN sumber_link VARCHAR(30) NULL AFTER email",
                'id_petugas' => "ALTER TABLE kunjungan ADD COLUMN id_petugas INT NULL AFTER status_layanan",
                'petugas_penyelesaian' => "ALTER TABLE kunjungan ADD COLUMN petugas_penyelesaian VARCHAR(150) NULL AFTER id_petugas",
                'waktu_selesai' => "ALTER TABLE kunjungan ADD COLUMN waktu_selesai DATETIME NULL AFTER petugas_penyelesaian",
                'lock_admin_id' => "ALTER TABLE kunjungan ADD COLUMN lock_admin_id INT NULL AFTER waktu_selesai",
                'lock_at' => "ALTER TABLE kunjungan ADD COLUMN lock_at DATETIME NULL AFTER lock_admin_id",
            ],
            'admins' => [
                'last_login' => "ALTER TABLE admins ADD COLUMN last_login DATETIME NULL AFTER created_at",
            ],
        ];

        foreach ($requiredColumns as $table => $columns) {
            foreach ($columns as $column => $alterSql) {
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT COUNT(*)
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = ?
                       AND COLUMN_NAME = ?"
                );

                if (!$stmt) {
                    throw new RuntimeException('Gagal memeriksa struktur database.');
                }

                mysqli_stmt_bind_param($stmt, 'ss', $table, $column);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $exists = (int)(mysqli_fetch_row($result)[0] ?? 0) > 0;
                mysqli_stmt_close($stmt);

                if (!$exists && !mysqli_query($conn, $alterSql)) {
                    throw new RuntimeException('Gagal memperbarui struktur tabel.');
                }
            }
        }

        $tableSql = [
            "CREATE TABLE IF NOT EXISTS notifikasi (
                id_notifikasi BIGINT AUTO_INCREMENT PRIMARY KEY,
                id_kunjungan INT NULL,
                jenis VARCHAR(40) NOT NULL,
                judul VARCHAR(180) NOT NULL,
                pesan TEXT NOT NULL,
                url_tujuan VARCHAR(255) DEFAULT NULL,
                kunci_unik VARCHAR(120) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_notifikasi_kunci (kunci_unik),
                INDEX idx_notifikasi_created (created_at),
                INDEX idx_notifikasi_kunjungan (id_kunjungan),
                INDEX idx_notifikasi_jenis (jenis)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS notifikasi_dibaca (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                id_notifikasi BIGINT NOT NULL,
                id_admin INT NOT NULL,
                dibaca_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_notif_admin (id_notifikasi, id_admin),
                INDEX idx_notif_dibaca_admin (id_admin)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS catatan_tindakan (
                id_catatan INT AUTO_INCREMENT PRIMARY KEY,
                id_kunjungan INT NOT NULL,
                id_admin INT DEFAULT NULL,
                status_dari VARCHAR(30) DEFAULT NULL,
                status_ke VARCHAR(30) NOT NULL,
                catatan TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_catatan_kunjungan (id_kunjungan),
                INDEX idx_catatan_admin (id_admin),
                INDEX idx_catatan_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS log_aktivitas (
                id_log BIGINT AUTO_INCREMENT PRIMARY KEY,
                id_admin INT DEFAULT NULL,
                aktivitas VARCHAR(255) NOT NULL,
                detail TEXT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_log_admin (id_admin),
                INDEX idx_log_created (created_at),
                INDEX idx_log_aktivitas (aktivitas)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($tableSql as $sql) {
            if (!mysqli_query($conn, $sql)) {
                throw new RuntimeException('Gagal memastikan tabel tambahan tersedia.');
            }
        }

        // Migrasi ringan untuk database lama: kunci unik mencegah pengingat dibuat berulang.
        $hasUniqueKeyColumn = false;
        $checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM notifikasi LIKE 'kunci_unik'");
        if ($checkColumn) {
            $hasUniqueKeyColumn = mysqli_num_rows($checkColumn) > 0;
            mysqli_free_result($checkColumn);
        }
        if (!$hasUniqueKeyColumn) {
            if (!mysqli_query($conn, "ALTER TABLE notifikasi ADD COLUMN kunci_unik VARCHAR(120) NULL")) {
                throw new RuntimeException('Gagal menambahkan kunci notifikasi.');
            }
        }
        $hasUniqueIndex = false;
        $checkIndex = mysqli_query($conn, "SHOW INDEX FROM notifikasi WHERE Key_name = 'uq_notifikasi_kunci'");
        if ($checkIndex) {
            $hasUniqueIndex = mysqli_num_rows($checkIndex) > 0;
            mysqli_free_result($checkIndex);
        }
        if (!$hasUniqueIndex && !mysqli_query($conn, "ALTER TABLE notifikasi ADD UNIQUE KEY uq_notifikasi_kunci (kunci_unik)")) {
            throw new RuntimeException('Gagal membuat indeks unik notifikasi.');
        }
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}
