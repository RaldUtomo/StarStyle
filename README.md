# StarStyle

StarStyle adalah aplikasi web reservasi dan manajemen salon modern berbasis PHP, CSS, dan JavaScript tanpa framework berat. Proyek ini menampilkan portal publik pelanggan, dashboard internal admin/staff, POS, CRM, inventory, voucher, analytics, review, dan pengaturan akses staff.

## Stack

- PHP 8.2
- Bootstrap 5 + vanilla JavaScript
- Chart.js
- Flatpickr
- MySQL/MariaDB schema tersedia di `database/schema.sql`

## Fitur Utama

- Dashboard beranda dengan KPI, grafik penjualan, upcoming agenda, aktivitas booking, dan top performer
- Scheduling system dengan kalender per staff, anti double booking, blocked time, dan booking form internal
- Portal booking publik customer dengan slot availability
- POS ringan dengan multi-item cart, validasi voucher, dan checkout transaksi
- CRM pelanggan dengan loyalty, tags, histori kunjungan, dan segmentasi dasar
- Staff management dengan shift, attendance, komisi, dan permission matrix
- Layanan, paket layanan, inventory, voucher, analytics, review, dan activity logs
- Admin full access dan staff access yang bisa diatur oleh admin dari halaman Settings

## Menjalankan Aplikasi Lokal

1. Pastikan PHP 8.2+ tersedia.
2. Copy `.env.example` menjadi `.env`, lalu isi koneksi database lokal Anda.
3. Jalankan server lokal:

```bash
php -S localhost:8000 -t public
```

4. Buka [http://localhost:8000](http://localhost:8000).

## Demo Account

- Admin: `admin@starstyle.test` / `password123`
- Staff: `stylist@starstyle.test` / `password123`
- Customer: `customer@starstyle.test` / `password123`

## Struktur Folder

- `public/` entrypoint, CSS, dan JavaScript
- `app/Controllers` controller halaman dan API
- `app/Services` auth, permission, repository data demo
- `app/Views` layout dan halaman publik/internal
- `config/` konfigurasi aplikasi dan katalog permission
- `database/` skema MySQL dan seed demo
- `storage/cache/` session cache runtime lokal

## Database

- Skema utama: `database/schema.sql`
- Seed demo: `database/seeders/demo_seed.sql`

Import `database/schema.sql` ke MySQL/MariaDB, lalu jika perlu isi data awal dengan `database/seeders/demo_seed.sql`.

## Deploy ke Hosting Apache atau Hostinger

Struktur repo ini sudah disiapkan supaya bisa diupload langsung sebagai project penuh:

1. Upload seluruh isi project ke folder `public_html`.
2. Pastikan file root `index.php` dan root `.htaccess` ikut terupload.
3. Buat file `.env` di root project berdasarkan `.env.example`.
4. Isi `.env` dengan database hosting yang benar.
5. Pastikan folder `storage/cache` dan `storage/cache/payment-proofs` writable.
6. Import `database/schema.sql` ke database hosting.

Contoh `.env` produksi:

```env
APP_TIMEZONE=Asia/Jakarta
APP_DATA_SOURCE=db
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database_hosting
DB_USERNAME=user_database_hosting
DB_PASSWORD=password_database_hosting
DB_CHARSET=utf8mb4
BUSINESS_NAME=StarStyle Salon
BUSINESS_CITY=Jakarta
BUSINESS_HOTLINE=+62 812 0000 0000
BUSINESS_EMAIL=hello@starstyle.fun
BUSINESS_HOURS=09:00 - 20:00
BUSINESS_ADDRESS=Alamat salon Anda
```

## Catatan Implementasi

- Session disimpan ke `storage/cache` agar aman dipakai pada environment lokal ini.
- Folder `storage/cache/payment-proofs` dipakai untuk upload bukti bayar customer dan sebaiknya dibiarkan kosong saat awal deploy, cukup sisakan `.gitkeep`.
- Permission staff dimuat dari default config lalu dapat dioverride admin dari halaman Settings.
- Booking baru dari portal publik masuk sebagai `pending`, sedangkan booking dari internal langsung `confirmed`.
- Seluruh styling memakai tema soft blue clean minimal dengan layout dashboard yang terinspirasi Zenwell.
