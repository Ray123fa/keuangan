# 💰 Chatbot Keuangan WhatsApp

Bot WhatsApp untuk tracking pengeluaran harian dengan fitur laporan Excel, ringkasan per kategori, dan perbandingan periode.

Teknologi: **PHP 8.1+ + MySQL + Fonnte API + PhpSpreadsheet + Google OAuth + TailwindCSS + Alpine.js**

## ✨ Fitur Utama

- ✅ Catat pengeluaran dengan format simpel: `kategori nominal [keterangan]`
- ✅ Support berbagai format nominal: `50000`, `50rb`, `1.5jt`, `2,5rb`
- ✅ Multi transaksi sekaligus: `makan 50rb + transport 25rb gojek`
- ✅ Multi-date input: catat pengeluaran untuk tanggal lampau (format ddmmyy)
- ✅ Total & ringkasan: hari/minggu/bulan/tahun ini
- ✅ **Custom period**: tahun spesifik, bulan spesifik, range tahun, range bulan/tanggal
- ✅ Perbandingan dengan periode sebelumnya (% perubahan)
- ✅ Laporan Excel 2 sheet (detail transaksi + ringkasan per kategori)
- ✅ Auto upload file ke cloud (tmpfiles.org + fallback)
- ✅ Kategori default + custom
- ✅ Hapus transaksi terakhir
- ✅ Riwayat 5 transaksi terakhir

## 📁 Struktur Folder

```
keuangan/
├── public/                 # Front controller web (MVC)
│   ├── index.php
│   └── .htaccess
├── src/                    # App source code (MVC + PSR-4)
│   ├── Bootstrap/
│   ├── Core/
│   ├── Controllers/
│   ├── Application/
│   ├── Infrastructure/
│   ├── Repositories/
│   └── Services/
├── views/                  # Template server-rendered admin
├── storage/                # Log aplikasi & rate limits
│   ├── logs/
│   └── rate_limits/
├── config.php              # Konfigurasi global (DB, env, logging)
├── database.php            # PDO connection singleton
├── schema.sql              # Database schema
├── composer.json           # Dependencies
├── README.md               # Dokumentasi ini
├── .env                    # Environment lokal (git ignored ⚠️)
├── .env.example            # Template environment
├── .gitignore              # Git ignore rules
└── vendor/                 # Composer dependencies (git ignored)
```

### Status Migrasi MVC (2 Fase)

- **Fase 1 (selesai):** Front controller + routing + controller admin/webhook sudah PSR-4 di `src/`.
- **Fase 2 (selesai):** Class legacy bot sudah dipindahkan penuh ke namespace `src/Application`, `src/Domain`, `src/Infrastructure`.

## 🚀 Setup Lokal (Development)

### Requirements

- PHP 8.1+
- MySQL/MariaDB 5.7+
- Composer
- ngrok (untuk expose lokal ke public)
- Fonnte API account

### Installation

```bash
# 1. Clone/setup folder
cd keuangan

# 2. Install dependencies
composer install

# 3. Setup database
mysql -u root -p keuangan_db < schema.sql

# Optional: generate migration file baru (ala Laravel)
php artisan make:migration create_sample_table

# Jalankan migration pending
php artisan migrate

# 4. Setup environment
cp .env.example .env
# Edit .env dengan kredensial lokal kamu
nano .env

# 5. Start local server
php -S localhost:8000 -t public

# 6. Di terminal lain, expose ke public
ngrok http 8000
# Copy ngrok URL: https://xxx.ngrok.io
```

### Database Migration Command (Custom Artisan)

Project ini menyediakan command migration mirip Laravel lewat file `artisan` custom.

```bash
php artisan make:migration create_expenses_archive_table
php artisan make:seeder CategorySeeder
php artisan migrate
php artisan migrate --seed
php artisan migrate:fresh
php artisan migrate:fresh --seed --force
php artisan migrate:status
php artisan migrate:rollback
php artisan db:seed
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=CategoryFromBackupSeeder
php artisan db:seed --class=ExpenseFromBackupSeeder
```

