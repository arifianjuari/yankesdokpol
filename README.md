# RS Bhayangkara Batu - Sistem Pencatatan Peserta Kegiatan

YankesDokpol adalah aplikasi web untuk mengelola kegiatan bakti kesehatan di lingkungan Polri. Aplikasi ini memungkinkan pencatatan data peserta secara digital dengan fitur OCR untuk KTP, serta menyediakan dashboard untuk visualisasi data.

## Fitur Utama

- Upload foto KTP dengan OCR otomatis
- Input data peserta secara manual
- Validasi NIK unik untuk mencegah data ganda
- Upload dokumen tambahan (tanda keanggotaan, foto kegiatan)
- Pemilihan layanan kesehatan yang diikuti
- Dashboard statistik dan visualisasi data
- Export data ke Excel

## Struktur Proyek

```
yankesdokpol/
├── assets/
│   ├── css/          # File CSS
│   ├── js/           # File JavaScript
│   ├── img/          # Gambar statis
│   └── uploads/      # Upload file
│       ├── ktp/
│       ├── tanda_anggota/
│       └── dokumentasi/
├── config/           # Konfigurasi
│   ├── database.php  # Koneksi database
│   └── schema.sql    # Skema database
├── includes/         # Fungsi-fungsi PHP
│   ├── functions.php # Fungsi umum
│   ├── ocr.php       # Fungsi OCR
│   └── validation.php # Validasi data
├── vendor/           # Library (via Composer)
├── api/              # API endpoints
│   ├── peserta.php   # API peserta
│   ├── layanan.php   # API layanan
│   └── ocr.php       # API OCR
├── index.php         # Halaman utama (form)
├── dashboard.php     # Dashboard statistik
└── export.php        # Export data Excel
```

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Ekstensi PHP: GD, mysqli, json
- Tesseract OCR (untuk fitur OCR)
- Composer (untuk manajemen dependensi)

## Instalasi

### 1. Instalasi Dependensi

Aplikasi ini membutuhkan beberapa dependensi yang dikelola melalui Composer. Jalankan perintah berikut di direktori utama proyek:

```bash
composer install
```

Jika Composer belum terinstal, ikuti petunjuk instalasi di [getcomposer.org](https://getcomposer.org/download/).

### 2. Instalasi Tesseract OCR

Untuk menggunakan fitur OCR, Tesseract OCR perlu diinstal di sistem:

**Windows:**
- Download installer dari [UB-Mannheim Tesseract](https://github.com/UB-Mannheim/tesseract/wiki)
- Install dengan bahasa Indonesia dan English

**macOS:**
```bash
brew install tesseract
brew install tesseract-lang  # untuk bahasa tambahan
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install tesseract-ocr
sudo apt install tesseract-ocr-ind  # untuk bahasa Indonesia
```

### 3. Konfigurasi Database

1. Buat database baru di MySQL/MariaDB
2. Import skema database dari `config/schema.sql`:
   ```bash
   mysql -u username -p nama_database < config/schema.sql
   ```
3. Edit file `config/database.php` sesuai dengan kredensial database Anda

### 4. Direktori Upload

Pastikan direktori berikut memiliki izin tulis:
```bash
chmod -R 755 assets/uploads/
```

## Dependensi

- Bootstrap 5.3.0 (UI framework)
- Chart.js 4.0.0 (Visualisasi data)
- PhpSpreadsheet (Export Excel)
- Tesseract OCR (OCR untuk KTP)
- Intervention Image (Pemrosesan gambar)

## Mekanisme Fallback

Aplikasi ini dirancang dengan mekanisme fallback untuk tetap berfungsi meskipun beberapa dependensi tidak tersedia:

1. **PhpSpreadsheet**: Jika tidak tersedia, aplikasi akan menggunakan ekspor CSV sederhana
2. **Intervention Image**: Jika tidak tersedia, aplikasi akan menggunakan GD Library bawaan PHP
3. **Tesseract OCR**: Jika tidak tersedia, aplikasi akan menggunakan input manual

## Mengatasi Peringatan IDE

Jika Anda melihat peringatan IDE terkait dependensi yang tidak ditemukan (misalnya `Undefined type 'Intervention\Image\ImageManagerStatic'`), ini normal jika dependensi belum diinstal. Peringatan akan hilang setelah menjalankan `composer install` dengan sukses.

Beberapa peringatan umum dan solusinya:

1. **Undefined type 'PhpOffice\PhpSpreadsheet\Spreadsheet'**
   - Jalankan `composer install` untuk menginstal PhpSpreadsheet

2. **Undefined type 'Intervention\Image\ImageManagerStatic'**
   - Jalankan `composer install` untuk menginstal Intervention Image

3. **Class 'thiagoalessio\TesseractOCR\TesseractOCR' not found**
   - Jalankan `composer install` untuk menginstal PHP wrapper
   - Pastikan Tesseract OCR terinstal di sistem operasi Anda

## Instalasi

1. Clone repositori ini ke direktori web server Anda
2. Import skema database dari `config/schema.sql`
3. Sesuaikan konfigurasi database di `config/database.php`
4. Install dependensi dengan Composer:
   ```
   composer install
   ```
5. Pastikan direktori `assets/uploads` dan subdirektorinya memiliki izin tulis
6. Install Tesseract OCR pada server (lihat dokumentasi Tesseract)

## Penggunaan

1. Buka aplikasi di browser
2. Upload foto KTP atau isi form secara manual
3. Pilih layanan yang diikuti peserta
4. Simpan data
5. Lihat dashboard untuk statistik dan visualisasi
6. Export data ke Excel jika diperlukan

## Pengembangan Selanjutnya

- Sistem login dan manajemen pengguna
- Fitur pencarian dan filter data
- Integrasi dengan sistem lain
- Aplikasi mobile untuk pengumpulan data di lapangan
- Cetak kartu peserta dengan QR code

## Lisensi

Hak Cipta © 2025 RS Bhayangkara Batu - Sistem Pencatatan Peserta Kegiatan
