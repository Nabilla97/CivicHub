<?php
/**
 * CivicHub – bootstrap: session, helper, data demo.
 * Data disimpan di $_SESSION agar bisa langsung dijalankan tanpa database.
 * Untuk produksi, ganti fungsi di bagian "DATA" dengan query PDO/MySQL.
 */
session_start();
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirect(string $to) { header('Location: ' . $to); exit; }

function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}
function tgl(string $ymd): string {
    $b = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($ymd);
    return date('j', $t) . ' ' . $b[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}

function mask_email(string $email): string {
    $parts = explode('@', $email, 2);
    $u = $parts[0];
    $d = $parts[1] ?? '';
    if (strlen($u) < 3) return $email;
    return $u[0] . str_repeat('*', max(strlen($u) - 2, 3)) . substr($u, -1) . '@' . $d;
}

function icon(string $name): string {
    static $p = [
        'home'     => '<path d="M3 11 12 3l9 8"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-5h4v5"/>',
        'doc'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'news'     => '<path d="M4 5h13v14H6a2 2 0 0 1-2-2z"/><path d="M17 9h3v8a2 2 0 0 1-2 2M8 9h5M8 13h5"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'bell'     => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10 21a2 2 0 0 0 4 0"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'back'     => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'pin'      => '<path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'warn'     => '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17.5v.01"/>',
        'eye'      => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eyeoff'   => '<path d="M3 3l18 18"/><path d="M10.6 6.1A9.5 9.5 0 0 1 12 6c6 0 10 6 10 6a17 17 0 0 1-3.2 3.8M6.5 7.5C3.8 9.3 2 12 2 12s4 7 10 7c1.4 0 2.7-.3 3.9-.8"/>',
        'bookmark' => '<path d="M6 3h12v18l-6-4-6 4z"/>',
        'share'    => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.6 6.8-4M8.6 13.4l6.8 4"/>',
        'check'    => '<path d="m4 12 5 5L20 6"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'id'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.5-2 5.5-2 6 0M15 10h3M15 14h3"/>',
        'lock'     => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'edit'     => '<path d="M4 20h4L19 9l-4-4L4 16z"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'shield'   => '<path d="M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
        'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'wallet'   => '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18M16 15h2"/>',
        'image'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.5"/><path d="m21 16-5-5-9 9"/>',
    ];
    $inner = $p[$name] ?? '';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

const STATUS = ['Diajukan', 'Diverifikasi', 'Diproses', 'Selesai', 'Ditolak'];
const KATEGORI_BERITA = ['Pengumuman', 'Acara', 'Rapat', 'Lingkungan', 'Meninggal'];
const JENIS_SURAT = [
    'Surat Keterangan Domisili', 'Surat Keterangan Usaha', 'Surat Keterangan Tidak Mampu',
    'Surat Pengantar KTP', 'Surat Pengantar KK', 'Surat Keterangan Kelahiran', 'Lainnya',
];
// ====== Preferensi tampilan (tema & bahasa) ======
// Disimpan di cookie supaya tetap ada setelah keluar/masuk dan juga berlaku di halaman login.
function set_pref(string $key, string $value): void {
    $_SESSION[$key === 'lang' ? 'bahasa' : 'tema'] = $value;
    if (!headers_sent()) {
        setcookie('ch_' . $key, $value, [
            'expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax',
        ]);
    }
}
function load_prefs(): void {
    $c = $_COOKIE['ch_lang'] ?? null;
    if (in_array($c, ['id', 'en'], true)) $_SESSION['bahasa'] = $c;
    $c = $_COOKIE['ch_tema'] ?? null;
    if (in_array($c, ['light', 'dark'], true)) $_SESSION['tema'] = $c;
    $_SESSION['bahasa'] = $_SESSION['bahasa'] ?? 'id';
    $_SESSION['tema'] = $_SESSION['tema'] ?? 'light';
}
function lang(): string { return ($_SESSION['bahasa'] ?? 'id') === 'en' ? 'en' : 'id'; }

/** Terjemahan: teks Indonesia sebagai kunci. Dukung sprintf: t('Halo, %s.', $nama) */
function t(string $id, ...$args): string {
    static $en = null;
    if ($en === null) $en = require __DIR__ . '/lang_en.php';
    $s = (lang() === 'en') ? ($en[$id] ?? $id) : $id;
    return $args ? vsprintf($s, $args) : $s;
}
load_prefs();
function seed(): void {
    if (!empty($_SESSION['seeded'])) return;

    $_SESSION['users'] = [
        ['id' => 1, 'nama' => 'Ratu Aliyah', 'email' => 'ratualiyah@gmail.com', 'nik' => '3273011708910001',
         'sandi' => password_hash('12345678', PASSWORD_DEFAULT), 'role' => 'warga',
         'rt' => '03', 'rw' => '05', 'alamat' => 'Blok B2 No 14, Kel. Pepaya', 'verified' => true],
        ['id' => 2, 'nama' => 'Pengurus RT 04', 'email' => 'admin@civichub.id', 'nik' => '3273010101800002',
         'sandi' => password_hash('AdminMasuk1_', PASSWORD_DEFAULT), 'role' => 'admin',
         'rt' => '04', 'rw' => '05', 'alamat' => 'Sekretariat RT 04, Kel. Pepaya', 'verified' => true],
    ];
    $_SESSION['next_user'] = 3;

    $_SESSION['surat'] = [
        ['id' => 1, 'user_id' => 1, 'jenis' => 'Surat Keterangan Domisili',
         'judul' => 'Domisili untuk pendaftaran sekolah anak', 'deskripsi' => 'Surat domisili dibutuhkan untuk pendaftaran sekolah anak. Alamat sesuai KK, Blok B2 No 14.',
         'tanggal' => '2026-10-14', 'lokasi' => 'Blok B2 No 14, dekat mushola', 'urgensi' => 'Sedang', 'status' => 'Diproses', 'foto' => null,
         'timeline' => [['2026-10-14', 'Pengajuan diterima oleh pengurus RT.'], ['2026-10-15', 'Data dan KTP sudah diverifikasi.'], ['2026-10-16', 'Surat sedang diproses dan ditandatangani.']]],
        ['id' => 2, 'user_id' => 1, 'jenis' => 'Surat Keterangan Usaha',
         'judul' => 'Keterangan usaha warung kelontong', 'deskripsi' => 'Pengajuan surat usaha untuk melengkapi syarat pinjaman modal UMKM.',
         'tanggal' => '2026-10-12', 'lokasi' => 'Blok B2 No 14', 'urgensi' => 'Rendah', 'status' => 'Diverifikasi', 'foto' => null,
         'timeline' => [['2026-10-12', 'Pengajuan diterima oleh pengurus RT.'], ['2026-10-13', 'Data sedang diverifikasi.']]],
        ['id' => 3, 'user_id' => 1, 'jenis' => 'Surat Pengantar KTP',
         'judul' => 'Pengantar perpanjangan KTP', 'deskripsi' => 'KTP lama rusak, butuh surat pengantar untuk pembuatan ulang di kelurahan.',
         'tanggal' => '2026-10-10', 'lokasi' => 'Blok B2 No 14', 'urgensi' => 'Tinggi', 'status' => 'Selesai', 'foto' => null,
         'timeline' => [['2026-10-10', 'Pengajuan diterima oleh pengurus RT.'], ['2026-10-11', 'Data terverifikasi.'], ['2026-10-12', 'Surat sudah selesai, silakan ambil di sekretariat.']]],
    ];
    $_SESSION['next_surat'] = 4;

    $_SESSION['berita'] = [
        ['id' => 1, 'judul' => 'Iuran RT Resmi Pakai QRIS Mulai Bulan November', 'kategori' => 'Pengumuman', 'tanggal' => '2026-10-15',
         'ringkasan' => 'Mulai bulan ini, seluruh pembayaran iuran RT 04 resmi menggunakan QRIS.',
         'isi' => ["Mulai bulan ini, seluruh pembayaran iuran RT 04 resmi menggunakan QRIS. Langkah ini diambil untuk memberikan kemudahan, keamanan dan transparansi bagi seluruh warga.", "Warga tidak perlu lagi membayar secara tunai kepada bendahara. Cukup scan QRIS yang tersedia, kemudian lakukan pembayaran melalui aplikasi e-wallet atau mobile banking."],
         'langkah' => ['Buka aplikasi e-wallet atau mobile banking Anda.', 'Pilih menu Scan / Bayar dengan QRIS.', 'Scan kode QRIS iuran RT 04 dan masukkan nominal iuran.', 'Simpan bukti pembayaran dan tunjukkan bila diminta bendahara.'],
         'penulis' => 'Pengurus RT 04'],
        ['id' => 2, 'judul' => 'Perbaikan Selokan Blok C Dikerjakan Hari Minggu', 'kategori' => 'Lingkungan', 'tanggal' => '2026-10-13',
         'ringkasan' => 'Selokan di Blok C hampir meluap saat hujan. Perbaikan dilakukan bersama warga.',
         'isi' => ["Selokan di Blok C sudah beberapa kali hampir meluap saat hujan deras. Pengurus RT bersama warga akan memperbaiki saluran pada hari Minggu pagi.", "Warga Blok C diharapkan membawa sarung tangan dan alat kebersihan masing-masing."],
         'langkah' => [], 'penulis' => 'Pengurus RT 04'],
        ['id' => 3, 'judul' => 'Kerja Bakti Bulanan Sabtu Pagi', 'kategori' => 'Acara', 'tanggal' => '2026-10-11',
         'ringkasan' => 'Kerja bakti rutin dimulai pukul 07.00 di depan pos ronda.',
         'isi' => ["Kerja bakti bulanan dilaksanakan Sabtu pukul 07.00 WIB. Titik kumpul di depan pos ronda.", "Konsumsi disediakan oleh ibu-ibu PKK. Terima kasih atas partisipasi warga."],
         'langkah' => [], 'penulis' => 'Pengurus RT 04'],
        ['id' => 4, 'judul' => 'Rapat Warga Pembahasan Anggaran 2027', 'kategori' => 'Rapat', 'tanggal' => '2026-10-09',
         'ringkasan' => 'Rapat terbuka membahas rencana iuran dan kegiatan RT tahun depan.',
         'isi' => ["Pengurus RT mengundang seluruh warga untuk menghadiri rapat pembahasan rencana anggaran 2027 di balai warga.", "Usulan program dapat disampaikan langsung saat rapat."],
         'langkah' => [], 'penulis' => 'Pengurus RT 04'],
        ['id' => 5, 'judul' => 'Jadwal Pengambilan Surat di Sekretariat RW', 'kategori' => 'Pengumuman', 'tanggal' => '2026-10-07',
         'ringkasan' => 'Surat yang sudah selesai dapat diambil setiap Senin dan Kamis.',
         'isi' => ["Surat yang berstatus Selesai dapat diambil di sekretariat setiap Senin dan Kamis pukul 09.00-14.00 WIB.", "Bawa KTP asli saat pengambilan."],
         'langkah' => [], 'penulis' => 'Pengurus RT 04'],
        ['id' => 6, 'judul' => 'Turut Berduka Cita atas Wafatnya Bapak Hendra', 'kategori' => 'Meninggal', 'tanggal' => '2026-10-05',
         'ringkasan' => 'Telah meninggal dunia warga Blok A. Pemakaman dilakukan siang ini.',
         'isi' => ["Innalillahi wa inna ilaihi raji'un. Telah meninggal dunia Bapak Hendra, warga Blok A No 7.", "Pemakaman dilaksanakan hari ini setelah salat Zuhur. Mohon doa dan kehadiran warga."],
         'langkah' => [], 'penulis' => 'Pengurus RT 04'],
    ];
    $_SESSION['next_berita'] = 7;

    $_SESSION['notif'] = [
        ['id' => 1, 'judul' => 'Kerja Bakti', 'teks' => 'Saatnya kerja bakti bulan ini!', 'tipe' => 'Pengumuman', 'hari_ini' => true,  'dibaca' => false],
        ['id' => 2, 'judul' => 'Berita Hari Ini', 'teks' => 'Iuran RT resmi pakai QRIS mulai…', 'tipe' => 'Pengumuman', 'hari_ini' => true,  'dibaca' => false],
        ['id' => 3, 'judul' => 'Berita terkini', 'teks' => 'Lihatlah berita terkini', 'tipe' => 'Berita', 'hari_ini' => false, 'dibaca' => true],
        ['id' => 4, 'judul' => 'Surat Anda diproses', 'teks' => 'Surat Keterangan Domisili sedang diproses.', 'tipe' => 'Surat', 'hari_ini' => false, 'dibaca' => true],
    ];
    $_SESSION['tema'] = 'light';
    
    if (!isset($_SESSION['bahasa'])) {
    $_SESSION['bahasa'] = 'id';
}

    $_SESSION['seeded'] = true;
}

seed();
function find_user_by_email(string $email): ?array
{
    global $conn;

    if (!isset($conn) || !($conn instanceof mysqli)) {
        $conn = require $_SERVER['DOCUMENT_ROOT']
            . '/CivicHub-2/config/database.php';
    }

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1"
    );

    $stmt->bind_param('s', $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    return $user ?: null;
}
function find_user(int $id): ?array {
    global $conn;

    if (!isset($conn) || !($conn instanceof mysqli)) {
        $conn = require $_SERVER['DOCUMENT_ROOT']
            . '/CivicHub-2/config/database.php';
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    return $user ?: null;
}
function save_user(array $user): void {
    foreach ($_SESSION['users'] as $i => $u) if ($u['id'] === $user['id']) { $_SESSION['users'][$i] = $user; return; }
}
function current_user(): ?array { return isset($_SESSION['uid']) ? find_user((int)$_SESSION['uid']) : null; }
function require_login(): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') { flash('Halaman ini hanya untuk administrator.', 'err'); redirect('dashboard.php'); }
    return $u;
}
function guest_only(): void { if (current_user()) redirect('dashboard.php'); }
function surat_visible(): array {
    $u = require_login();
    $list = $u['role'] === 'admin'
        ? $_SESSION['surat']
        : array_filter($_SESSION['surat'], fn($s) => $s['user_id'] === $u['id']);
    usort($list, fn($a, $b) => $b['id'] <=> $a['id']);
    return array_values($list);
}
function surat_find(int $id): ?array {
    foreach (surat_visible() as $s) if ($s['id'] === $id) return $s;
    return null;
}
function surat_save(array $s): void {
    foreach ($_SESSION['surat'] as $i => $x) if ($x['id'] === $s['id']) { $_SESSION['surat'][$i] = $s; return; }
    $_SESSION['surat'][] = $s;
}
function surat_delete(int $id): void {
    $_SESSION['surat'] = array_values(array_filter($_SESSION['surat'], fn($s) => $s['id'] !== $id));
}
function status_index(string $st): int { $i = array_search($st, STATUS, true); return $i === false ? 0 : $i; }

function progress_html(string $status): string {
    $cur = status_index($status);
    $h = '<div class="progress" role="img" aria-label="Status: ' . e($status) . '">';
    foreach (STATUS as $i => $lbl) {
        $cls = $i < $cur ? 'done' : ($i === $cur ? 'now' : '');
        if ($lbl === 'Ditolak' && $i === $cur) {
            $cls = 'rejected';
        }
        $h .= '<div class="seg ' . $cls . '"><i></i><small>' . e($lbl) . '</small></div>';
    }
    return $h . '</div>';
}

function status_class(string $st): string {
    $m = ['Diajukan' => 'st-aju', 'Diverifikasi' => 'st-ver', 'Diproses' => 'st-pro', 'Selesai' => 'st-sel', 'Ditolak' => 'st-tol'];
    return $m[$st] ?? 'st-aju';
}
function berita_all(): array {
    $l = $_SESSION['berita'];
    usort($l, fn($a, $b) => [$b['tanggal'], $b['id']] <=> [$a['tanggal'], $a['id']]);
    return $l;
}
function berita_find(int $id): ?array {
    foreach ($_SESSION['berita'] as $b) if ($b['id'] === $id) return $b;
    return null;
}
function kategori_class(string $k): string { return 'k-' . strtolower($k); }
function kategori_icon(string $k): string {
    $m = ['Pengumuman' => 'bell', 'Acara' => 'calendar', 'Rapat' => 'user', 'Lingkungan' => 'pin', 'Meninggal' => 'clock'];
    return $m[$k] ?? 'news';
}
function berita_thumb(array $b, string $extra = ''): string {
    return '<div class="thumb ' . kategori_class($b['kategori']) . ' ' . $extra . '">' . icon(kategori_icon($b['kategori'])) . '</div>';
}
function simpan_foto(string $field): ?string {
    if (empty($_FILES[$field]['tmp_name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    if ($_FILES[$field]['size'] > 3 * 1024 * 1024) return null;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
    $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $ext = $map[$mime] ?? null;
    if (!$ext) return null;
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = 'surat_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    return move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $name) ? 'uploads/' . $name : null;
}
