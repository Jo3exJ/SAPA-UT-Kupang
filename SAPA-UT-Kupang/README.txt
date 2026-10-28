INSTALLASI
1. Ekstrak folder ini ke htdocs, misalnya htdocs/layanan_universitas.
2. Import database.sql di phpMyAdmin.
3. Pastikan koneksi.php memakai database layanan_universitas.
4. Buka /admin/login.php
Login awal: admin / admin123
5. Setelah instalasi, buat akun admin/petugas baru dari menu Tambah Admin dan gunakan password yang kuat.

FITUR ADMIN
Dashboard, antrian berbasis nama, pencarian/filter, detail, tindak lanjut + petugas, catatan, hapus, laporan, export Excel, daftar/tambah admin, backup database.
Keamanan: prepared statements untuk login/tambah admin/tindak lanjut/hapus dan password_hash/password_verify.

PENTING: sistem tidak menggunakan nomor registrasi atau nomor antrean. Antrian menampilkan pengunjung berdasarkan nama; petugas menangani secara manual dari halaman Detail.


UPDATE FITUR TERBARU

Alur pengunjung: index.php -> sumber.php -> pengunjung.php -> layanan.php.
Sumber link dicatat sebagai WhatsApp Admin atau Langsung dari Lokasi Universitas dan ditampilkan di Data Pengunjung serta export Excel.
Saat layanan berstatus Selesai, Detail Pengunjung menampilkan kotak Konfirmasi ke Pengunjung.
Untuk pengunjung dari WhatsApp Admin, pengingat menginformasikan bahwa konfirmasi ke pengunjung bersifat WAJIB.
Untuk pengunjung yang datang langsung dari lokasi universitas, pengingat tersebut bersifat OPSIONAL.
Nomor HP pada kotak tersebut dapat ditekan untuk membuka WhatsApp melalui https://wa.me/.

Jalankan database_update_kjj.sql satu kali pada database layanan_universitas sebelum menerima data baru dengan fitur sumber link.

Fitur tambahan: antrian berdasarkan nama tanpa fitur panggil nama/voice, pencegahan submit ganda, keamanan CSRF/login, backup database, Profil Saya, dan log Aktivitas Admin.

PENTING - MIGRASI TERBARU:
Jalankan database_update_kjj.sql di phpMyAdmin satu kali bila ingin menerapkan struktur database secara manual. Aplikasi juga akan memeriksa struktur tambahan secara otomatis saat login/admin. Migrasi tidak menambahkan fitur nomor registrasi, nomor antrean, atau panggil nama. Pengunjung dengan sumber link WhatsApp Admin tidak ditampilkan di Antrian; mereka tetap tersimpan di Data Pengunjung.


FITUR TAMBAHAN: KUNCI DETAIL PER PETUGAS
- Satu data detail pengunjung hanya bisa dibuka oleh satu akun petugas pada satu waktu.
- Petugas lain akan menerima alert berisi nama petugas yang sedang membuka detail.
- Kunci diperbarui otomatis selama halaman tetap terbuka.
- Jika tab/browser tertutup mendadak, kunci akan kedaluwarsa setelah 5 menit tanpa pembaruan.
- Kolom lock_admin_id dan lock_at ditambahkan otomatis melalui schema_bootstrap.php.
- Untuk hasil terbaik, uji dengan dua akun petugas di dua browser/sesi berbeda.


FITUR PENCARIAN DATA MAHASISWA (SIAP DIISI NANTI)
1. Tabel kosong mahasiswa disediakan pada file database_mahasiswa.sql.
2. Buka phpMyAdmin, pilih database layanan_universitas, lalu impor database_mahasiswa.sql.
3. Tabel memiliki kolom nim, nama, prodi, dan alamat. NIM harus unik dan terdiri dari 9 angka.
4. Setelah data mahasiswa tersedia, ketik NIM 9 angka pada pengunjung.php. Jika ditemukan, nama, prodi, dan alamat terisi otomatis.
5. Jika NIM tidak ditemukan atau tabel belum dibuat, formulir tetap dapat diisi manual.
6. Untuk mengisi data nantinya, siapkan Excel/CSV dengan kolom nim, nama, prodi, alamat lalu impor ke tabel mahasiswa melalui phpMyAdmin. Pertahankan NIM sebagai teks agar angka tidak berubah.


DATA MAHASISWA
File database_mahasiswa.sql kini berisi data mahasiswa dari file Excel yang diberikan.
Jumlah data unik yang disiapkan: 15,938 mahasiswa.
Kolom yang diimpor: NIM, Nama Mahasiswa, Prodi, dan Alamat Mahasiswa.
Untuk mengimpor: buka phpMyAdmin, pilih database layanan_universitas, pilih Import, lalu pilih database_mahasiswa.sql.
Jika tabel mahasiswa sudah berisi data, query akan memperbarui baris dengan NIM sama.
Data kontak, agama, umur, dan kolom lain dari Excel tidak ikut diimpor karena fitur pencarian hanya memerlukan empat kolom tersebut.
