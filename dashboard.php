<?php
require __DIR__ . '/includes/layout.php';

$u = require_login();
$admin = $u['role'] === 'admin';

if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT']
        . '/CivicHub-2/config/database.php';
}

if ($admin) {
    $stmt = $conn->prepare(
        "SELECT * FROM surat ORDER BY id DESC"
    );
} else {
    $stmt = $conn->prepare(
        "SELECT * FROM surat WHERE user_id = ? ORDER BY id DESC"
    );
    $userId = (int) $u['id'];
    $stmt->bind_param('i', $userId);
}

$stmt->execute();
$result = $stmt->get_result();
$surat = [];

while ($row = $result->fetch_assoc()) {
    $surat[] = $row;
}

$stmt->close();

$aktif = null;
$total = count($surat);
$selesai = 0;
$proses = 0;

foreach ($surat as $s) {
    if (strcasecmp($s['status'], 'Selesai') === 0) {
        $selesai++;
    } else {
        $proses++;

        if ($aktif === null) {
            $aktif = $s;
        }
    }
}

$beritaTerbaru = [];

$stmtBerita = $conn->prepare(
    "SELECT id, judul, ringkasan, kategori, tanggal
     FROM berita
     ORDER BY tanggal DESC, id DESC
     LIMIT 3"
);

$stmtBerita->execute();
$resultBerita = $stmtBerita->get_result();

while ($row = $resultBerita->fetch_assoc()) {
    $beritaTerbaru[] = $row;
}

$stmtBerita->close();

app_start('Dashboard', 'dashboard', 'CivicHub',
    t('Halo, %s.', $u['nama']) . ' ' . t($admin ? 'Ini ringkasan surat yang masuk.' : 'Pantau pengajuan suratmu di sini.'));
?>
<div class="stats">
    <div class="stat g"><div><small><?= e(t($admin ? 'Surat Masuk' : 'Total Surat')) ?></small><b><?= $total ?></b></div><?= icon('doc') ?></div>
    <div class="stat y"><div><small><?= e(t('Sedang Diproses')) ?></small><b><?= $proses ?></b></div><?= icon('clock') ?></div>
    <div class="stat g2"><div><small><?= e(t('Selesai')) ?></small><b><?= $selesai ?></b></div><?= icon('check') ?></div>
</div>

<?php if ($aktif): ?>
    <a class="status-card" href="surat_detail.php?id=<?= $aktif['id'] ?>">
        <div>
            <h3><?= e(t('Laporan sedang diproses…')) ?></h3>
            <span class="tag grad"><?= e(t($aktif['jenis'])) ?></span>
            <p><?= e(t('Klik untuk selengkapnya')) ?></p>
            <div class="status-row"><?= e(t('Status')) ?> <span class="badge <?= status_class($aktif['status']) ?>"><?= e(t($aktif['status'])) ?></span></div>
        </div>
        <span class="go"><?= icon('arrow') ?></span>
    </a>
<?php else: ?>
    <div class="empty">
        <h3><?= e(t('Belum ada surat yang diproses')) ?></h3>
        <p><?= e(t('Semua pengajuan sudah selesai.')) ?><?= $admin ? '' : e(t(' Ajukan surat baru kapan saja.')) ?></p>
        <?php if (!$admin): ?><a class="btn btn-dark" style="margin-top:14px" href="surat_form.php"><?= icon('plus') ?><?= e(t('Ajukan Surat')) ?></a><?php endif; ?>
    </div>
<?php endif; ?>

<div class="sec-title"><h2><?= e(t('Features')) ?></h2></div>
<div class="features">
    <a class="feature" href="surat.php"><span class="dia"><?= icon('doc') ?></span><span><?= e(t('Pelayanan')) ?></span></a>
    <a class="feature" href="berita.php"><span class="dia"><?= icon('news') ?></span><span><?= e(t('Berita')) ?></span></a>
    <a class="feature" href="profil.php"><span class="dia"><?= icon('user') ?></span><span><?= e(t('Profile')) ?></span></a>
</div>

<div class="sec-title"><h2><?= e(t('Berita Terbaru')) ?></h2><a href="berita.php"><?= e(t('Lihat semua')) ?> <?= icon('arrow') ?></a></div>
<div class="grid3">
    <?php foreach ($beritaTerbaru as $b): ?>
        <a class="news" href="berita_detail.php?id=<?= $b['id'] ?>">
            <div class="news-body" style="grid-template-columns:1fr">
                <div class="news-meta"><span class="kat <?= kategori_class($b['kategori']) ?>"><?= e(t($b['kategori'])) ?></span>
                    <span class="date" style="margin:0"><?= icon('calendar') ?><?= e(tgl($b['tanggal'])) ?></span></div>
                <h3><?= e($b['judul']) ?></h3>
                <p><?= e($b['ringkasan']) ?></p>
            </div>
            <div class="news-foot"><?= e(t('Baca Selengkapnya')) ?> <?= icon('arrow') ?></div>
        </a>
    <?php endforeach; ?>
</div>
<?php app_end(); ?>