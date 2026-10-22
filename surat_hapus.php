
<?php
require __DIR__ . '/includes/layout.php';

// Pakai koneksi MySQLi CivicHub.
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = require $_SERVER['DOCUMENT_ROOT'] . '/CivicHub-2/config/database.php';
}

$u = require_login();

// Penghapusan wajib menggunakan POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('surat.php');
}

csrf_check();

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('Pengajuan tidak ditemukan.', 'err');
    redirect('surat.php');
}

// Ambil data surat langsung dari database.
$stmt = $conn->prepare(
    'SELECT id, user_id, status, foto FROM surat WHERE id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();
$s = $result->fetch_assoc();

$stmt->close();

// Validasi kepemilikan, role, dan status surat.
if (
    !$s ||
    $u['role'] === 'admin' ||
    (int) $s['user_id'] !== (int) $u['id'] ||
    $s['status'] === 'Selesai'
) {
    flash('Pengajuan ini tidak bisa dihapus.', 'err');
    redirect('surat.php');
}

// Hapus data langsung dari MySQL.
$userId = (int) $u['id'];

$stmt = $conn->prepare(
    "DELETE FROM surat
     WHERE id = ? AND user_id = ? AND status <> 'Selesai'"
);
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();

$berhasil = $stmt->affected_rows > 0;
$stmt->close();

if ($berhasil) {
    // Hapus file foto lampiran jika ada.
    if (!empty($s['foto'])) {
        $fotoPath = __DIR__ . '/' . ltrim($s['foto'], '/');

        if (is_file($fotoPath)) {
            unlink($fotoPath);
        }
    }

    flash('Pengajuan berhasil dihapus.');
} else {
    flash('Pengajuan gagal dihapus.', 'err');
}

redirect('surat.php');
