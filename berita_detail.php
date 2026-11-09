<?php
require __DIR__ . '/includes/layout.php';

// Koneksi database
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT'] . '/CivicHub-2/config/database.php';
}

// Pastikan user sudah login
$u = require_login();

// Ambil ID berita
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('Berita tidak ditemukan.', 'err');
    redirect('berita.php');
}

// Ambil berita dari MySQL
$sql = "SELECT b.*, u.nama AS penulis
        FROM berita b
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Gagal menyiapkan query berita: ' . e($conn->error));
}

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();
$b = $result->fetch_assoc();

$stmt->close();

// Jika berita tidak ditemukan
if (!$b) {
    flash('Berita tidak ditemukan.', 'err');
    redirect('berita.php');
}

// Siapkan data penulis dan isi berita
$b['penulis'] = $b['penulis'] ?: 'Admin CivicHub';

// Isi berita di database berupa teks biasa.
// Pecah menjadi beberapa paragraf berdasarkan baris kosong.
$b['isi'] = preg_split('/\R{2,}/', trim($b['isi'] ?? ''));
$b['isi'] = array_values(array_filter(
    array_map('trim', $b['isi']),
    fn($p) => $p !== ''
));

// Kompatibilitas dengan berita tanpa langkah pembayaran
$b['langkah'] = [];

app_start(
    $b['judul'],
    'berita',
    'Berita',
    'Kabar dan pengumuman terbaru seputar pelayanan warga.'
);
?>

<article class="article">

    <div class="article-bar">
        <a
            class="sq"
            href="berita.php"
            aria-label="Kembali ke berita"
        >
            <?= icon('back') ?>
        </a>

        <div class="actions">
            <button
                class="sq"
                id="bookmark"
                type="button"
                aria-label="Simpan berita"
                aria-pressed="false"
            >
                <?= icon('bookmark') ?>
            </button>

            <button
                class="sq"
                id="share"
                type="button"
                aria-label="Bagikan berita"
            >
                <?= icon('share') ?>
            </button>
        </div>
    </div>

    <?= berita_thumb($b, 'hero-img') ?>

    <div style="margin-top:20px">
        <span class="kat <?= e(kategori_class($b['kategori'])) ?>">
            <?= e($b['kategori']) ?>
        </span>
    </div>

    <h1><?= e($b['judul']) ?></h1>

    <div class="author">
        <span class="av">
            <?= icon('user') ?>
        </span>

        <div>
            <strong><?= e($b['penulis']) ?></strong>
            <small><?= e(tgl($b['tanggal'])) ?></small>
        </div>
    </div>

    <?php foreach ($b['isi'] as $p): ?>
        <p class="t"><?= $p ?></p>
    <?php endforeach; ?>

</article>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bookmark = document.getElementById('bookmark');
    const share = document.getElementById('share');

    if (bookmark) {
        bookmark.addEventListener('click', function () {
            const active = this.getAttribute('aria-pressed') === 'true';

            this.setAttribute('aria-pressed', String(!active));
            this.setAttribute(
                'aria-label',
                active ? 'Simpan berita' : 'Hapus dari simpanan'
            );

            // Status tombol hanya berubah di halaman ini.
            // Penyimpanan permanen memerlukan fitur bookmark tersendiri.
        });
    }

    if (share) {
        share.addEventListener('click', async function () {
            const data = {
                title: document.title,
                text: document.querySelector('article h1')?.textContent || document.title,
                url: window.location.href
            };

            try {
                if (navigator.share) {
                    await navigator.share(data);
                } else if (navigator.clipboard) {
                    await navigator.clipboard.writeText(data.url);
                    alert('Link berita berhasil disalin!');
                } else {
                    prompt('Salin link berita ini:', data.url);
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    alert('Link berita: ' + data.url);
                }
            }
        });
    }
});
</script>

<?php app_end(); ?>