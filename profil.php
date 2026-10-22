<?php
require __DIR__ . '/includes/layout.php';

// Koneksi database
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT'] . '/CivicHub-2/config/database.php';
}

$u = require_login();

$idUser = (int)($u['id'] ?? 0);

if ($idUser <= 0) {
    flash('Sesi akun tidak valid. Silakan login kembali.', 'err');
    redirect('login.php');
}

// Ambil data terbaru dari database
function ambil_profil(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        "SELECT id, nama, email, nik, password, role, alamat, rt, rw, verified
         FROM users WHERE id = ? LIMIT 1"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();

    return $data ?: null;
}

$u = ambil_profil($conn, $idUser);

if (!$u) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$tab = $_GET['tab'] ?? 'data';

if (!in_array($tab, [
    'data', 'edit', 'verifikasi', 'notifikasi', 'bahasa', 'tema'
], true)) {
    $tab = 'data';
}

$err = [];
$old = $u;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $aksi = $_POST['aksi'] ?? '';
    // ==========================================
    // GANTI BAHASA
    // ==========================================
    if ($aksi === 'bahasa') {
    $nilai = $_POST['nilai'] ?? 'id';

    if (in_array($nilai, ['id', 'en'], true)) {
        set_pref('lang', $nilai);

        flash(
            $nilai === 'en'
                ? 'English selected.'
                : 'Bahasa Indonesia digunakan.'
        );
    }

    redirect('profil.php?tab=bahasa');
    }
    // ==========================================
    // EDIT PROFIL
    // ==========================================
    if ($aksi === 'edit') {
        $old = array_merge($u, [
            'nama'   => trim($_POST['nama'] ?? ''),
            'email'  => strtolower(trim($_POST['email'] ?? '')),
            'nik'    => preg_replace('/\s+/', '', $_POST['nik'] ?? ''),
            'alamat' => trim($_POST['alamat'] ?? ''),
            'rt'     => preg_replace('/\D/', '', $_POST['rt'] ?? ''),
            'rw'     => preg_replace('/\D/', '', $_POST['rw'] ?? ''),
        ]);

        $sandi = $_POST['sandi'] ?? '';

        if ($old['nama'] === '') {
            $err['nama'] = 'Nama wajib diisi.';
        }

        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'Format email belum benar.';
        } else {
            // Cek email digunakan akun lain atau tidak
            $stmt = $conn->prepare(
                "SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1"
            );

            $stmt->bind_param('si', $old['email'], $idUser);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $err['email'] = 'Email sudah dipakai akun lain.';
            }

            $stmt->close();
        }

        if (!preg_match('/^\d{16}$/', $old['nik'])) {
            $err['nik'] = 'NIK harus 16 digit angka.';
        } else {
            // Cek NIK duplikat
            $stmt = $conn->prepare(
                "SELECT id FROM users WHERE nik = ? AND id <> ? LIMIT 1"
            );

            $stmt->bind_param('si', $old['nik'], $idUser);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $err['nik'] = 'NIK sudah digunakan akun lain.';
            }

            $stmt->close();
        }

        if ($old['rt'] === '' || $old['rw'] === '') {
            $err['rt'] = 'RT dan RW wajib diisi.';
        }

        if ($sandi !== '' && strlen($sandi) < 8) {
            $err['sandi'] = 'Sandi baru minimal 8 karakter.';
        }

        if (!$err) {
            $rt = str_pad($old['rt'], 2, '0', STR_PAD_LEFT);
            $rw = str_pad($old['rw'], 2, '0', STR_PAD_LEFT);

            // Jika email atau NIK berubah, verifikasi diulang
            $emailBerubah = $old['email'] !== $u['email'];
            $nikBerubah = $old['nik'] !== ($u['nik'] ?? '');

            $verified = ($emailBerubah || $nikBerubah)
                ? 0
                : (int)($u['verified'] ?? 0);

            if ($sandi !== '') {
                $hash = password_hash($sandi, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "UPDATE users
                     SET nama = ?, email = ?, nik = ?, alamat = ?,
                         rt = ?, rw = ?, password = ?, verified = ?
                     WHERE id = ?"
                );

                $stmt->bind_param(
                    'sssssssii',
                    $old['nama'],
                    $old['email'],
                    $old['nik'],
                    $old['alamat'],
                    $rt,
                    $rw,
                    $hash,
                    $verified,
                    $idUser
                );
            } else {
                $stmt = $conn->prepare(
                    "UPDATE users
                     SET nama = ?, email = ?, nik = ?, alamat = ?,
                         rt = ?, rw = ?, verified = ?
                     WHERE id = ?"
                );

                $stmt->bind_param(
                    'ssssssii',
                    $old['nama'],
                    $old['email'],
                    $old['nik'],
                    $old['alamat'],
                    $rt,
                    $rw,
                    $verified,
                    $idUser
                );
            }

            if ($stmt->execute()) {
                $stmt->close();

                flash('Profil berhasil disimpan.');
                redirect('profil.php?tab=data');
            }

            $stmt->close();
            $err['database'] = 'Gagal menyimpan profil. Silakan coba lagi.';
        }

        $tab = 'edit';
    }

    // ==========================================
    // VERIFIKASI
    // ==========================================
    if ($aksi === 'verifikasi') {
        $stmt = $conn->prepare(
            "UPDATE users SET verified = 1 WHERE id = ?"
        );

        $stmt->bind_param('i', $idUser);

        if ($stmt->execute()) {
            $stmt->close();

            flash('Akun Anda sudah terverifikasi.');
            redirect('profil.php?tab=verifikasi');
        }

        $stmt->close();
        flash('Verifikasi gagal. Silakan coba lagi.', 'err');
        redirect('profil.php?tab=verifikasi');
    }

    // ==========================================
    // TANDAI NOTIFIKASI DIBACA
    // Notifikasi masih menggunakan session
    // ==========================================
    
    if ($aksi === 'baca') {
        $conn = $GLOBALS['conn'] ?? require __DIR__ . '/config/database.php';

        $notifId = (int) ($_POST['id'] ?? 0);
        $userId = (int) $u['id'];

        $stmt = $conn->prepare(
            'UPDATE notifikasi
            SET dibaca = 1
            WHERE id = ? AND user_id = ?'
        );
        $stmt->bind_param('ii', $notifId, $userId);
        $stmt->execute();
        $stmt->close();

        redirect(
            'profil.php?tab=notifikasi&f=' .
            urlencode($_POST['f'] ?? '')
        );
    }

    if ($aksi === 'baca_semua') {
        $conn = $GLOBALS['conn'] ?? require __DIR__ . '/config/database.php';

        $userId = (int) $u['id'];

        $stmt = $conn->prepare(
            'UPDATE notifikasi
            SET dibaca = 1
            WHERE user_id = ? AND dibaca = 0'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        redirect('profil.php?tab=notifikasi');
    }


    if ($aksi === 'baca_semua') {
        foreach ($_SESSION['notif'] ?? [] as &$n) {
            $n['dibaca'] = true;
        }

        unset($n);

        redirect('profil.php?tab=notifikasi');
    }

    // ==========================================
    // PENGATURAN TEMA
    // ==========================================
    if (
        $aksi === 'tema' &&
        in_array($_POST['nilai'] ?? '', ['light', 'dark'], true)
    ) {
        set_pref('tema', $_POST['nilai']);

        flash(
            $_POST['nilai'] === 'dark'
                ? 'Tema gelap diaktifkan.'
                : 'Tema terang diaktifkan.'
        );

        redirect('profil.php?tab=tema');
    }
}

