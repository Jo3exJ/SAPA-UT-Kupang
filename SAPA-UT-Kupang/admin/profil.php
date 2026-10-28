<?php
$title='Profil Saya'; require 'header.php';
$idAdmin=(int)($_SESSION['admin_id']??0); $error=''; $success='';
$q=mysqli_prepare($conn,'SELECT id_admin,nama,username,password,aktif,created_at,last_login FROM admins WHERE id_admin=? LIMIT 1'); mysqli_stmt_bind_param($q,'i',$idAdmin); mysqli_stmt_execute($q); $profil=mysqli_fetch_assoc(mysqli_stmt_get_result($q));
if(!$profil){$error='Data profil tidak ditemukan.';} elseif($_SERVER['REQUEST_METHOD']==='POST'){
 if(!csrf_valid((string)($_POST['csrf_token']??''))){$error='Sesi keamanan tidak valid. Silakan muat ulang halaman.';} else {
  $nama=trim($_POST['nama']??''); $lama=$_POST['password_lama']??''; $baru=$_POST['password_baru']??''; $konf=$_POST['konfirmasi_password']??'';
  if($nama===''){$error='Nama tidak boleh kosong.';} else {
   if($baru!==''||$lama!==''||$konf!==''){
    if(!password_verify($lama,$profil['password'])){$error='Password lama tidak benar.';}
    elseif(strlen($baru)<6){$error='Password baru minimal 6 karakter.';}
    elseif($baru!==$konf){$error='Konfirmasi password baru tidak sama.';}
   }
   if($error===''){
    $u=mysqli_prepare($conn,'UPDATE admins SET nama=? WHERE id_admin=?'); mysqli_stmt_bind_param($u,'si',$nama,$idAdmin); mysqli_stmt_execute($u); $_SESSION['admin_nama']=$nama;
    if($baru!==''){$hash=password_hash($baru,PASSWORD_DEFAULT);$up=mysqli_prepare($conn,'UPDATE admins SET password=? WHERE id_admin=?');mysqli_stmt_bind_param($up,'si',$hash,$idAdmin);mysqli_stmt_execute($up);catatAktivitas($conn,'Ubah password','Password akun sendiri diperbarui');}
    catatAktivitas($conn,'Ubah profil','Nama profil diperbarui menjadi '.$nama); $success='Profil berhasil diperbarui.'; $profil['nama']=$nama;
   }
  }
 }
}
?>
<div class="mb-4"><h2>👤 Profil Saya</h2><p class="text-muted mb-0">Kelola nama dan password akun petugas yang sedang digunakan.</p></div>
<?php if($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<div class="row g-3"><div class="col-lg-7"><div class="cardbox"><h5 class="mb-1">Informasi Akun</h5><p class="text-muted small mb-4">Username tidak dapat diubah dari halaman ini.</p><form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8') ?>"><div class="mb-3"><label class="form-label fw-semibold">Nama</label><input class="form-control" name="nama" value="<?= htmlspecialchars($profil['nama']??'') ?>" required></div><div class="mb-3"><label class="form-label fw-semibold">Username</label><input class="form-control" value="<?= htmlspecialchars($profil['username']??'') ?>" readonly></div><hr class="my-4"><h6>Ganti Password <span class="text-muted fw-normal">(opsional)</span></h6><div class="mb-3"><label class="form-label">Password Lama</label><div class="input-group"><input type="password" id="password_lama" name="password_lama" class="form-control" autocomplete="current-password"><button type="button" class="btn btn-outline-secondary toggle-password" data-target="password_lama">Tampilkan</button></div></div><div class="mb-3"><label class="form-label">Password Baru</label><div class="input-group"><input type="password" id="password_baru" name="password_baru" class="form-control" minlength="6" autocomplete="new-password"><button type="button" class="btn btn-outline-secondary toggle-password" data-target="password_baru">Tampilkan</button></div><div class="form-text">Minimal 6 karakter. Kosongkan jika tidak ingin mengganti password.</div></div><div class="mb-4"><label class="form-label">Konfirmasi Password Baru</label><div class="input-group"><input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-control" minlength="6" autocomplete="new-password"><button type="button" class="btn btn-outline-secondary toggle-password" data-target="konfirmasi_password">Tampilkan</button></div></div><button class="btn btn-primary">Simpan Perubahan</button></form></div></div><div class="col-lg-5"><div class="cardbox"><h5 class="mb-3">Ringkasan</h5><div class="mb-3"><small class="text-muted d-block">Status Akun</small><span class="badge bg-success">Aktif</span></div><div class="mb-3"><small class="text-muted d-block">Akun Dibuat</small><strong><?= htmlspecialchars($profil['created_at']??'-') ?></strong></div><div><small class="text-muted d-block">Login Terakhir</small><strong><?= htmlspecialchars($profil['last_login']??'-') ?></strong></div></div></div></div>
<?php require 'footer.php'; ?>
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
