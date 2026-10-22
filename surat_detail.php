<?php
require __DIR__ . '/includes/layout.php';

// Pakai koneksi MySQLi CivicHub.
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT'] . '/CivicHub-2/config/database.php';
}

$u = require_login();
$admin = $u['role'] === 'admin';
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('Pengajuan tidak ditemukan.', 'err');
    redirect('surat.php');
}

// Ambil detail surat langsung dari MySQL.
if ($admin) {
    $stmt = $conn->prepare('SELECT * FROM surat WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
} else {
    $userId = (int) $u['id'];
    $stmt = $conn->prepare(
        'SELECT * FROM surat WHERE id = ? AND user_id = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $id, $userId);
}

$stmt->execute();
$result = $stmt->get_result();
$s = $result->fetch_assoc();
$stmt->close();

if (!$s) {
    flash('Pengajuan tidak ditemukan.', 'err');
    redirect('surat.php');
}

// ADMIN: update status pengajuan dan simpan riwayat timeline.
if (
    $admin &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['aksi'] ?? '') === 'status'
) {
    csrf_check();

    $statusBaru = $_POST['status'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');

    // Validasi status sesuai pilihan yang diizinkan.
    if (!in_array($statusBaru, STATUS, true)) {
        flash('Status tidak valid.', 'err');
        redirect('surat_detail.php?id=' . $id);
    }

    // Baca timeline sebelumnya.
    $timelineLama = [];

    if (!empty($s['timeline'])) {
        $decoded = json_decode($s['timeline'], true);

        if (is_array($decoded)) {
            $timelineLama = $decoded;
        }
    }

    // Catat perubahan status atau catatan admin.
    if (
        $statusBaru !== $s['status'] ||
        $catatan !== ''
    ) {
        $entri = [
            date('Y-m-d'),
            'Status diubah menjadi ' . $statusBaru
        ];

        if ($catatan !== '') {
            $entri[] = mb_substr($catatan, 0, 300);
        }

        $timelineLama[] = $entri;

        $jsonTimeline = json_encode(
            $timelineLama,
            JSON_UNESCAPED_UNICODE
        );

        $stmtUpdate = $conn->prepare(
            'UPDATE surat
             SET status = ?, timeline = ?
             WHERE id = ?
             LIMIT 1'
        );

        $stmtUpdate->bind_param(
            'ssi',
            $statusBaru,
            $jsonTimeline,
            $id
        );

        $stmtUpdate->execute();
        $stmtUpdate->close();

        flash('Status pengajuan berhasil diperbarui.');
    } else {
        flash('Tidak ada perubahan status.');
    }

    redirect('surat_detail.php?id=' . $id);
}

// Ambil data pemohon dari tabel users jika detail sedang dilihat admin.
$pemohon = null;
if ($admin) {
    $userIdPemohon = (int) $s['user_id'];
    $stmtUser = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmtUser->bind_param('i', $userIdPemohon);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $pemohon = $resultUser->fetch_assoc();
    $stmtUser->close();
}

// Pastikan timeline selalu berupa array PHP.
// Jika kolom timeline kosong atau JSON tidak valid, gunakan pesan awal.
$timeline = [];
if (!empty($s['timeline'])) {
    $decodedTimeline = json_decode($s['timeline'], true);
    if (is_array($decodedTimeline)) {
        $timeline = $decodedTimeline;
    }
}
if (!$timeline) {
    $timeline = [
        [
            $s['tanggal'] ?? date('Y-m-d'),
            'Pengajuan diterima oleh sistem.'
        ]
    ];
}

$boleh = !$admin
    && (int) $s['user_id'] === (int) $u['id']
    && $s['status'] !== 'Selesai';

app_start('Detail Pengajuan', 'surat', 'Detail Pengajuan', $s['jenis']);
?>

<div class="toolbar detail-toolbar">
    <a class="btn btn-out btn-sm" href="surat.php">
        <?= icon('back') ?>Kembali
    </a>

    <?php if ($boleh): ?>
    <div class="actions">
        <a class="btn btn-lime btn-sm" href="surat_form.php?id=<?= (int) $s['id'] ?>">
            <?= icon('edit') ?>Ubah
        </a>

        <form method="post" action="surat_hapus.php" id="formHapusSurat">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">

            <button class="btn btn-red btn-sm" type="button" id="btnHapusSurat">
                <?= icon('trash') ?>Hapus
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<div class="detail detail-compact">
    <!-- KOLOM KIRI -->
    <div class="detail-left">
        <div class="detail-photo detail-photo-compact">
            <?php if (!empty($s['foto'])): ?>
                <img src="<?= e($s['foto']) ?>" alt="Foto lampiran pengajuan">
            <?php else: ?>
                <?= icon('image') ?>
            <?php endif; ?>
        </div>

        <div class="card detail-info-card">
            <div class="k">Judul Pengajuan</div>
            <div class="v detail-title"><?= e($s['judul']) ?></div>

            <div class="info detail-info">
                <div>
                    <span class="ic"><?= icon('calendar') ?></span>
                    <small>Tanggal Pengajuan</small>
                    <b><?= e(tgl($s['tanggal'])) ?></b>
                </div>

                <div>
                    <span class="ic"><?= icon('pin') ?></span>
                    <small>Lokasi</small>
                    <b class="detail-location"><?= e($s['lokasi']) ?></b>
                </div>

                <div>
                    <span class="ic"><?= icon('warn') ?></span>
                    <small>Tingkat Urgensi</small>
                    <span class="badge urg-<?= e($s['urgensi']) ?>">
                        <?= e($s['urgensi']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

<!-- KOLOM KANAN -->
<div class="detail-right">

    <!-- TIMELINE: SATU KALI SAJA -->
    <div class="card detail-timeline-card">
        <div class="detail-card-heading">
            <h3><?= e(t('Timeline')) ?></h3>

            <span class="badge <?= e(status_class($s['status'])) ?>">
                <?= e(t($s['status'])) ?>
            </span>
        </div>

        <div class="detail-progress">
            <?= progress_html($s['status']) ?>
        </div>

<ol class="timeline detail-timeline<?= $s['status'] === 'Ditolak' ? ' is-rejected' : '' ?>">
            <?php foreach ($timeline as $item): ?>
                <?php if (is_array($item) && isset($item[0], $item[1])): ?>
                    <li>
                        <time datetime="<?= e($item[0]) ?>">
                            <?= e(tgl($item[0])) ?>
                        </time>

                        <span>
<?= e((string) $item[1]) ?>

                            <?php if (!empty($item[2])): ?>
                                <br>
                                <small style="color:var(--muted)">
                                    <?= e($item[2]) ?>
                                </small>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </div>

<!-- DESKRIPSI PENGAJUAN -->
<div class="card detail-description-card">
    <h3>Deskripsi Pengajuan</h3>

    <div class="detail-description">
        <?= nl2br(e($s['deskripsi'])) ?>
    </div>

    <?php if ($admin && $pemohon): ?>
        <p class="k detail-applicant">
            Diajukan oleh
            <b style="color:var(--ink)">
                <?= e($pemohon['nama'] ?? 'Warga') ?>
            </b>

            <?php if (isset($pemohon['rt'], $pemohon['rw'])): ?>
                · RT <?= e($pemohon['rt']) ?>/RW <?= e($pemohon['rw']) ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<!-- FORM UPDATE STATUS ADMIN -->
<?php if ($admin): ?>
    <div class="card detail-status-card">
        <h3>Ubah Status Pengajuan</h3>

        <form class="form" method="post" style="margin-top: 12px">
            <?= csrf_field() ?>

            <input type="hidden" name="aksi" value="status">

            <div class="field">
                <label for="status">Status Pengajuan</label>

                <div class="input">
                    <select id="status" name="status" required>
                        <?php foreach (STATUS as $st): ?>
                            <option
                                value="<?= e($st) ?>"
                                <?= $s['status'] === $st ? 'selected' : '' ?>
                            >
                                <?= e($st) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="catatan">Catatan untuk Warga</label>

                <div class="input">
                    <textarea
                        id="catatan"
                        name="catatan"
                        maxlength="300"
                        placeholder="Contoh: Dokumen sedang diperiksa oleh pengurus."
                        style="min-height: 90px"
                    ></textarea>
                </div>
            </div>

            <button class="btn btn-dark" type="submit">
                <?= icon('check') ?>
                Simpan Status
            </button>
        </form>
    </div>
<?php endif; ?>

    </div>

</div>

</div>

<?php if ($boleh): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const tombolHapus = document.getElementById('btnHapusSurat');

if (tombolHapus) {
    tombolHapus.addEventListener('click', function () {
        Swal.fire({
            title: <?= json_encode(t('Yakin ingin dihapus?')) ?>,
            text: <?= json_encode(t('Data yang telah dihapus tidak dapat dikembalikan.')) ?>,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#e3342f',
            cancelButtonColor: '#6b7280',
            confirmButtonText: <?= json_encode(t('Ya, hapus!')) ?>,
            cancelButtonText: <?= json_encode(t('Batal')) ?>,
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formHapusSurat').submit();
            }
        });
    });
}
</script>
<?php endif; ?>

<?php app_end(); ?>
