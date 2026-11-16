# Website Pengelolaan Perencanaan Desa (PHP + MySQL)

Aplikasi web sederhana (PHP native + MySQL/PDO) untuk mengelola data Master Plan/Perencanaan Desa. Bukan landing page — setelah login langsung masuk ke dashboard ringkasan, dan pengelolaan dibagi per halaman.

## Struktur
- `config.php` — konfigurasi DB, session, helper, inisialisasi tabel (auto-create DB & schema saat pertama dijalankan).
- `includes/layout.php` — layout/sidebar & utilitas tampilan.
- `login.php` / `logout.php` — autentikasi admin.
- `index.php` — dashboard ringkasan (bukan landing page konten).
- Halaman pengelolaan (CRUD/input): `profil.php`, `baseline.php`, `pilar.php`, `roadmap.php`, `tahun1.php`, `monitoring.php`, `anggaran.php`, `checklist.php`, `catatan.php`.

## Kebutuhan
- PHP 8+ dengan ekstensi `pdo_mysql`
- MySQL/MariaDB berjalan di local

## Konfigurasi Database (.env)
Konfigurasi database dibaca dari file `perencanaan/.env` (tidak di-commit ke Git). Salin contoh konfigurasi lalu sesuaikan:

```bash
cp .env.example .env
```

Isi default `.env.example`:
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_USER=root`
- `DB_PASS=` *(kosong/tanpa password)*
- `DB_NAME=perencanaan_desa` (dibuat otomatis jika belum ada)

Pastikan service MySQL aktif di port 3306. Jika kredensial MySQL lokal Anda berbeda, ubah nilai pada `.env`. File `.env` sudah di-ignore oleh Git.

## Kredensial Login Admin
- Username: `admin`
- Password: `password`

(Didefinisikan di `config.php` sebagai `ADMIN_USER` dan `ADMIN_PASS`.)

## Cara Menjalankan
1. Letakkan folder `perencanaan` di document root web server (Apache/Nginx) atau jalankan PHP built-in server.
2. Jalankan MySQL lokal (port 3306, user root tanpa password).
3. Buka aplikasi di browser:
   - Built-in PHP (dari dalam folder):  
     `php -S localhost:8000` lalu buka `http://localhost:8000/login.php`
   - Atau via web server: `http://localhost/perencanaan/login.php`
4. Login dengan `admin` / `password`. Database & tabel akan otomatis dibuat dan baseline awal di-seed pada akses pertama.

## Fitur Input per Halaman
- **Profil & Visi**: edit judul, lokasi, periode, versi, slogan, visi.
- **Baseline**: input/edit/hapus data baseline dan daftar tugas verifikasi 90 hari.
- **Pilar & Indikator**: input/edit/hapus pilar, deskripsi, dan indikator.
- **Roadmap**: input/edit/hapus fase/periode dan poin-poin.
- **Tahun Pertama**: input/edit kalender 12 bulan, agenda rutin, target kuartal, evaluasi semester.
- **Monitoring**: input/edit/hapus register (kode, program, lokasi, PIC, target, anggaran, progres fisik, realisasi biaya, status, bukti, tindak lanjut).
- **Anggaran**: input/edit/hapus catatan & item strategi/sumber anggaran.
- **Checklist 90 Hari**: input/edit/hapus item, centang selesai, reset, ringkasan progres per tahap.
- **Catatan**: input/edit/hapus catatan kebijakan/dokumen.

## Catatan
- Ini alat pengelolaan internal berbasis data input; sesuaikan isi dengan dokumen resmi (RPJM/RKP/APBDes) dan data tervalidasi.
- Untuk produksi, ganti kredensial admin hardcoded dan password DB kosong dengan mekanisme yang lebih aman.