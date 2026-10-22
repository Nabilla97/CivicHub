
<?php
require __DIR__ . '/includes/layout.php';
guest_only();

// Koneksi database MySQLi CivicHub.
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT'] . '/CivicHub-2/config/database.php';
}

$step = (int) ($_GET['step'] ?? 1);
if (!in_array($step, [1, 2, 3], true)) {
    redirect('daftar.php?step=1');
}

$reg = $_SESSION['reg'] ?? [];
$err = [];
$old = $reg;

// Kembali mengubah informasi akun.
if (isset($_GET['ubah'])) {
    redirect('daftar.php?step=1');
}

if ($step >= 2 && empty($reg['email'])) {
    redirect('daftar.php?step=1');
}

if ($step === 3 && empty($reg['rt'])) {
    redirect('daftar.php?step=2');
}

// Kirim ulang OTP (mode demo).
if ($step === 3 && isset($_GET['kirim_ulang'])) {
    $_SESSION['reg']['otp'] = (string) random_int(1000, 9999);
    flash('Kode OTP baru sudah dibuat.');
    redirect('daftar.php?step=3');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // LANGKAH 1: INFORMASI AKUN
    if ($step === 1) {
        $old = [
            'nama'  => trim($_POST['nama'] ?? ''),
            'email' => strtolower(trim($_POST['email'] ?? '')),
            'nik'   => preg_replace('/\s+/', '', $_POST['nik'] ?? ''),
        ];

        $sandi = $_POST['sandi'] ?? '';
        $konf  = $_POST['konfirmasi'] ?? '';

        if ($old['nama'] === '') {
            $err['nama'] = 'Nama lengkap wajib diisi.';
        }

        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'Format email belum benar.';
        } else {
            $stmt = $conn->prepare(
                'SELECT id FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->bind_param('s', $old['email']);
            $stmt->execute();
            $ada = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Cek juga akun admin yang mungkin didefinisikan di bootstrap.
            $adminLama = find_user_by_email($old['email']);

            if ($ada || $adminLama) {
                $err['email'] = 'Email sudah terdaftar. Silakan masuk.';
            }
        }

        if (!preg_match('/^\d{16}$/', $old['nik'])) {
            $err['nik'] = 'NIK harus 16 digit angka.';
        } else {
            $stmt = $conn->prepare(
                'SELECT id FROM users WHERE nik = ? LIMIT 1'
            );
            $stmt->bind_param('s', $old['nik']);
            $stmt->execute();
            $nikAda = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($nikAda) {
                $err['nik'] = 'NIK sudah terdaftar.';
            }
        }

        if (strlen($sandi) < 8) {
            $err['sandi'] = 'Sandi minimal 8 karakter.';
        }

        if ($konf !== $sandi) {
            $err['konfirmasi'] = 'Konfirmasi sandi tidak sama.';
        }

        if (!$err) {
            $_SESSION['reg'] = $old + [
                'sandi' => password_hash($sandi, PASSWORD_DEFAULT)
            ];

            redirect('daftar.php?step=2');
        }
    }

    // LANGKAH 2: DATA PRIBADI
    if ($step === 2) {
        $old = $reg;

        $old['rt'] = preg_replace('/\D/', '', $_POST['rt'] ?? '');
        $old['rw'] = preg_replace('/\D/', '', $_POST['rw'] ?? '');
        $old['alamat'] = trim($_POST['alamat'] ?? '');

        if ($old['rt'] === '') {
            $err['rt'] = 'RT wajib diisi.';
        }

        if ($old['rw'] === '') {
            $err['rw'] = 'RW wajib diisi.';
        }

        if ($old['alamat'] === '') {
            $err['alamat'] = 'Alamat wajib diisi.';
        }

        if (!$err) {
            $old['rt'] = str_pad($old['rt'], 2, '0', STR_PAD_LEFT);
            $old['rw'] = str_pad($old['rw'], 2, '0', STR_PAD_LEFT);

            $_SESSION['reg'] = array_merge($reg, [
                'rt' => $old['rt'],
                'rw' => $old['rw'],
                'alamat' => $old['alamat'],
                'otp' => (string) random_int(1000, 9999)
            ]);

            redirect('daftar.php?step=3');
        }
    }

    // LANGKAH 3: VERIFIKASI OTP DAN SIMPAN KE MYSQL
    if ($step === 3) {
        $kode = implode('', array_map(
            'strval',
            (array) ($_POST['otp'] ?? [])
        ));

        if (!hash_equals((string) ($reg['otp'] ?? ''), $kode)) {
            $err['otp'] = 'Kode OTP salah. Silakan coba lagi.';
        } else {
            $nama = $reg['nama'];
            $email = $reg['email'];
            $nik = $reg['nik'];
            $passwordHash = $reg['sandi'];
            $role = 'warga';
            $alamat = $reg['alamat'];
            $rt = $reg['rt'];
            $rw = $reg['rw'];
            $verified = 1;

            // Cegah email atau NIK ganda jika sudah terdaftar
            // selama pengguna berada di langkah verifikasi.
            $stmt = $conn->prepare(
                'SELECT id FROM users
                 WHERE email = ? OR nik = ? LIMIT 1'
            );
            $stmt->bind_param('ss', $email, $nik);
            $stmt->execute();
            $duplikat = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($duplikat) {
                $err['otp'] = 'Email atau NIK sudah terdaftar. Silakan daftar ulang.';
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO users
                    (nama, email, nik, password, role, alamat, rt, rw, verified)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                $stmt->bind_param(
                    'ssssssssi',
                    $nama,
                    $email,
                    $nik,
                    $passwordHash,
                    $role,
                    $alamat,
                    $rt,
                    $rw,
                    $verified
                );

                if ($stmt->execute()) {
                    $id = (int) $conn->insert_id;
                    $stmt->close();

                    unset($_SESSION['reg']);

                    session_regenerate_id(true);
                    $_SESSION['uid'] = $id;

                    flash('Akun berhasil dibuat. Selamat datang di CivicHub!');
                    redirect('dashboard.php');
                }

                $stmt->close();
                $err['otp'] = 'Akun gagal disimpan. Silakan coba lagi.';
            }
        }
    }
}

