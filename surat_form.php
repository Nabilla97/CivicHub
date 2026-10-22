<?php
require __DIR__ . '/includes/layout.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT'] . '/CivicHub-2/config/database.php';
}

$u = require_login();

if ($u['role'] === 'admin') {
    flash('Administrator hanya dapat melihat surat yang masuk.', 'err');
    redirect('surat.php');
}

$id = (int) ($_GET['id'] ?? 0);
$s = null;
$edit = false;
$err = [];

/*
 * Sesuaikan $conn dengan variabel koneksi database
 * yang digunakan di project CivicHub lo.
 */

// Ambil data surat untuk mode edit (MySQLi).
if ($id > 0) {
    $userId = (int) $u['id'];

    $stmt = $conn->prepare(
        'SELECT * FROM surat WHERE id = ? AND user_id = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $s = $result->fetch_assoc();
    $stmt->close();

    if (!$s || $s['status'] === 'Selesai') {
        flash('Pengajuan ini tidak bisa diubah.', 'err');
        redirect('surat.php');
    }

    $edit = true;
}

$old = $s ?? [
    'jenis' => JENIS_SURAT[0],
    'judul' => '',
    'deskripsi' => '',
    'tanggal' => date('Y-m-d'),
    'lokasi' => $u['alamat'] ?? '',
    'urgensi' => 'Sedang',
    'foto' => null,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['jenis'] = in_array(
        $_POST['jenis'] ?? '',
        JENIS_SURAT,
        true
    ) ? $_POST['jenis'] : JENIS_SURAT[0];

    $old['judul'] = trim($_POST['judul'] ?? '');
    $old['deskripsi'] = trim($_POST['deskripsi'] ?? '');
    $old['tanggal'] = $_POST['tanggal'] ?? '';
    $old['lokasi'] = trim($_POST['lokasi'] ?? '');
    $old['urgensi'] = in_array(
        $_POST['urgensi'] ?? '',
        ['Rendah', 'Sedang', 'Tinggi'],
        true
    ) ? $_POST['urgensi'] : 'Sedang';

    if ($old['judul'] === '') {
        $err['judul'] = 'Judul pengajuan wajib diisi.';
    }

    if (mb_strlen($old['deskripsi']) < 10) {
        $err['deskripsi'] = 'Jelaskan keperluan minimal 10 karakter.';
    }

    $tanggalValid = DateTime::createFromFormat(
        '!Y-m-d',
        $old['tanggal']
    );

    if (
        !$tanggalValid ||
        $tanggalValid->format('Y-m-d') !== $old['tanggal']
    ) {
        $err['tanggal'] = 'Tanggal tidak valid.';
    }

    if ($old['lokasi'] === '') {
        $err['lokasi'] = 'Lokasi/alamat wajib diisi.';
    }

    if (!$err) {
        try {
            // Pertahankan foto lama jika tidak mengunggah foto baru.
            $foto = $old['foto'] ?? null;

            if (
                isset($_FILES['foto']) &&
                $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                $fotoBaru = simpan_foto('foto');

                if ($fotoBaru) {
                    $foto = $fotoBaru;
                } else {
                    $err['foto'] = 'Foto gagal diunggah. Periksa format dan ukuran file.';
                }
            }

            if (!$err) {
                if ($edit) {
                    // UPDATE hanya untuk surat milik user yang sedang login
                    // dan belum berstatus Selesai.
                    $userId = (int) $u['id'];
                    $statusSelesai = 'Selesai';

                    $stmt = $conn->prepare(
                        'UPDATE surat
                         SET jenis = ?,
                             judul = ?,
                             deskripsi = ?,
                             tanggal = ?,
                             lokasi = ?,
                             urgensi = ?,
                             foto = ?
                         WHERE id = ?
                           AND user_id = ?
                           AND status <> ?'
                    );

                    $stmt->bind_param(
                        'sssssssiis',
                        $old['jenis'],
                        $old['judul'],
                        $old['deskripsi'],
                        $old['tanggal'],
                        $old['lokasi'],
                        $old['urgensi'],
                        $foto,
                        $id,
                        $userId,
                        $statusSelesai
                    );

                    $stmt->execute();
                    $stmt->close();

                    flash('Pengajuan berhasil diperbarui.');
                    redirect('surat.php');
                } else {
                    // INSERT pengajuan baru.
                    $userId = (int) $u['id'];
                    $status = 'Diajukan';

                    $stmt = $conn->prepare(
                        'INSERT INTO surat
                         (user_id, jenis, judul, deskripsi, tanggal,
                          lokasi, urgensi, status, foto)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );

                    $stmt->bind_param(
                        'issssssss',
                        $userId,
                        $old['jenis'],
                        $old['judul'],
                        $old['deskripsi'],
                        $old['tanggal'],
                        $old['lokasi'],
                        $old['urgensi'],
                        $status,
                        $foto
                    );

                    $stmt->execute();
                    $newId = (int) $conn->insert_id;
                    $stmt->close();

                    // Kirim notifikasi kepada semua admin.
                    $stmtAdmin = $conn->prepare(
                        "SELECT id FROM users WHERE role = 'admin'"
                    );
                    $stmtAdmin->execute();
                    $resultAdmin = $stmtAdmin->get_result();

                    $stmtNotif = $conn->prepare(
                        "INSERT INTO notifikasi
                            (user_id, judul, pesan, link)
                        VALUES (?, ?, ?, ?)"
                    );

                    $judulNotif = 'Pengajuan surat baru';
                    $pesanNotif = 'Ada pengajuan surat baru dari ' . $u['nama'] . '.';
                    $linkNotif = 'surat_detail.php?id=' . $newId;

                    while ($adminUser = $resultAdmin->fetch_assoc()) {
                        $adminId = (int) $adminUser['id'];

                        $stmtNotif->bind_param(
                            'isss',
                            $adminId,
                            $judulNotif,
                            $pesanNotif,
                            $linkNotif
                        );

                        $stmtNotif->execute();
                    }

                    $stmtNotif->close();
                    $stmtAdmin->close();

                    flash('Pengajuan berhasil dikirim.');
                    redirect('surat.php');
                }
            }
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            $err['database'] = 'Pengajuan gagal disimpan. Periksa konfigurasi database.';
        }
    }
}

function ferr(array $err, string $k): string
{
    return isset($err[$k])
        ? '<p class="err-msg" role="alert">' . e($err[$k]) . '</p>'
        : '';
}

app_start(
    $edit ? 'Ubah Pengajuan' : 'Ajukan Surat',
    'surat',
    $edit ? 'Ubah Pengajuan' : 'Ajukan Surat Baru',
    'Isi data dengan lengkap agar pengajuanmu cepat diproses.'
);
?>

<div class="card" style="max-width:820px;margin:0 auto">

    <?php if (isset($err['database'])): ?>
        <p class="err-msg" role="alert">
            <?= e($err['database']) ?>
        </p>
    <?php endif; ?>

    <form class="form" method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>

        <div class="row2">
            <div class="field">
                <label for="jenis">Jenis surat</label>
                <div class="input">
                    <select id="jenis" name="jenis">
                        <?php foreach (JENIS_SURAT as $j): ?>
                            <option value="<?= e($j) ?>"
                                <?= $old['jenis'] === $j ? 'selected' : '' ?>>
                                <?= e($j) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="urgensi">Tingkat urgensi</label>
                <div class="input">
                    <select id="urgensi" name="urgensi">
                        <?php foreach (['Rendah', 'Sedang', 'Tinggi'] as $x): ?>
                            <option value="<?= e($x) ?>"
                                <?= $old['urgensi'] === $x ? 'selected' : '' ?>>
                                <?= e($x) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="field">
            <label for="judul">
                Judul pengajuan <span class="req">*</span>
            </label>
            <div class="input <?= isset($err['judul']) ? 'has-err' : '' ?>">
                <input
                    id="judul"
                    name="judul"
                    value="<?= e($old['judul']) ?>"
                    placeholder="Contoh: Domisili untuk pendaftaran sekolah"
                    required
                >
            </div>
            <?= ferr($err, 'judul') ?>
        </div>

        <div class="row2">
            <div class="field">
                <label for="tanggal">Tanggal pengajuan</label>
                <div class="input <?= isset($err['tanggal']) ? 'has-err' : '' ?>">
                    <?= icon('calendar') ?>
                    <input
                        id="tanggal"
                        type="date"
                        name="tanggal"
                        value="<?= e($old['tanggal']) ?>"
                        required
                    >
                </div>
                <?= ferr($err, 'tanggal') ?>
            </div>

            <div class="field">
                <label for="lokasi">
                    Lokasi / alamat <span class="req">*</span>
                </label>
                <div class="input <?= isset($err['lokasi']) ? 'has-err' : '' ?>">
                    <?= icon('pin') ?>
                    <input
                        id="lokasi"
                        name="lokasi"
                        value="<?= e($old['lokasi']) ?>"
                        required
                    >
                </div>
                <?= ferr($err, 'lokasi') ?>
            </div>
        </div>

        <div class="field">
            <label for="deskripsi">
                Deskripsi pengajuan <span class="req">*</span>
            </label>
            <div class="input <?= isset($err['deskripsi']) ? 'has-err' : '' ?>">
                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    placeholder="Jelaskan keperluan surat ini"
                    required
                ><?= e($old['deskripsi']) ?></textarea>
            </div>
            <?= ferr($err, 'deskripsi') ?>
        </div>

        <div class="field">
            <label for="foto">
                Foto lampiran (opsional, JPG/PNG/WebP maks. 3 MB)
            </label>
            <div class="input">
                <?= icon('image') ?>
                <input
                    id="foto"
                    type="file"
                    name="foto"
                    accept="image/jpeg,image/png,image/webp"
                >
            </div>
            <?= ferr($err, 'foto') ?>

            <?php if (!empty($old['foto'])): ?>
                <p>Foto saat ini: <?= e($old['foto']) ?></p>
                <p class="caption">
                    Biarkan kosong jika tidak ingin mengganti foto.
                </p>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <a
                class="btn btn-out"
                href="<?= $edit
                    ? 'surat_detail.php?id=' . (int) $id
                    : 'surat.php' ?>"
            >
                Batal
            </a>

            <button class="btn btn-dark" type="submit">
                <?= $edit ? 'Simpan perubahan' : 'Kirim pengajuan' ?>
            </button>
        </div>
    </form>
</div>

<?php app_end(); ?>