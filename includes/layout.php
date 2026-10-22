<?php
require_once __DIR__ . '/bootstrap.php';

function layout_current_user(): array
{
    $u = require_login();

    if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
        $GLOBALS['conn'] = require $_SERVER['DOCUMENT_ROOT'] . '/CivicHub-2/config/database.php';
    }

    $conn = $GLOBALS['conn'];
    $id = (int)($_SESSION['uid'] ?? $u['id'] ?? 0);

    if ($id <= 0) {
        flash('Silakan login kembali.', 'err');
        redirect('login.php');
    }

    $stmt = $conn->prepare(
        "SELECT id, nama, email, role, alamat, rt, rw, nik, verified
         FROM users WHERE id = ? LIMIT 1"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    if (!$user) {
        flash('Akun tidak ditemukan. Silakan login kembali.', 'err');
        redirect('logout.php');
    }

    return $user;
}

/** Daun dekoratif untuk banner & halaman auth */
function leaves(string $cls = ''): string {
    return '<svg class="leaves ' . $cls . '" viewBox="0 0 900 220" preserveAspectRatio="none" aria-hidden="true">'
        . '<path d="M20 220C-10 150 10 70 70 20c50 50 40 150-50 200z" fill="#92c93f"/>'
        . '<path d="M120 220C90 170 110 110 160 80c30 40 20 100-40 140z" fill="#c4e07a" opacity=".8"/>'
        . '<path d="M880 0c40 60 20 140-50 200-50-60-30-150 50-200z" fill="#92c93f"/>'
        . '<path d="M780 40c30 30 30 90-10 130-40-40-40-100 10-130z" fill="#c4e07a" opacity=".8"/>'
        . '</svg>';
}

/**
 * Pembuka halaman aplikasi (sidebar + banner).
 * $judul/$sub tampil di kartu putih dengan lebah mengintip.
 */
function app_start(string $title, string $active, string $judul, string $sub): array {
    $u = layout_current_user();
    $tema = $_SESSION['tema'] ?? 'light';
    $conn = $GLOBALS['conn'] ?? null;
    $unread = 0;

    if ($conn instanceof mysqli) {
        $stmtNotif = $conn->prepare(
            "SELECT COUNT(*) AS total
            FROM notifikasi
            WHERE user_id = ? AND dibaca = 0"
        );

        $userId = (int) $u['id'];
        $stmtNotif->bind_param('i', $userId);
        $stmtNotif->execute();

        $hasilNotif = $stmtNotif->get_result()->fetch_assoc();
        $unread = (int) ($hasilNotif['total'] ?? 0);

        $stmtNotif->close();
    }
    $flash = flash();
    $nav = [
    'dashboard' => ['dashboard.php', 'home', 'Dashboard'],
    'surat'     => ['surat.php', 'doc', $u['role'] === 'admin' ? 'Surat Masuk' : 'Pengajuan Surat'],
    'berita'    => ['berita.php', 'news', 'Berita'],
    'profil'    => ['profil.php', 'user', 'Profil'],
    ];
    ?>
<!DOCTYPE html>
<html lang="<?= e(lang()) ?>" data-theme="<?= e($tema) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> – CivicHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/app.css?v=<?= filemtime(__DIR__ . '/../assets/app.css') ?>">
</head>
<body class="app">
<div class="shell">
    <aside class="side">
        <a href="dashboard.php" class="brand"><img src="assets/logo.png" alt=""><span>Civic<em>Hub</em></span></a>
        <nav class="side-nav" aria-label="Menu utama">
            <?php foreach ($nav as $key => [$href, $ic, $label]): ?>
                <a href="<?= $href ?>" class="<?= $key === $active ? 'on' : '' ?>" <?= $key === $active ? 'aria-current="page"' : '' ?>>
                    <?= icon($ic) ?><span><?= e(t($label)) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
            <a href="logout.php" class="side-out" id="btnLogout">
                <?= icon('logout') ?>
                <span><?= e(t('Keluar')) ?></span>
            </a>
    </aside>

    <div class="main">
        <section class="banner">
            <?= leaves() ?>
            <div class="banner-top">
                <form class="searchbar" action="cari.php" method="get" role="search">
                    <?= icon('search') ?>
                    <input
                    type="text"
                    name="q"
                    placeholder="<?= e(t('Masukkan kata kunci Anda')) ?>"
                    aria-label="<?= e(t('Cari surat atau berita')) ?>"
                >
                </form>
                <a class="round" href="profil.php?tab=notifikasi" aria-label="<?= e(t('Notifikasi')) ?> (<?= $unread ?> <?= e(t('belum dibaca')) ?>)">
                    <?= icon('bell') ?>
                    <?php if ($unread): ?><b class="dot"><?= $unread ?></b><?php endif; ?>
                </a>
                <a class="userchip" href="profil.php">
                    <span class="av"><?= e(mb_strtoupper(mb_substr($u['nama'], 0, 1))) ?></span>
                    <span><strong><?= e($u['nama']) ?></strong><small><?= e(t($u['role'] === 'admin' ? 'Administrator' : 'Warga')) ?></small></span>
                </a>
            </div>
            <div class="banner-card">
                <img class="peek" src="assets/logo.png" alt="">
                <h1><?= e($judul) ?></h1>
                <p><?= e($sub) ?></p>
            </div>
        </section>

        <main class="content">
            <?php if ($flash): ?>
                <div class="alert <?= $flash[1] === 'err' ? 'err' : 'ok' ?>" role="status"><?= e($flash[0]) ?></div>
            <?php endif; ?>
<?php
    return $u;
}

