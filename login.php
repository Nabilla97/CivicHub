
<?php
require __DIR__ . '/includes/layout.php';
guest_only();

// Koneksi database MySQLi CivicHub.
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT'] . '/CivicHub-2/config/database.php';
}

$err = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $sandi = $_POST['sandi'] ?? '';
    $u = null;

    // Cari akun di database MySQL.
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare(
            'SELECT * FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $u = $result->fetch_assoc();

        $stmt->close();
    }

    // Fallback untuk akun admin yang disediakan bootstrap.php.
    if (!$u) {
        $admin = find_user_by_email($email);

        if ($admin && ($admin['role'] ?? '') === 'admin') {
            $u = $admin;
        }
    }

    // Kolom database bernama password.
    // Helper lama mungkin masih menggunakan sandi.
    $hash = $u['password'] ?? $u['sandi'] ?? '';
    $passwordBenar = false;

    if ($hash !== '') {
        // Akun dengan password hash.
        if (password_verify($sandi, $hash)) {
            $passwordBenar = true;
        }
        // Mendukung sementara admin plaintext dari bootstrap lama.
        // Sebaiknya admin juga memakai password_hash().
        elseif (
            ($u['role'] ?? '') === 'admin'
            && hash_equals((string) $hash, (string) $sandi)
        ) {
            $passwordBenar = true;
        }
    }

    if ($u && $passwordBenar) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $u['id'];

        redirect('dashboard.php');
    }

    $err = 'Email atau sandi salah. Periksa lagi lalu coba masuk.';
}

$flash = flash();

auth_start('Masuk', 'yellow');
?>

<h1>Masuk</h1>

<?php if ($flash): ?>
    <div class="alert ok" style="margin-top:14px">
        <?= e($flash[0]) ?>
    </div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert err" style="margin-top:14px" role="alert">
        <?= e($err) ?>
    </div>
<?php endif; ?>

<form class="form" method="post" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="email">Email Address</label>
        <div class="input">
            <input id="email" type="email" name="email"
                value="<?= e($email) ?>"
                placeholder="Enter Your Email"
                autocomplete="email" required>
        </div>
    </div>

    <div class="field">
        <label for="sandi">Password</label>
        <div class="input">
            <input id="sandi" type="password" name="sandi"
                placeholder="Enter Your Password"
                autocomplete="current-password" required>
            <button type="button" class="eye" data-eye
                aria-label="Tampilkan sandi"><?= icon('eye') ?></button>
        </div>
    </div>

    <button class="btn btn-dark btn-pill btn-block" type="submit"
        style="margin-top:10px;background:#0a3d22">
        Sign In
    </button>
</form>

<div class="divider">Belum punya akun?</div>

<p class="alt"><a href="daftar.php">Daftar Akun</a></p>

<div class="social" aria-label="Masuk dengan akun lain (segera hadir)">
    <button type="button" disabled title="Segera hadir" aria-label="Apple">
        <svg viewBox="0 0 24 24">
            <path fill="#000" d="M16.37 12.62c-.03-2.67 2.18-3.95 2.28-4.01-1.24-1.82-3.18-2.07-3.87-2.1-1.65-.17-3.22.97-4.06.97-.84 0-2.13-.95-3.5-.92-1.8.03-3.46 1.05-4.39 2.66-1.87 3.24-.48 8.04 1.34 10.67.89 1.29 1.95 2.73 3.34 2.68 1.34-.05 1.85-.87 3.47-.87 1.62 0 2.08.87 3.5.84 1.44-.03 2.36-1.31 3.24-2.6 1.02-1.49 1.44-2.93 1.47-3-.03-.01-2.81-1.08-2.84-4.28zM13.7 4.78c.74-.9 1.24-2.14 1.1-3.38-1.06.04-2.35.71-3.11 1.6-.68.79-1.28 2.05-1.12 3.26 1.18.09 2.39-.6 3.13-1.48z"/>
        </svg>
    </button>

    <button type="button" disabled title="Segera hadir" aria-label="Google">
        <svg viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22 12.2c0-.7-.1-1.400-.2-2H12v3.800h5.600a4.800 4.800 0 0 1-2.100 3.100v2.600h3.400c2-1.800 3.100-4.500 3.100-7.500z"/>
            <path fill="#34A853" d="M12 22.500c2.800 0 5.200-.9 6.900-2.500l-3.400-2.600c-.9.600-2.100 1-3.500 1-2.700 0-5-1.800-5.800-4.300H2.700v2.700A10.500 10.500 0 0 0 12 22.500z"/>
            <path fill="#FBBC05" d="M6.200 14.100a6.300 6.300 0 0 1 0-4.200V7.200H2.700a10.500 10.500 0 0 0 0 9.600z"/>
            <path fill="#EA4335" d="M12 5.600c1.500 0 2.900.5 4 1.600l3-3A10.500 10.500 0 0 0 2.700 7.200l3.500 2.700C7 7.400 9.300 5.600 12 5.600z"/>
        </svg>
    </button>

    <button type="button" disabled title="Segera hadir" aria-label="Facebook">
        <svg viewBox="0 0 24 24">
            <path fill="#1877F2" d="M14 8.500V6.800c0-.8.200-1.200 1.400-1.200H17V2.500h-2.500C11.500 2.500 10 4.300 10 6.700v1.800H7.500V12H10v9.500h4V12h2.700l.5-3.500z"/>
        </svg>
    </button>
</div>

<?php auth_end(); ?>