Jika `APP_ENV=production`, command `migrate:fresh` akan meminta konfirmasi interaktif sebelum mengeksekusi drop semua tabel. Untuk mode non-interactive (misalnya CI), gunakan `--force`.

```bash
php artisan migrate:fresh --force
```

Setiap migration file akan dibuat di `database/migrations` dengan format timestamp, dan status eksekusi disimpan di tabel `migrations`.

Khusus command database (`migrate`, `migrate:fresh`, `migrate:rollback`, `migrate:status`, `db:seed`), jika database dari `.env` belum ada maka `artisan` akan otomatis membuat database tersebut terlebih dulu.

Seeder file dibuat di `database/seeders`. Default `php artisan db:seed` akan menjalankan `DatabaseSeeder.php`, dan kamu bisa jalankan seeder spesifik dengan `--class=NamaSeeder`.

Seeder default saat ini memuat data dari backup untuk tabel `categories` dan `expenses` (tanpa data admin), dengan pola idempotent `ON DUPLICATE KEY UPDATE`.

### Konfigurasi .env

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=keuangan_db
DB_USER=root
DB_PASS=root

# Fonnte (dapatkan dari dashboard Fonnte)
FONNTE_TOKEN=your_token_here

# Webhook Secret (generate random string minimal 32 karakter)
WEBHOOK_SECRET=your_random_secret_token_here

# Whitelist (pisahkan dengan koma)
WHITELIST_NUMBERS=6282255623881

# Timezone
TIMEZONE=Asia/Jakarta

# Application
APP_ENV=development
APP_DEBUG=true
```

### Setup Webhook

Di dashboard Fonnte:
1. Pilih device → Edit
2. Webhook URL: `https://ngrok-url/webhook?secret=YOUR_WEBHOOK_SECRET`
   (Gunakan secret token yang sama dengan `WEBHOOK_SECRET` di `.env`)
3. Aktifkan "Auto Read" = ON
4. Save

**Catatan Keamanan**: Webhook secret token wajib diset untuk mencegah unauthorized webhook requests.

## 🖥️ Admin Interface (MVC)

Interface admin tersedia di `https://keuangan.rayfa.my.id/admin`.

### Fitur Admin Dashboard

- 📊 Dashboard overview dengan statistik pengeluaran
- 💰 Manajemen expense (CRUD) dengan filter & pagination
- 🏷️ Manajemen kategori (CRUD)
- 📥 Export expense ke Excel dengan filter fleksibel (tanggal, kategori, custom period)
- 🔐 Google OAuth authentication
- 🛡️ CSRF protection & security headers
- ⏱️ Session timeout management

### Konfigurasi Google OAuth

Tambahkan di `.env`:

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://keuangan.rayfa.my.id/admin/auth/google/callback
ADMIN_ALLOWED_EMAILS=admin@email.com

# Session admin (1440 menit = 24 jam)
SESSION_IDLE_TIMEOUT_MINUTES=1440
SESSION_ABSOLUTE_TIMEOUT_MINUTES=1440
```

Di Google Cloud Console (OAuth Web App), daftarkan redirect URI:

```text
https://keuangan.rayfa.my.id/admin/auth/google/callback
http://localhost:8000/admin/auth/google/callback
```

### Akses Login

- Buka `/admin/login`
- Klik `Masuk dengan Google`
- Hanya email yang ada di `ADMIN_ALLOWED_EMAILS` yang diizinkan masuk
- Session admin akan expired jika idle selama `SESSION_IDLE_TIMEOUT_MINUTES`
- Session admin juga punya batas maksimum `SESSION_ABSOLUTE_TIMEOUT_MINUTES` sejak login

### Catatan FlyEnv + Apache (Windows)

- Disarankan arahkan `DocumentRoot` ke folder `public`.
- Pastikan `mod_rewrite` aktif dan `AllowOverride All` untuk project.
- Jika `DocumentRoot` masih di root project, `.htaccess` root tetap me-rewrite request ke `public/index.php`.

### Test

Kirim ke nomor WhatsApp yang terhubung:
```
bantuan              # Tampilkan help
makan 50000 warteg   # Catat expense
total bulan ini      # Lihat total
```

## 📖 Panduan Penggunaan

### 💰 Catat Pengeluaran

```
Format: <kategori> <nominal> [keterangan]

