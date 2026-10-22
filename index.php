<?php
session_start();
$sudahMasuk = !empty($_SESSION['uid']);

// ====== Data ======
$nav = [
    'beranda' => 'Beranda',
    'layanan' => 'Layanan',
    'tentang' => 'Tentang',
];

$fitur = [
    ['icon' => 'doc',    'judul' => 'Mudah Diakses',   'teks' => 'Layanan dapat diakses kapan saja dan di mana saja.'],
    ['icon' => 'shield', 'judul' => 'Aman & Terpercaya', 'teks' => 'Data Anda terlindungi dengan sistem keamanan terbaik.'],
    ['icon' => 'clock',  'judul' => 'Proses Cepat',    'teks' => 'Pengurusan surat menjadi lebih efisien dan tidak berbelit.'],
];

$layanan = [
    [
        'nama'  => 'Pengajuan Surat',
        'desc'  => 'Ajukan surat secara online dan pantau statusnya kapan saja.',
        'peran' => [
            ['Warga', 'Tambah, lihat, ubah, dan hapus surat'],
            ['Administrator', 'Lihat surat yang masuk'],
        ],
    ],
    [
        'nama'  => 'Berita',
        'desc'  => 'Kabar dan pengumuman terbaru seputar pelayanan warga.',
        'peran' => [
            ['Administrator', 'Tambah berita'],
        ],
    ],
];

$surat = [
    ['nama' => 'Surat Keterangan Domisili', 'tgl' => '14 Okt 2026', 'status' => 'Diproses',     'kelas' => 'proses'],
    ['nama' => 'Surat Keterangan Usaha',    'tgl' => '12 Okt 2026', 'status' => 'Terverifikasi', 'kelas' => 'verif'],
    ['nama' => 'KTP',                       'tgl' => '10 Okt 2026', 'status' => 'Selesai',       'kelas' => 'selesai'],
];