$flash = flash();

$stepsDef = [
    1 => ['Informasi Akun', 'Mengisi informasi akun'],
    2 => ['Data Pribadi', 'Mengisi data pribadi'],
    3 => ['Verifikasi', 'Memasukkan kode OTP']
];

function f_err(array $err, string $k): string {
    return isset($err[$k])
        ? '<p class="err-msg" role="alert">' . e($err[$k]) . '</p>'
        : '';
}

auth_start('Daftar', 'plain');
?>

<h1><?= $step === 3 ? 'Verifikasi Kode OTP' : 'Daftar' ?></h1>

<div class="steps" aria-label="Langkah pendaftaran">
    <?php foreach ($stepsDef as $n => $item): ?>
        <div class="step <?= $n === $step ? 'on' : ($n < $step ? 'done' : '') ?>">
            <b><?= e($item[0]) ?></b><?= e($item[1]) ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($flash): ?>
    <div class="alert ok" style="margin-top:14px">
        <?= e($flash[0]) ?>
    </div>
<?php endif; ?>

<?php if ($step === 1): ?>

<form class="form" method="post" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="nama">Nama Lengkap</label>
        <div class="input <?= isset($err['nama']) ? 'has-err' : '' ?>">
            <?= icon('user') ?>
            <input id="nama" name="nama"
                value="<?= e($old['nama'] ?? '') ?>"
                placeholder="Masukkan nama lengkap Anda"
                autocomplete="name" required>
        </div>
        <?= f_err($err, 'nama') ?>
    </div>

    <div class="field">
        <label for="email">Email</label>
        <div class="input <?= isset($err['email']) ? 'has-err' : '' ?>">
            <?= icon('mail') ?>
            <input id="email" type="email" name="email"
                value="<?= e($old['email'] ?? '') ?>"
                placeholder="Masukkan email Anda"
                autocomplete="email" required>
        </div>
        <?= f_err($err, 'email') ?>
    </div>

    <div class="field">
        <label for="nik">NIK</label>
        <div class="input <?= isset($err['nik']) ? 'has-err' : '' ?>">
            <?= icon('id') ?>
            <input id="nik" name="nik" inputmode="numeric"
                maxlength="16" value="<?= e($old['nik'] ?? '') ?>"
                placeholder="Masukkan NIK 16 digit" required>
        </div>
        <?= f_err($err, 'nik') ?>
    </div>

    <div class="row2">
        <div class="field">
            <label for="sandi">Sandi</label>
            <div class="input <?= isset($err['sandi']) ? 'has-err' : '' ?>">
                <?= icon('lock') ?>
                <input id="sandi" type="password" name="sandi"
                    placeholder="Minimal 8 karakter"
                    autocomplete="new-password" required>
                <button type="button" class="eye" data-eye
                    aria-label="Tampilkan sandi"><?= icon('eye') ?></button>
            </div>
            <?= f_err($err, 'sandi') ?>
        </div>

        <div class="field">
            <label for="konfirmasi">Konfirmasi Sandi</label>
            <div class="input <?= isset($err['konfirmasi']) ? 'has-err' : '' ?>">
                <?= icon('lock') ?>
                <input id="konfirmasi" type="password" name="konfirmasi"
                    placeholder="Ulangi sandi"
                    autocomplete="new-password" required>
                <button type="button" class="eye" data-eye
                    aria-label="Tampilkan sandi"><?= icon('eye') ?></button>
            </div>
            <?= f_err($err, 'konfirmasi') ?>
        </div>
    </div>

    <button class="btn btn-dark btn-block" type="submit" style="margin-top:8px">
        Selanjutnya
    </button>
