<?php
require __DIR__ . '/includes/layout.php';

// Koneksi database MySQL
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT'] . '/CivicHub-2/config/database.php';
}

// Pastikan hanya admin yang bisa menambah berita
$u = require_admin();

$old = [
    'judul' => '',
    'kategori' => 'Pengumuman',
    'ringkasan' => '',
    'isi' => ''
];

$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['judul'] = trim($_POST['judul'] ?? '');
    $old['kategori'] = in_array(
        $_POST['kategori'] ?? '',
        KATEGORI_BERITA,
        true
    ) ? $_POST['kategori'] : 'Pengumuman';

    $old['ringkasan'] = trim($_POST['ringkasan'] ?? '');
    $old['isi'] = trim($_POST['isi'] ?? '');

    // Validasi input
    if ($old['judul'] === '') {
        $err['judul'] = 'Judul berita wajib diisi.';
    }

    if ($old['ringkasan'] === '') {
        $err['ringkasan'] = 'Ringkasan wajib diisi.';
    }

    if (mb_strlen($old['isi']) < 20) {
        $err['isi'] = 'Isi berita minimal 20 karakter.';
    }

    if (!$err) {
        $tanggal = date('Y-m-d');
        $userId = (int) ($u['id'] ?? 0);

        // Simpan berita langsung ke MySQL
        $sql = "INSERT INTO berita
                (user_id, judul, ringkasan, isi, kategori, tanggal)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $err['database'] = 'Gagal menyiapkan query: ' . $conn->error;
        } else {
            $stmt->bind_param(
                'isssss',
                $userId,
                $old['judul'],
                $old['ringkasan'],
                $old['isi'],
                $old['kategori'],
                $tanggal
            );

            if ($stmt->execute()) {
                $id = $conn->insert_id;
                
            $stmt->close();

            // Kirim notifikasi kepada semua warga yang bukan admin.
            $stmtWarga = $conn->prepare(
                "SELECT id FROM users WHERE role != 'admin'"
            );
            $stmtWarga->execute();
            $resultWarga = $stmtWarga->get_result();

            $stmtNotif = $conn->prepare(
                "INSERT INTO notifikasi (user_id, judul, pesan, link)
                VALUES (?, ?, ?, ?)"
            );

            $judulNotif = 'Berita terbaru';
            $pesanNotif = 'Berita baru telah ditambahkan: ' . $old['judul'];
            $linkNotif = 'berita_detail.php?id=' . $id;

            while ($warga = $resultWarga->fetch_assoc()) {
                $wargaId = (int) $warga['id'];

                $stmtNotif->bind_param(
                    'isss',
                    $wargaId,
                    $judulNotif,
                    $pesanNotif,
                    $linkNotif
                );

                $stmtNotif->execute();
            }

            $stmtNotif->close();
            $stmtWarga->close();

            flash('Berita berhasil ditambahkan.');
            redirect('berita_detail.php?id=' . $id);

            } else {
                $err['database'] = 'Gagal menyimpan berita: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

function berr(array $err, string $k): string
{
    return isset($err[$k])
        ? '<p class="err-msg" role="alert">' . e($err[$k]) . '</p>'
        : '';
}

app_start(
    'Tambah Berita',
    'berita',
    'Tambah Berita',
    'Bagikan kabar dan pengumuman terbaru untuk warga.'
);
?>

<div class="card" style="max-width:820px;margin:0 auto">

    <?php if (isset($err['database'])): ?>
        <p class="err-msg" role="alert">
            <?= e($err['database']) ?>
        </p>
    <?php endif; ?>

    <form class="form" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="row2">
            <div class="field">
                <label for="judul">
                    Judul berita <span class="req">*</span>
                </label>

                <div class="input <?= isset($err['judul']) ? 'has-err' : '' ?>">
                    <input
                        id="judul"
                        name="judul"
                        value="<?= e($old['judul']) ?>"
                        required
                    >
                </div>

                <?= berr($err, 'judul') ?>
            </div>

            <div class="field">
                <label for="kategori">Kategori</label>

                <div class="input">
                    <select id="kategori" name="kategori">
                        <?php foreach (KATEGORI_BERITA as $kat): ?>
                            <option
                                value="<?= e($kat) ?>"
                                <?= $old['kategori'] === $kat ? 'selected' : '' ?>
                            >
                                <?= e($kat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="field">
            <label for="ringkasan">
                Ringkasan <span class="req">*</span>
            </label>

            <div class="input <?= isset($err['ringkasan']) ? 'has-err' : '' ?>">
                <input
                    id="ringkasan"
                    name="ringkasan"
                    value="<?= e($old['ringkasan']) ?>"
                    placeholder="Satu kalimat yang tampil di daftar berita"
                    required
                >
            </div>

            <?= berr($err, 'ringkasan') ?>
        </div>



<div class="field">
    <label for="isi">
        Isi berita <span class="req">*</span>
    </label>

    <div class="input">
        <textarea
            id="isi"
            name="isi"
            style="min-height:300px"
            placeholder="Tulis isi berita di sini..."
            required
        ><?= e($old['isi']) ?></textarea>
    </div>

    <?= berr($err, 'isi') ?>
</div>


        <div class="form-actions">
            <a class="btn btn-out" href="berita.php">Batal</a>
            <button class="btn btn-dark" type="submit">
                Terbitkan berita
            </button>
        </div>
    </form>
</div>




<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dark =
        document.documentElement.getAttribute('data-theme') === 'dark';

    tinymce.init({
           selector: '#isi',
    height: 500,
    min_height: 450,
    menubar: false,
    statusbar: false,
    branding: false,
    promotion: false,
    plugins: 'lists link',
    toolbar:
            'undo redo | blocks | bold italic underline | bullist numlist | link | removeformat',

        skin: dark ? 'oxide-dark' : 'oxide',
        content_css: dark ? 'dark' : 'default',

        content_style: dark
            ? 'body { background-color: #101d15; color: #e8f3ec; font-family: Arial, sans-serif; font-size: 15px; } a { color: #f5d34f; }'
            : 'body { font-family: Arial, sans-serif; font-size: 15px; }',

        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
        }
    });

    const form = document.querySelector('#isi').closest('form');

    if (form) {
        form.addEventListener('submit', function () {
            tinymce.triggerSave();
        });
    }
});
</script>

<?php app_end(); ?>