// ====== Pencarian layanan (GET ?q=) ======
$q = trim($_GET['q'] ?? '');
$hasil = $layanan;
if ($q !== '') {
    $hasil = array_values(array_filter($layanan, function ($l) use ($q) {
        $teks = $l['nama'] . ' ' . $l['desc'] . ' ' . implode(' ', array_map(fn($p) => $p[0] . ' ' . $p[1], $l['peran']));
        return stripos($teks, $q) !== false;
    }));
}

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function icon($name) {
    $icons = [
        'doc'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5z"/><path d="m8.5 12 2.5 2.5 4.5-5" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'clock'  => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/><path d="M12 7v5l3.5 2" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'news'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h13v14H6a2 2 0 0 1-2-2z"/><path d="M17 9h3v8a2 2 0 0 1-2 2M8 9h5M8 13h5"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
        'arrow'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
        'star'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="m12 2 3 6.5 7 .8-5.2 4.8 1.5 7L12 17.6 5.7 21l1.5-7L2 9.3l7-.8z"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function leaf($class = '') {
    return '<img class="leaf ' . $class . '" src="assets/logo.png" alt="Logo CivicHub">';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CivicHub – Pelayanan Publik Lebih Mudah</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=2">
</head>
<body>

<!-- ===== Navbar ===== -->
<header class="navbar">
    <div class="container nav-inner">
        <a href="#beranda" class="logo"><?= leaf() ?><span>Civic<em>Hub</em></span></a>

        <nav class="nav-links" id="navLinks">
            <?php foreach ($nav as $id => $label): ?>
                <a href="#<?= e($id) ?>" class="<?= $id === 'beranda' ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="nav-actions">
            <a href="#layanan" class="icon-btn" aria-label="Cari"><?= icon('search') ?></a>
            <?php if ($sudahMasuk): ?>
                <a href="dashboard.php" class="btn btn-yellow">Dashboard</a>
            <?php else: ?>
                <a href="daftar.php" class="btn btn-ghost">Daftar</a>
                <a href="login.php" class="btn btn-yellow">Masuk</a>
            <?php endif; ?>
            <button class="burger" id="burger" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
        </div>
    </div>
</header>

<main>
<!-- ===== Hero ===== -->
<section id="beranda" class="hero">
    <div class="container hero-grid">
        <div class="hero-text">
            <span class="pill"><?= leaf('sm') ?>Pelayanan Publik Lebih Mudah</span>
            <h1>Selamat Datang di <span class="accent">CivicHub</span></h1>
            <p class="lead">Platform layanan administrasi kependudukan dan surat-menyurat secara online, cepat, aman, dan terpercaya.</p>

            <form class="search" action="index.php#layanan" method="get">
                <span class="search-icon"><?= icon('search') ?></span>
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari layanan atau masukkan kata kunci..." aria-label="Cari layanan">
                <button type="submit" class="go" aria-label="Cari"><?= icon('arrow') ?></button>
            </form>
        </div>

        <div class="hero-art">
            <div class="blob" aria-hidden="true"></div>
            <svg class="building" aria-hidden="true" viewBox="0 0 220 150">
                <rect x="105" y="0" width="3" height="26" fill="#2a6a45"/><path d="M108 2h20l-6 6 6 6h-20z" fill="#2f8f4e"/>
                <path d="M20 70 110 28l90 42z" fill="#2a6a45"/>
                <rect x="30" y="70" width="160" height="14" fill="#3d8a58"/>
                <g fill="#cfe9a8"><rect x="44" y="84" width="12" height="52"/><rect x="72" y="84" width="12" height="52"/><rect x="136" y="84" width="12" height="52"/><rect x="164" y="84" width="12" height="52"/></g>
                <rect x="20" y="136" width="180" height="14" fill="#2a6a45"/>
            </svg>
            <svg class="plant p1" aria-hidden="true" viewBox="0 0 120 140"><path d="M60 140C20 110 10 60 30 20c40 20 50 70 30 120z" fill="#2f8f4e"/><path d="M60 140C90 100 110 70 100 30 70 50 55 90 60 140z" fill="#4aa564"/></svg>
            <svg class="plant p2" aria-hidden="true" viewBox="0 0 120 160"><path d="M50 160C10 110 20 50 70 10c30 50 10 110-20 150z" fill="#2a6a45"/></svg>
            <img class="mascot" src="assets/maskot2.png" alt="Maskot lebah CivicHub">
            <div class="card doc-a" aria-hidden="true"><b>Surat Keterangan</b><i></i><i></i><i class="s"></i><span class="check"></span></div>
            <div class="card doc-b" aria-hidden="true">
                <div class="doc-head"><span class="avatar"></span><b>Pengajuan<br>Surat</b></div>
                <i></i><i></i><i class="s"></i><span class="check"></span>
            </div>
        </div>
    </div>
</section>

<!-- ===== Fitur ===== -->
<section class="features">
    <div class="container features-grid">
        <?php foreach ($fitur as $f): ?>
            <div class="feature">
                <div class="feature-icon"><?= icon($f['icon']) ?></div>
                <div>
                    <h3><?= e($f['judul']) ?></h3>
                    <p><?= e($f['teks']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ===== Layanan ===== -->

<section class="services-section" id="layanan">
    
    <div class="section-heading">
            <h2>Layanan Kami</h2>
            <p>Dua layanan utama CivicHub untuk warga dan administrator.</p>
    </div>

    <div class="services-grid">

        <article class="service-card service-card-yellow">
            <div class="service-icon">▤</div>

            <h3>Pengajuan Surat</h3>

            <p class="service-description">
                Ajukan surat dari rumah, lalu pantau setiap tahapnya
                sampai selesai.
            </p>

            <div class="service-features">
                <div class="service-feature">
                    <span class="service-check">✓</span>
                    <span>Ajukan kapan saja tanpa antre</span>
                </div>

                <div class="service-feature">
                    <span class="service-check">✓</span>
                    <span>Pantau status dari Diajukan sampai Selesai</span>
                </div>

                <div class="service-feature">
                    <span class="service-check">✓</span>
                    <span>Lampirkan foto sebagai bukti pendukung</span>
                </div>
            </div>

            <a class="service-button" href="surat.php">
                Ajukan Surat <span>→</span>
            </a>

            <div class="service-preview">
                <span class="service-preview-label">
                    Surat Keterangan Domisili
                </span>

                <p>
                    Permohonan surat domisili untuk keperluan bank
                </p>

                <div class="service-progress">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <div class="service-progress-labels">
                    <span>Diajukan</span>
                    <span>Verifikasi</span>
                    <span>Diproses</span>
                    <span>Selesai</span>
                </div>
            </div>
        </article>

        <!-- KARTU BERITA -->
        <article class="service-card service-card-green">
            <div class="service-icon">▤</div>

            <h3>Berita</h3>

            <p class="service-description">
                Kabar, pengumuman, dan agenda lingkungan dalam
                satu tempat.
            </p>

            <div class="service-features">
                <div class="service-feature">
                    <span class="service-check">✓</span>
                    <span>Pengumuman resmi dari pengurus RT</span>
                </div>

                <div class="service-feature">
                    <span class="service-check">✓</span>
                    <span>Saring berdasarkan acara, rapat, atau lingkungan</span>
                </div>

                <div class="service-feature">
                    <span class="service-check">✓</span>
                    <span>Selalu tahu kabar terbaru warga</span>
                </div>
            </div>

            <a class="service-button" href="berita.php">
                Baca Berita <span>→</span>
            </a>

            <div class="service-news-preview">
                <div class="service-news-item">
                    <span>Iuran RT Resmi Pakai QRIS</span>
                    <span class="service-news-tag">Pengumuman</span>
                </div>

                <div class="service-news-item">
                    <span>Kerja Bakti Bulan Agustus</span>
                    <span class="service-news-tag">Acara</span>
                </div>

                <div class="service-news-item">
                    <span>Rapat Warga Bahas Keamanan</span>
                    <span class="service-news-tag">Rapat</span>
                </div>
            </div>
        </article>

    </div>
</section>

<!-- ===== Tentang ===== -->
<section id="tentang" class="about">
    <div class="container about-grid">
        <div class="about-text">
            <span class="pill pill-yellow"><span class="star"><?= icon('star') ?></span>Tentang Kami</span>
            <h2>Solusi Digital untuk Pelayanan Publik</h2>
            <p>CivicHub hadir untuk mempermudah masyarakat dalam mengakses layanan administrasi secara online. Kami berkomitmen untuk memberikan pelayanan yang cepat, transparan, dan akuntabel.</p>
            <a href="<?= $sudahMasuk ? 'dashboard.php' : 'daftar.php' ?>" class="btn btn-yellow"><?= $sudahMasuk ? 'Buka Dashboard' : 'Mulai Sekarang' ?> <?= icon('arrow') ?></a>
        </div>

        <div class="about-art" aria-hidden="true">
            <svg class="plant p3" viewBox="0 0 120 160"><path d="M60 160C10 120 5 60 40 10c45 30 50 90 20 150z" fill="#bfd23a"/><path d="M60 160C40 110 45 60 40 10" stroke="#2f8f4e" stroke-width="3" fill="none"/></svg>
            <div class="dash">
                <div class="dots"><i></i><i></i><i></i></div>
                <div class="dash-body">
                    <aside>
                        <div class="dash-logo"><?= leaf('sm') ?>CivicHub</div>
                        <span class="on">Dashboard</span><span>Pengajuan Surat</span><span>Berita</span><span>Profil</span>
                    </aside>
                    <div class="dash-main">
                        <div class="dash-search"></div>
                        <div class="stats">
                            <div class="stat g"><small>Total Surat</small><b>12</b></div>
                            <div class="stat y"><small>Sedang Diproses</small><b>5</b></div>
                            <div class="stat g2"><small>Selesai</small><b>7</b></div>
                        </div>
                        <h4>Surat Terbaru</h4>
                        <table>
                            <thead><tr><th>Nama Surat</th><th>Tanggal Pengajuan</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($surat as $s): ?>
                                <tr><td><?= e($s['nama']) ?></td><td><?= e($s['tgl']) ?></td><td><span class="badge <?= e($s['kelas']) ?>"><?= e($s['status']) ?></span></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <svg class="plant p4" viewBox="0 0 120 160"><path d="M60 160C100 120 110 60 80 10c-45 30-50 90-20 150z" fill="#2f8f4e"/></svg>
        </div>
    </div>
</section>
</main>

<footer class="footer">
    <div class="container">© <?= date('Y') ?> CivicHub. Semua hak dilindungi.</div>
</footer>

<script>
    const burger = document.getElementById('burger');
    const links = document.getElementById('navLinks');
    burger.addEventListener('click', () => {
        const open = links.classList.toggle('open');
        burger.setAttribute('aria-expanded', open);
    });
    links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => links.classList.remove('open')));

    // Tandai menu aktif saat scroll
    const sections = ['beranda', 'layanan', 'tentang'].map(id => document.getElementById(id));
    const items = links.querySelectorAll('a');
    window.addEventListener('scroll', () => {
        let cur = 'beranda';
        sections.forEach(s => { if (s.getBoundingClientRect().top <= 120) cur = s.id; });
        items.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + cur));
    }, { passive: true });
</script>
</body>
</html>
