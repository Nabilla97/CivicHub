
<?php
require __DIR__ . '/includes/layout.php';

// Koneksi database MySQLi CivicHub.
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT'] . '/CivicHub-2/config/database.php';
}

$u = require_login();
$admin = ($u['role'] ?? '') === 'admin';

$k = trim($_GET['k'] ?? '');
$q = trim($_GET['q'] ?? '');

$list = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['hapus_berita'])) {

    if (!$admin) {
        http_response_code(403);
        exit('Akses ditolak.');
    }

    csrf_check();

    $id = (int) ($_POST['berita_id'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare(
            'DELETE FROM berita WHERE id = ? LIMIT 1'
        );

        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            flash('Berita berhasil dihapus.');
        } else {
            flash('Berita tidak ditemukan.', 'err');
        }

        $stmt->close();
    }

    redirect('berita.php');
}

// Ambil berita langsung dari MySQL.
$sql = 'SELECT * FROM berita WHERE 1=1';
$params = [];
$types = '';

if (in_array($k, KATEGORI_BERITA, true)) {
    $sql .= ' AND kategori = ?';
    $params[] = $k;
    $types .= 's';
}

if ($q !== '') {
    $sql .= ' AND (judul LIKE ? OR ringkasan LIKE ?)';
    $keyword = '%' . $q . '%';
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= 'ss';
}

$sql .= ' ORDER BY tanggal DESC, id DESC';

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Gagal menyiapkan query berita: ' . e($conn->error));
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $list[] = $row;
}

$stmt->close();

app_start(
    t('Berita'),
    'berita',
    t('Berita'),
    t('Selamat datang para warga di halaman ini, pusat informasi warga.')
);
?>

<div class="toolbar">
    <div class="chips">
        <a class="chip <?= $k === '' ? 'on' : '' ?>" href="berita.php">
            <?= e(t('Terakhir')) ?>
        </a>

        <?php foreach (KATEGORI_BERITA as $kat): ?>
            <a
                class="chip <?= $k === $kat ? 'on' : '' ?>"
                href="berita.php?k=<?= urlencode($kat) ?>"
            >
                <?= e($kat) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div style="display:flex;gap:10px;align-items:center">
        <form class="input" method="get" style="min-width:260px" role="search">
            <?php if ($k !== ''): ?>
                <input type="hidden" name="k" value="<?= e($k) ?>">
            <?php endif; ?>

            <?= icon('search') ?>

            <input
                name="q"
                value="<?= e($q) ?>"
                placeholder="<?= e(t('Cari berita')) ?>"
                aria-label="Cari berita"
            >
        </form>

        <?php if ($admin): ?>
            <a class="btn btn-dark" href="berita_form.php">
                <?= icon('plus') ?><?= e(t('Tambah Berita')) ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$list): ?>

    <div class="empty">
        <h3><?= e(t('Berita tidak ditemukan')) ?></h3>
        <p><?= e(t('Coba kategori lain atau hapus kata kunci pencarian.')) ?></p>
    </div>

<?php else: ?>

<div class="grid2">
    <?php foreach ($list as $b): ?>
        <div class="news-card-wrapper">

            <!-- Kartu berita -->
            <a
                class="news"
                href="berita_detail.php?id=<?= (int) $b['id'] ?>"
            >
                <div class="news-body">

                    <?= berita_thumb($b) ?>

                    <div>
                        <div class="news-meta">
                            <span></span>

                            <span class="kat <?= e(kategori_class($b['kategori'])) ?>">
                                <?= e(t($b['kategori'])) ?>
                            </span>
                        </div>

                        <h3><?= e(t($b['judul'])) ?></h3>

                        <div class="date">
                            <?= icon('calendar') ?>
                            <?= e(tgl($b['tanggal'])) ?>
                        </div>

                        <p><?= e(t($b['ringkasan'])) ?></p>
                    </div>

                </div>

                <div class="news-foot">
                    <?= e(t('Baca Selengkapnya')) ?>
                    <?= icon('arrow') ?>
                </div>
            </a>

            <!-- Tombol hapus khusus admin -->
            <?php if ($admin): ?>
                <div class="news-admin-actions">
                    <form
                        method="post"
                        action="berita.php"
                        onsubmit="return confirm('Yakin ingin menghapus berita ini? Berita yang dihapus tidak dapat dikembalikan.');"
                    >
                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="berita_id"
                            value="<?= (int) $b['id'] ?>"
                        >

                        <button
                            type="submit"
                            name="hapus_berita"
                            value="1"
                            class="btn-delete-news"
                        >
                            <?= icon('trash') ?>
                            <span>Hapus Berita</span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php app_end(); ?>