$u = ambil_profil($conn, $idUser) ?? $u;

function perr(array $err, string $k): string { return isset($err[$k]) ? '<p class="err-msg" role="alert">' . e($err[$k]) . '</p>' : ''; }

$menu = [
    'AKUN' => [
        'data'       => ['user', 'Data Diri', 'Lihat data pribadi Anda'],
        'edit'       => ['edit', 'Edit Profil', 'Ubah nama, alamat, dan sandi'],
        'verifikasi' => ['shield', 'Verifikasi', 'Status verifikasi akun'],
    ],
    'PENGATURAN' => [
        'notifikasi' => ['bell', 'Notifikasi', 'Pengumuman dan pembaruan surat'],
        'bahasa'     => ['globe', 'Bahasa', 'Pilih bahasa aplikasi'],
        'tema'       => ['sun', 'Tema', 'Terang atau gelap'],
    ],
];

app_start('Profil', 'profil', 'Profil Saya', 'Kelola akun, notifikasi, dan tampilan CivicHub.');
?>
<div class="settings">
    <aside class="pcard">
        
    <div class="pcard-top">
        <span class="av">
            <?= e(mb_strtoupper(mb_substr($u['nama'], 0, 1))) ?>
        </span>

        <div class="profile-identity">
            <strong><?= e($u['nama']) ?></strong>

            <small class="profile-rt">
                <span><?= icon('pin') ?></span>
                RT <?= e($u['rt']) ?> / RW <?= e($u['rw']) ?>
            </small>

            <small class="profile-address">
                <?= e($u['alamat']) ?>
            </small>

            <span class="role">
                <?= $u['role'] === 'admin' ? 'Administrator' : 'Warga' ?>
            </span>
        </div>
    </div>

        <?php foreach ($menu as $grp => $items): ?>
            <div class="menu-group">
                <h4><?= e(t($grp)) ?></h4>
                <?php foreach ($items as $key => [$ic, $lbl, $desc]): ?>
                    <a class="menu-item <?= $tab === $key ? 'on' : '' ?>" href="profil.php?tab=<?= $key ?>">
                        <span class="mi"><?= icon($ic) ?></span>
                        <span><b><?= e(t($lbl)) ?></b><small><?= e(t($desc)) ?></small></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="pcard-foot">
    <a class="btn btn-red btn-block" href="logout.php" id="btnLogoutProfile">
        <?= icon('logout') ?>Keluar
    </a>