function app_end(): void { ?>
        </main>
    </div>
</div>
<script src="assets/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnLogout = document.getElementById('btnLogout');

    if (btnLogout) {
        btnLogout.addEventListener('click', function (e) {
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
                    window.location.href = btnLogout.href;
                }
            });
        });
    }
});
</script>
</body>
</html>
<?php }

/** Halaman auth (login / daftar): panel ilustrasi + kartu form */
function auth_start(string $title, string $variant = 'yellow'): void {
    $tema = $_SESSION['tema'] ?? 'light'; ?>
<!DOCTYPE html>
<html lang="<?= e(lang()) ?>" data-theme="<?= e($tema) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> – CivicHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/app.css?v=<?= filemtime(__DIR__ . '/../assets/app.css') ?>">
</head>
<body class="auth-page <?= $variant === 'plain' ? 'register-page' : '' ?>">
<div class="auth">
    <div class="auth-art">
        <a href="index.php" class="brand light"><img src="assets/logo.png" alt=""><span>Civic<em>Hub</em></span></a>
        <svg class="art-leaves" viewBox="0 0 600 700" aria-hidden="true">
            <g fill="#d6eac0"><path d="M0 120c60 0 110 40 130 100-70 20-130-20-130-100z"/><path d="M40 40c50 10 80 50 80 110-60-10-90-50-80-110z"/><path d="M520 90c-40 0-90 30-110 80 60 20 110-10 110-80z"/><path d="M600 200c-50-10-100 20-120 70 60 20 120-10 120-70z"/></g>
            <g fill="#92c93f"><path d="M30 700C0 600 20 500 90 430c50 80 30 200-60 270z"/><path d="M580 700c30-90 10-190-60-260-50 80-30 190 60 260z"/><path d="M480 700c0-70-30-130-90-170-30 70 0 150 90 170z"/></g>
        </svg>
        <img class="art-bee" src="assets/maskot2.png" alt="Maskot lebah CivicHub">
    </div>
    <div class="auth-side">
        <div class="auth-sheet <?= e($variant) ?>">
<?php }

function auth_end(): void { ?>
        </div>
    </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>
<?php }
