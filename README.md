# CivicHub — Sistem Informasi Pelayanan Warga

CivicHub adalah aplikasi berbasis web yang membantu warga mengajukan surat secara online serta memudahkan admin mengelola pengajuan surat, berita, dan informasi pelayanan warga.

## Fitur Utama

### Warga
- Registrasi dan login akun.
- Melihat ringkasan pengajuan surat pada dashboard.
- Mengajukan dan melihat detail surat.
- Memantau status pengajuan surat.
- Membaca berita dan pengumuman terbaru.
- Mengelola profil dan pengaturan akun.
- Mengubah bahasa dan tema tampilan.

### Administrator
- Melihat ringkasan dan daftar surat masuk.
- Memantau status pengajuan surat warga.
- Mengelola berita dan pengumuman.
- Melihat notifikasi pengajuan surat baru.

## Teknologi yang Digunakan
- PHP
- MySQL
- HTML
- CSS
- JavaScript
- SweetAlert2
- Laragon sebagai lingkungan pengembangan lokal

## Persyaratan
- PHP 7.4 atau lebih baru.
- MySQL atau MariaDB.
- Laragon atau lingkungan server lokal sejenis.

## Cara Menjalankan
1. Clone repository CivicHub ke folder web server lokal.
2. Jalankan Apache dan MySQL melalui Laragon.
3. Buat database bernama `db_civichub`.
4. Impor struktur database melalui file SQL yang tersedia jika diperlukan.
5. Sesuaikan konfigurasi koneksi database pada `config/database.php`.
6. Buka aplikasi melalui browser menggunakan URL lokal Laragon, misalnya `http://localhost/CivicHub-2/`.

## Database
CivicHub menggunakan MySQL untuk menyimpan data pengguna, pengajuan surat, berita, dan data terkait lainnya.

**Catatan:** Sesuaikan struktur database dengan file SQL yang tersedia. Jangan mengunggah password asli, data pribadi warga, atau informasi sensitif ke repository publik.

## Repository
Source code CivicHub tersedia di GitHub pada repository ini.

## About CivicHub

CivicHub is a web-based citizen administration service
application developed using PHP and MySQL.git add .