</div>
    </aside>

    <section class="card panel">
<?php if ($tab === 'data'): ?>
        <h2><?= e(t('Data Diri')) ?></h2>
        <p class="lead"><?= e(t('Data ini dipakai untuk memproses pengajuan suratmu.')) ?></p>
        <dl class="dl">
            <div><dt><?= e(t('Nama Lengkap')) ?></dt><dd><?= icon('user') ?><?= e($u['nama']) ?></dd></div>
            <div><dt><?= e(t('Email')) ?></dt><dd><?= icon('mail') ?><?= e($u['email']) ?></dd></div>
            <div><dt><?= e(t('NIK')) ?></dt><dd><?= icon('id') ?><?= e($u['nik']) ?></dd></div>
            <div><dt><?= e(t('Alamat')) ?></dt><dd><?= icon('pin') ?><?= e($u['alamat']) ?></dd></div>
            <div><dt><?= e(t('RT / RW')) ?></dt><dd><?= e($u['rt']) ?> / <?= e($u['rw']) ?></dd></div>
            <div><dt><?= e(t('Sandi')) ?></dt><dd><?= icon('lock') ?>••••••••</dd></div>
        </dl>
        <div class="actions" style="margin-top:20px"><a class="btn btn-lime" href="profil.php?tab=edit"><?= icon('edit') ?>Edit Profil</a></div>

<?php elseif ($tab === 'edit'): ?>
        <h2><?= e(t('Edit Profil')) ?></h2>
        <p class="lead"><?= e(t('Mengubah email atau NIK akan membuat akunmu perlu diverifikasi ulang.')) ?></p>
        <?php if (isset($err['database'])): ?>
        <p class="err-msg" role="alert">
            <?= e($err['database']) ?>
        </p>
        <?php endif; ?>
        <form class="form" method="post" novalidate>
            <?= csrf_field() ?><input type="hidden" name="aksi" value="edit">
            <div class="field"><label for="nama">Nama</label>
                <div class="input <?= isset($err['nama']) ? 'has-err' : '' ?>"><?= icon('user') ?><input id="nama" name="nama" value="<?= e($old['nama']) ?>"></div><?= perr($err, 'nama') ?></div>
            <div class="row2">
                <div class="field"><label for="email">Email address</label>
                    <div class="input <?= isset($err['email']) ? 'has-err' : '' ?>"><?= icon('mail') ?><input id="email" type="email" name="email" value="<?= e($old['email']) ?>"></div><?= perr($err, 'email') ?></div>
                <div class="field"><label for="nik">NIK</label>
                    <div class="input <?= isset($err['nik']) ? 'has-err' : '' ?>"><?= icon('id') ?><input id="nik" name="nik" inputmode="numeric" maxlength="16" value="<?= e($old['nik']) ?>"></div><?= perr($err, 'nik') ?></div>
            </div>
            <div class="row2">
                <div class="field"><label for="rt">RT</label>
                    <div class="input <?= isset($err['rt']) ? 'has-err' : '' ?>"><input id="rt" name="rt" inputmode="numeric" maxlength="3" value="<?= e($old['rt']) ?>"></div><?= perr($err, 'rt') ?></div>
                <div class="field"><label for="rw">RW</label>
                    <div class="input"><input id="rw" name="rw" inputmode="numeric" maxlength="3" value="<?= e($old['rw']) ?>"></div></div>
            </div>
            <div class="field"><label for="alamat">Alamat</label>
                <div class="input"><?= icon('pin') ?><input id="alamat" name="alamat" value="<?= e($old['alamat']) ?>"></div></div>
            <div class="field"><label for="sandi">Sandi baru (kosongkan jika tidak diubah)</label>
                <div class="input <?= isset($err['sandi']) ? 'has-err' : '' ?>"><?= icon('lock') ?><input id="sandi" type="password" name="sandi" autocomplete="new-password"><button type="button" class="eye" data-eye aria-label="Tampilkan sandi"><?= icon('eye') ?></button></div><?= perr($err, 'sandi') ?></div>
            <div class="form-actions">
                <a class="btn btn-out" href="profil.php?tab=data">Batal</a>
                <button class="btn btn-dark" type="submit"><?= icon('check') ?>Simpan perubahan</button>
            </div>
        </form>

