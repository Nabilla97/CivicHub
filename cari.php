<?php
require __DIR__ . '/includes/layout.php';
$u = require_login();
$q = trim($_GET['q'] ?? '');
$hs = []; $hb = [];
if ($q !== '') {
    $hs = array_values(array_filter(surat_visible(), fn($s) => stripos($s['jenis'] . ' ' . $s['judul'] . ' ' . $s['deskripsi'], $q) !== false));
    $hb = array_values(array_filter(berita_all(), fn($b) => stripos($b['judul'] . ' ' . $b['ringkasan'] . ' ' . $b['kategori'], $q) !== false));
}
app_start('Pencarian', 'dashboard', 'Hasil Pencarian', $q === '' ? 'Ketik kata kunci pada kolom pencarian.' : count($hs) + count($hb) . ' hasil untuk “' . $q . '”');
?>
<?php if ($q !== '' && !$hs && !$hb): ?>
    <div class="empty"><h3>Tidak ada hasil</h3><p>Coba kata kunci lain, misalnya “domisili” atau “QRIS”.</p></div>
<?php endif; ?>

<?php if ($hs): ?>
    <div class="sec-title"><h2>Surat (<?= count($hs) ?>)</h2></div>
    <div class="grid2">
    <?php foreach ($hs as $s): ?>
        <article class="surat-card">
            <div><span class="tag grad"><?= e($s['jenis']) ?></span></div>
            <h3><?= e($s['judul']) ?></h3>
            <?= progress_html($s['status']) ?>
            <div class="foot"><span class="who"><?= e(tgl($s['tanggal'])) ?></span><a class="btn btn-dark btn-sm" href="surat_detail.php?id=<?= $s['id'] ?>">Detail</a></div>
        </article>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($hb): ?>
    <div class="sec-title"><h2>Berita (<?= count($hb) ?>)</h2></div>
    <div class="grid2">
    <?php foreach ($hb as $b): ?>
        <a class="news" href="berita_detail.php?id=<?= $b['id'] ?>">
            <div class="news-body">
                <?= berita_thumb($b) ?>
                <div><div class="news-meta"><span class="kat <?= kategori_class($b['kategori']) ?>"><?= e($b['kategori']) ?></span></div>
                <h3><?= e($b['judul']) ?></h3><p><?= e($b['ringkasan']) ?></p></div>
            </div>
            <div class="news-foot">Baca Selengkapnya <?= icon('arrow') ?></div>
        </a>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php app_end(); ?>
