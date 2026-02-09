# 💰 Chatbot Keuangan WhatsApp

Bot WhatsApp untuk tracking pengeluaran harian dengan fitur laporan Excel, ringkasan per kategori, dan perbandingan periode.

Teknologi: **PHP + MySQL + Fonnte API + PhpSpreadsheet**

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
├── config.php              # Konfigurasi global (DB, Fonnte, timezone)
├── database.php            # PDO connection singleton
├── webhook.php             # Entry point webhook Fonnte
├── schema.sql              # Database schema
├── composer.json           # Dependencies
├── README.md               # Dokumentasi ini
├── .env                    # Environment lokal (git ignored ⚠️)
├── .env.example            # Template environment
├── .gitignore              # Git ignore rules
│
├── handlers/
│   └── MessageHandler.php  # Route & handle incoming messages
├── services/
│   ├── FonnteService.php   # Send messages via Fonnte API
│   ├── ExpenseService.php  # CRUD expenses, stats, comparison
│   ├── ReportService.php   # Generate & upload Excel reports
│   └── SessionService.py   # Pending expenses + confirmation
├── utils/
│   ├── Parser.php          # Parse commands, expenses, custom periods
│   └── ExcelGenerator.php  # Generate Excel files (2 sheets)
└── vendor/                 # Composer dependencies (git ignored)

## 🚀 Setup Lokal (Development)

### Requirements

- PHP 7.4+
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

# 4. Setup environment
cp .env.example .env
# Edit .env dengan kredensial lokal kamu
nano .env

# 5. Start PHP server
php -S localhost:8000

# 6. Di terminal lain, expose ke public
ngrok http 8000
# Copy ngrok URL: https://xxx.ngrok.io
```

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

# Timezone
TIMEZONE=Asia/Jakarta
```

### Setup Webhook

Di dashboard Fonnte:
1. Pilih device → Edit
2. Webhook URL: `https://ngrok-url/webhook.php`
3. Aktifkan "Auto Read" = ON
4. Save

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
- Cek apakah URL webhook bisa diakses (tidak error 404/500)
- Cek file `error.log` untuk melihat error

### Database error
- Pastikan kredensial database di `config.php` sudah benar
- Pastikan database dan tabel sudah dibuat (import `schema.sql`)

### Excel tidak terkirim
- Pastikan paket Fonnte kamu mendukung attachment (Super/Advanced/Ultra)
- Cek apakah PhpSpreadsheet terinstall dengan benar

## Requirements

- PHP 7.4+
- MySQL 5.7+ atau MariaDB 10+
- Extensions: PDO, cURL, zip
- Composer

## Changelog

### v2.1 (Current - Feb 2026)
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
**Last Updated**: 09 Feb 2026