Contoh:
  makan 50000 warteg
  transport 25rb gojek
  belanja 1.5jt indomaret
  makan 50rb + transport 25rb gojek    (multi transaksi)
```

Format nominal support:
- `50000` - nominal biasa
- `50rb` - ribuan  
- `50k` - ribuan (alternative)
- `1.5jt` - juta dengan desimal
- `2,5rb` - ribuan dengan koma desimal

Bot akan langsung menyimpan dan menampilkan:
- Konfirmasi transaksi yang tersimpan
- Total hari ini (jika tanpa tanggal) atau Total (jika dengan tanggal spesifik)

### 📅 Catat Pengeluaran Tanggal Lampau

```
Format: ddmmyy (tanggal, bulan, tahun 2 digit)

Contoh (multi-date):
250226
makan 7k nasduk
belanja 100k alfa

260226
makan 5k nasi
```

Bot akan menyimpan transaksi ke tanggal yang sesuai (25 Feb 2026 dan 26 Feb 2026).

### 📊 Total & Ringkasan

```
TOTAL (breakdown per kategori):
  total hari ini
  total minggu ini
  total bulan ini
  total tahun ini
  total 2025
  total januari 2025
  total 2024-2025
  total jan 2024 hingga jun 2025

RINGKASAN (dengan persentase):
  ringkasan hari ini
  ringkasan minggu ini
  ringkasan bulan ini
  ringkasan 2025
  ringkasan januari 2025
```

### 📈 Perbandingan vs Periode Sebelumnya

```
  perbandingan minggu           (minggu ini vs minggu lalu)
  perbandingan bulan            (bulan ini vs bulan lalu)
  perbandingan januari 2025     (Jan 2025 vs Des 2024)
  perbandingan 2025             (Tahun 2025 vs Tahun 2024)
```

Output:
```
Total pengeluaran Januari 2025: Rp2.500.000
vs Desember 2024: Rp2.200.000 (+13.6%)

Per kategori:
- Makan: Rp1.500.000
- Transport: Rp750.000
- Belanja: Rp250.000
```

### 📋 Laporan Excel

Generate file Excel dengan 2 sheet (detail + ringkasan).

```
PREDEFINED REPORTS:
  report mingguan               (laporan minggu ini)
  report bulanan                (laporan bulan ini)
  report tahunan                (laporan tahun ini)

CUSTOM PERIOD:
  report 2025                   (tahun 2025)
  report januari 2025           (Januari 2025)
  report 2024-2025              (tahun 2024-2025)
  report jan 2024 hingga jun 2025    (periode bulan)
  report 01/01/2024 hingga 31/12/2025  (periode tanggal DD/MM/YYYY)
  report 2024-01-01 hingga 2025-12-31  (periode tanggal YYYY-MM-DD)