</form>

<?php elseif ($step === 2): ?>

<form class="form" method="post" novalidate>
    <?= csrf_field() ?>

    <div class="row2">
        <div class="field">
            <label for="rt">RT</label>
            <div class="input <?= isset($err['rt']) ? 'has-err' : '' ?>">
                <input id="rt" name="rt" inputmode="numeric"
                    maxlength="3" value="<?= e($old['rt'] ?? '') ?>"
                    placeholder="03" required>
            </div>
            <?= f_err($err, 'rt') ?>
        </div>

        <div class="field">
            <label for="rw">RW</label>
            <div class="input <?= isset($err['rw']) ? 'has-err' : '' ?>">
                <input id="rw" name="rw" inputmode="numeric"
                    maxlength="3" value="<?= e($old['rw'] ?? '') ?>"
                    placeholder="05" required>
            </div>
            <?= f_err($err, 'rw') ?>
        </div>
    </div>

    <div class="field">
        <label for="alamat">Alamat</label>
        <div class="input <?= isset($err['alamat']) ? 'has-err' : '' ?>">
            <?= icon('pin') ?>
            <input id="alamat" name="alamat"
                value="<?= e($old['alamat'] ?? '') ?>"
                placeholder="Blok, nomor rumah, kelurahan"
                autocomplete="street-address" required>
        </div>
        <?= f_err($err, 'alamat') ?>
    </div>

    <div class="form-actions">
        <a class="btn btn-out" href="daftar.php?step=1">Kembali</a>
        <button class="btn btn-dark" type="submit" style="flex:1">
            Selanjutnya
        </button>
    </div>
</form>

<?php else: ?>

<p class="sub" style="margin-top:14px">
    Masukkan kode OTP untuk menyelesaikan pendaftaran.
    Mode demo: kode ditampilkan di bawah dan tidak dikirim melalui email.
</p>

<form method="post" novalidate>
    <?= csrf_field() ?>

    <div class="otp" role="group" aria-label="Kode OTP 4 digit">
        <?php for ($i = 0; $i < 4; $i++): ?>
            <input type="text" name="otp[]" inputmode="numeric"
                maxlength="1" aria-label="Digit <?= $i + 1 ?>"
                <?= $i === 0 ? 'autofocus' : '' ?>>
        <?php endfor; ?>
    </div>

    <?php if (isset($err['otp'])): ?>
        <p class="err-msg center" role="alert"><?= e($err['otp']) ?></p>
    <?php endif; ?>

    <div class="otp-meta">
        <strong style="color:var(--ink)">
            (<?= e(mask_email($reg['email'])) ?>)
        </strong>
        <span>Tidak menerima OTP?
            <a href="daftar.php?step=3&kirim_ulang=1">Kirim Ulang OTP</a>
        </span>
        <span>Salah memasukkan email/NIK?
            <a href="daftar.php?ubah=1">Ubah</a>
        </span>
    </div>

    <button class="btn btn-dark btn-block" type="submit">Verifikasi</button>
</form>

<p class="demo" style="background:var(--yl);color:var(--ink)">
    Mode demo: kode OTP Anda
    <code><?= e($reg['otp'] ?? '') ?></code>
</p>

<?php endif; ?>

<p class="alt" style="margin-top:22px;color:var(--muted)">
    Sudah punya akun? <a href="login.php">Masuk Akun</a>
</p>

<?php auth_end(); ?>
