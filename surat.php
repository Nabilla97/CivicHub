<?php
require __DIR__ . '/includes/layout.php';

// Gunakan koneksi MySQLi CivicHub.
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT']
        . '/CivicHub-2/config/database.php';
}

$u = require_login();
$admin = $u['role'] === 'admin';
$f = $_GET['status'] ?? '';

// Hanya gunakan filter status yang valid.
$filterValid = in_array($f, STATUS, true);

$list = [];

if ($admin) {
    // Admin melihat semua pengajuan.
    if ($filterValid) {
        $stmt = $conn->prepare(
            'SELECT * FROM surat WHERE status = ? ORDER BY id DESC'
        );
        $stmt->bind_param('s', $f);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query(
            'SELECT * FROM surat ORDER BY id DESC'
        );
    }
} else {
    // Warga hanya melihat pengajuan miliknya sendiri.
    $userId = (int) $u['id'];

    if ($filterValid) {
        $stmt = $conn->prepare(
            'SELECT * FROM surat
             WHERE user_id = ? AND status = ?
             ORDER BY id DESC'
        );
        $stmt->bind_param('is', $userId, $f);
    } else {
        $stmt = $conn->prepare(
            'SELECT * FROM surat
             WHERE user_id = ?
             ORDER BY id DESC'
        );
        $stmt->bind_param('i', $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();
}

// Ambil hasil query menjadi array untuk tampilan kartu.
while ($row = $result->fetch_assoc()) {
    $list[] = $row;
}

if (isset($stmt)) {
    $stmt->close();
}

app_start(
    t($admin ? 'Surat Masuk' : 'Pengajuan Surat'),
    'surat',
    t($admin ? 'Surat Masuk Warga' : 'Pengajuan Surat Warga'),
    t(
        $admin
            ? 'Daftar surat yang diajukan warga untuk ditinjau.'
            : 'Selamat datang para warga di halaman untuk membuat pengajuan surat.'
    )
);
?>


<div class="sec-title">
    <h2><?= e(t('Riwayat Pengajuan')) ?></h2>
    <?php if (!$admin): ?><a class="btn btn-dark" style="color:#fff" href="surat_form.php"><?= icon('plus') ?>Ajukan Surat</a><?php endif; ?>
</div>
<div class="toolbar">
    <div class="chips">
        <a class="chip <?= $f === '' ? 'on' : '' ?>" href="surat.php">
    <?= e(t('Semua')) ?>
</a>

        <?php foreach (STATUS as $st): ?>
            <a
                class="chip <?= $f === $st ? 'on' : '' ?>"
                href="surat.php?status=<?= urlencode($st) ?>"
            >
                <?= e(t($st)) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$list): ?>
    <div class="empty">
    <h3><?= e(t('Belum ada pengajuan')) ?></h3>
    <p>
        <?= e(t($admin
            ? 'Belum ada surat yang masuk.'
            : 'Mulai dengan mengajukan surat pertamamu.'
        )) ?>
    </p>

    <?php if (!$admin): ?>
        <a class="btn btn-dark" style="margin-top:14px" href="surat_form.php">
            <?= icon('plus') ?><?= e(t('Ajukan Surat')) ?>
        </a>
    <?php endif; ?>
    </div>
<?php else: ?>
<div class="grid2">
    <?php foreach ($list as $s): $pemohon = find_user($s['user_id']); ?>
        <article class="surat-card">
            <div><span class="tag grad"><?= e($s['jenis']) ?></span></div>
            <h3><?= e($s['judul']) ?></h3>
            <p><?= e(mb_strimwidth($s['deskripsi'], 0, 90, '…')) ?></p>
            <?= progress_html($s['status']) ?>
            <div class="foot">
                <span class="who"><?= icon('calendar') ?></span>
                <span class="who" style="margin-right:auto;margin-left:-6px"><?= e(tgl($s['tanggal'])) ?><?= $admin && $pemohon ? ' · ' . e($pemohon['nama']) : '' ?></span>
                <a class="btn btn-dark btn-sm" href="surat_detail.php?id=<?= $s['id'] ?>">Detail</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php app_end(); ?>