```

Bot akan:
- Generate Excel dengan detail transaksi + ringkasan
- Upload ke tmpfiles.org (or fallback 0x0.st)
- Kirim link download langsung ke chat

### 🏷️ Kategori

```
kategori                        (lihat daftar semua kategori)
tambah kategori kopi            (tambah kategori custom baru)
```

Default categories: makan, transport, belanja, hiburan, tagihan, lainnya

### 🗑️ Lainnya

```
hapus terakhir                  (hapus transaksi terakhir)
riwayat                         (lihat 5 transaksi terakhir)
bantuan                         (tampilkan panduan ini)
```

## Troubleshooting

### Webhook tidak jalan
- Pastikan "Auto Read" sudah ON di Fonnte
- Pastikan webhook URL menggunakan secret token yang benar: `/webhook?secret=YOUR_SECRET`
- Cek apakah URL webhook bisa diakses (tidak error 404/500)
- Cek file `storage/logs/error.log` untuk melihat error

### Database error
- Pastikan kredensial database di `.env` sudah benar
- Pastikan database dan tabel sudah dibuat (jalankan `php artisan migrate`)

### Excel tidak terkirim
- Pastikan paket Fonnte kamu mendukung attachment (Super/Advanced/Ultra)
- Cek apakah PhpSpreadsheet terinstall dengan benar

### Session admin expired terus
- Cek nilai `SESSION_IDLE_TIMEOUT_MINUTES` dan `SESSION_ABSOLUTE_TIMEOUT_MINUTES` di `.env`
- Pastikan server time sesuai dengan timezone yang diset

## Requirements

- PHP 8.1+
- MySQL 5.7+ atau MariaDB 10+
- Extensions: PDO, cURL, zip
- Composer

## 🔒 Keamanan

Bot dilindungi dengan whitelist nomor WhatsApp. Hanya nomor yang terdaftar di variabel `.env` `WHITELIST_NUMBERS` yang dapat mengakses bot. Percakapan dari nomor lain akan diabaikan tanpa respon.

### Fitur Keamanan

- ✅ **Webhook Secret Verification**: Webhook hanya menerima request dengan secret token yang valid
- ✅ **SSL Verification**: Semua API calls ke Fonnte menggunakan SSL verification
- ✅ **CSRF Protection**: Token CSRF dengan auto-rotation setelah validasi
- ✅ **Security Headers**: X-Frame-Options, CSP, X-Content-Type-Options, dll
- ✅ **Rate Limiting**: Proteksi terhadap spam dengan file-based rate limiter
- ✅ **Session Security**: Custom session name, idle timeout, absolute timeout
- ✅ **Input Sanitization**: Sanitasi input dan output untuk mencegah XSS
- ✅ **Environment Protection**: `.env` file protection via `.htaccess`

### Mengubah Nomor Whitelist

Edit file `.env`:
```env
WHITELIST_NUMBERS=6282255623881
```

Untuk multiple nomor:
```env
WHITELIST_NUMBERS=6282255623881,62812345678,62898765432
```

## Changelog

### v2.3 (Current - Feb 2026)
- 🛡️ **Security Hardening**: OWASP vulnerability fixes
  - Webhook secret token verification
  - SSL verification enforcement on API calls
  - CSRF token rotation
  - Security response headers (X-Frame-Options, CSP, etc.)
  - Rate limiter race condition fix with flock()
  - Content-Disposition header sanitization
  - Custom session name for reduced fingerprinting
- ✨ **Admin Features**: Flexible expense export with custom filters
- 🐛 **UI Fixes**: Centered modals & Choices.js dropdown clipping fix
- 🔒 **Session Management**: Explicit idle & absolute timeout enforcement

### v2.2 (Feb 2026)
- 🔒 Whitelist nomor: hanya nomor terdaftar yang bisa mengakses bot
- 🤐 Silent fail: pesan tidak valid tidak mendapat respon

### v2.1 (Feb 2026)
- ✨ Multi-date expense input: catat pengeluaran untuk tanggal lampau (format ddmmyy)
- ✨ Direct save: pengeluaran langsung tersimpan tanpa konfirmasi y/n
- 🔧 Fix: respons "Total hari ini" → "Total" ketika tanggal spesifik digunakan

### v2.0 (Feb 2026)
- ✨ Custom period support (tahun, bulan, range dengan berbagai format)
- ✨ Perbandingan pengeluaran vs periode sebelumnya
- ✨ Improved help message dengan emoji
- 🔧 Type-safe SQL queries (explicit int casting)
- 📝 Enhanced documentation
- 📦 Added .env/.gitignore template

### v1.0 (Initial)
- ✨ Basic expense tracking
- ✨ Total/summary per period
- ✨ Excel report generation
- ✨ WhatsApp via Fonnte

## License

Private Project

---

**Created**: Feb 2026  
**Status**: Active Development  
**Last Updated**: 14 Feb 2026