<?php elseif ($tab === 'verifikasi'): ?>
        <h2><?= e(t('Verifikasi')) ?></h2>
        <p class="lead"><?= e(t('Akun terverifikasi dapat mengajukan surat tanpa pengecekan ulang.')) ?></p>
        <div class="verify-box">
            <?php if ($u['verified']): ?>
                <div class="big"><?= icon('shield') ?></div>
                <h3><?= e(t('Akun Anda sudah terverifikasi')) ?></h3>
                <p><?= e(t('NIK dan email Anda sudah terdaftar di sistem RT.')) ?></p>
                <a class="btn btn-dark" style="margin-top:20px" href="dashboard.php"><?= e(t('Kembali')) ?></a>
            <?php else: ?>
                <div class="big no"><?= icon('warn') ?></div>
                <h3><?= e(t('Akun Anda belum terverifikasi')) ?></h3>
                <p><?= e(t('Verifikasi NIK dan email agar pengajuan suratmu diprioritaskan.')) ?></p>
                <form method="post" style="margin-top:20px"><?= csrf_field() ?><input type="hidden" name="aksi" value="verifikasi">
                    <button class="btn btn-dark" type="submit"><?= e(t('Verifikasi sekarang')) ?></button></form>
                <p class="k" style="margin-top:10px"><?= e(t('Mode demo: verifikasi langsung disetujui.')) ?></p>
            <?php endif; ?>
        </div>



<?php elseif ($tab === 'notifikasi'):
    $f = $_GET['f'] ?? '';

    $conn = $GLOBALS['conn'] ?? require __DIR__ . '/config/database.php';
    $userId = (int) $u['id'];

    $sqlNotif = "SELECT id, judul, pesan, link, dibaca, created_at
                 FROM notifikasi
                 WHERE user_id = ?";

    if ($f === 'belum') {
        $sqlNotif .= " AND dibaca = 0";
    }

    $sqlNotif .= " ORDER BY created_at DESC";

    $stmtNotif = $conn->prepare($sqlNotif);
    $stmtNotif->bind_param('i', $userId);
    $stmtNotif->execute();

    $resultNotif = $stmtNotif->get_result();
    $ls = $resultNotif->fetch_all(MYSQLI_ASSOC);
    $stmtNotif->close();

    // Kelompokkan notifikasi berdasarkan tanggal.
    $hariIni = date('Y-m-d');

    foreach ($ls as &$n) {
        $n['hari_ini'] = (
            date('Y-m-d', strtotime($n['created_at'])) === $hariIni
        );
    }
    unset($n);

    $grup = [
        'Hari Ini' => array_filter($ls, fn($n) => $n['hari_ini']),
        'Sebelumnya' => array_filter($ls, fn($n) => !$n['hari_ini'])
    ];
