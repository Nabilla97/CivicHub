# CivicHub – Versi Desktop (PHP)

Website desktop CivicHub yang mengikuti desain mobile. Tanpa database & tanpa composer:
data demo disimpan di `$_SESSION`.

## Menjalankan
Butuh PHP 7.4+ (disarankan 8.x).

    cd civichub
    php -S localhost:8000

Buka http://localhost:8000 (landing page).

## Akun demo
| Peran         | Email                 | Sandi      |
|---------------|-----------------------|------------|
| Warga         | ratualiyah@gmail.com  | 12345678   |
| Administrator | admin@civichub.id     | admin123   |

## Peta halaman (mobile -> desktop)
| Desain mobile                       | File                                   |
|-------------------------------------|----------------------------------------|
| Splash / Selamat Datang             | `index.php` (landing)                  |
| Log in                              | `login.php`                            |
| Sign up (2 langkah + OTP)           | `daftar.php`                           |
| Dashboard                           | `dashboard.php`, `cari.php`            |
| Letter: riwayat, detail pengajuan   | `surat.php`, `surat_detail.php`        |
| (Warga) tambah/ubah/hapus surat     | `surat_form.php`, `surat_hapus.php`    |
| News: daftar & detail               | `berita.php`, `berita_detail.php`      |
| (Admin) tambah berita               | `berita_form.php`                      |
| Profile: data diri, edit, verifikasi, notifikasi, bahasa, tema | `profil.php?tab=...` |

## Hak akses (sesuai tabel layanan di landing)
- Warga: tambah, lihat, ubah, hapus surat milik sendiri.
- Administrator: lihat semua surat masuk, tambah berita.

## Struktur
    includes/bootstrap.php   session, helper, ikon, data demo, fungsi data
    includes/layout.php      sidebar + banner (app_start/app_end), layout auth
    assets/style.css         CSS landing page
    assets/app.css           CSS aplikasi (termasuk tema gelap)
    assets/app.js            toggle sandi, OTP, bookmark, konfirmasi hapus

## Menuju produksi
- Ganti fungsi di bagian "DATA" pada `includes/bootstrap.php` dengan query PDO/MySQL
  (users, surat, surat_timeline, berita, notifikasi). Nama fungsinya sengaja dibuat
  terpusat: `surat_visible`, `surat_find`, `surat_save`, `surat_delete`, `berita_all`, `berita_find`, dll.
- Kirim OTP lewat email/SMS (sekarang kode ditampilkan di layar sebagai mode demo).
- Tombol login Apple/Google/Facebook masih nonaktif.
- Tambahkan fitur admin untuk mengubah status surat (Diajukan -> Diverifikasi -> Diproses -> Selesai).
