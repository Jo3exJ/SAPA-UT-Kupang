<?php
$title = 'Backup Database';
require_once __DIR__ . '/../csrf.php';
require 'header.php';
?>

<h2>Backup Database</h2>
<div class="cardbox col-lg-7">
    <p class="text-muted mb-3">Unduh salinan database dalam format <code>.sql</code> sebelum melakukan perubahan besar pada sistem.</p>
    <form method="post" action="backup_database.php" onsubmit="return confirm('Unduh backup database sekarang?');">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn btn-outline-primary">💾 Backup Database</button>
    </form>
</div>

<?php require 'footer.php'; ?>