?>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <h2><?= e(t('Notifikasi')) ?></h2>

        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="baca_semua">

            <button class="btn btn-out btn-sm" type="submit">
                <?= e(t('Tandai semua dibaca')) ?>
            </button>
        </form>
    </div>

    <div class="chips" style="margin:14px 0 6px">
        <a class="chip <?= $f === '' ? 'on' : '' ?>"
           href="profil.php?tab=notifikasi">
            <?= e(t('Semua')) ?>
        </a>

        <a class="chip <?= $f === 'belum' ? 'on' : '' ?>"
           href="profil.php?tab=notifikasi&f=belum">
            <?= e(t('Belum Dibaca')) ?>
        </a>
    </div>

    <?php if (!$ls): ?>
        <div class="empty" style="margin-top:16px">
            <h3><?= e(t('Tidak ada notifikasi')) ?></h3>
            <p><?= e(t('Semua sudah terbaca.')) ?></p>
        </div>
    <?php endif; ?>

    <?php foreach ($grup as $judulGrup => $items): ?>
        <?php if (!$items) continue; ?>

        <div class="group-h">
            <?= e($judulGrup) ?>
        </div>

        <?php foreach ($items as $n): ?>
            <form method="post">
                <?= csrf_field() ?>

                <input type="hidden" name="aksi" value="baca">
                <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                <input type="hidden" name="f" value="<?= e($f) ?>">

                <button
                    class="notif <?= !$n['dibaca'] ? 'unread' : '' ?>"
                    type="submit"
                    style="width:100%;text-align:left"
                >
                    <span class="oi">
                        <?= icon(
                            stripos($n['judul'], 'surat') !== false
                                ? 'doc'
                                : 'bell'
                        ) ?>
                    </span>

                    <span style="flex:1">
                        <b><?= e($n['judul']) ?></b>

                        <small><?= e($n['pesan']) ?></small>

                        <span class="badge">
                            <?= e(date(
                                'd-m-Y H:i',
                                strtotime($n['created_at'])
                            )) ?>
                        </span>
                    </span>

                    <?php if (!$n['dibaca']): ?>
                        <span class="badge st-ver">Baru</span>
                    <?php endif; ?>
                </button>
            </form>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <?php elseif ($tab === 'bahasa'): $b = lang(); ?>
    <h2><?= e(t('Bahasa')) ?></h2>
    <p class="lead"><?= e(t('Pilihan bahasa')) ?></p>

    <?php
    $pilihanBahasa = [
        'id' => [
            'Bahasa Indonesia',
            'Pilihan utama bahasa'
        ],
        'en' => [
            'English',
            'Click here to change language'
        ]
    ];
    ?>

    <?php foreach ($pilihanBahasa as $kode => [$nm, $ds]): ?>
        <form method="post">
            <?= csrf_field() ?>

            <input type="hidden" name="aksi" value="bahasa">
            <input type="hidden" name="nilai" value="<?= e($kode) ?>">

            <button class="opt" type="submit">
                <span class="oi"><?= icon('globe') ?></span>

                <span>
                    <b><?= e($nm) ?></b>
                    <small><?= e($ds) ?></small>

                    <?php if ($b === $kode): ?>
                        <br>
                        <span class="used"><?= e(t('Digunakan')) ?></span>
                    <?php endif; ?>
                </span>
            </button>
        </form>
    <?php endforeach; ?>

<?php elseif ($tab === 'tema'): $t = $_SESSION['tema']; ?>
        <h2><?= e(t('Tema')) ?></h2>
        <p class="lead"><?= e(t('Pilihan tema')) ?></p>
        <?php foreach (['light' => ['Light theme', 'Pilihan utama tema', 'sun'], 'dark' => ['Dark theme', 'Klik di sini untuk ganti tema', 'moon']] as $kode => [$nm, $ds, $ic]): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="tema"><input type="hidden" name="nilai" value="<?= $kode ?>">
                <button class="opt" type="submit"><span class="oi">
            <?= $ic === 'moon' ? '🌙' : '☀️' ?>
        </span>
                    <span><b><?= e($nm) ?></b><small><?= e($ds) ?></small><?= $t === $kode ? '<br><span class="used">Digunakan</span>' : '' ?></span></button></form>
        <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnLogoutProfile');

    if (!btn) return;

    btn.addEventListener('click', function (e) {
        e.preventDefault();

        Swal.fire({
            title: <?= json_encode(t('Yakin ingin keluar?')) ?>,
            text: <?= json_encode(t('Anda akan keluar dari akun ini.')) ?>,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#e3342f',
            cancelButtonColor: '#6b7280',
            confirmButtonText: <?= json_encode(t('Ya, keluar!')) ?>,
            cancelButtonText: <?= json_encode(t('Batal')) ?>,
                        reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = btn.href;
            }
        });
    });
});
</script>
<?php app_end(); ?>
