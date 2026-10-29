# E-FO — Sistem Buku Tamu & Penomoran Surat

Prototipe proyek magang di Diskominfo (Diskominfosanti) Kabupaten Buleleng, Bali.

Sistem ini punya dua modul:

- **Buku Tamu** — tamu mengisi form dan tanda tangan di PC display lobi; petugas Front Office (FO) memantau dan menginput lewat panel admin.
- **Penomoran Surat** — menerbitkan nomor surat (seperti buku agenda digital). Sistem hanya menerbitkan nomor, tidak membuat atau menyimpan dokumen surat.

Dirancang multi-tenant (data dipisah per OPD); data awal hanya untuk Diskominfosanti.

## Stack dan kebutuhan

- PHP 8.3 dengan ekstensi `zip` (untuk ekspor Excel)
- Composer dan Node.js + npm
- MySQL 8 atau MariaDB, dengan privilege `TRIGGER` (penomoran memakai trigger database)
- Laravel 13, Filament v5 (Livewire 4), Blade + Alpine.js, Tailwind v4, Vite

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat database kosong:

```sql
CREATE DATABASE projectmagang CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Ubah `.env`, karena bawaan `.env.example` memakai SQLite:

```env
APP_NAME=E-FO
APP_URL=http://projectmagang.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=projectmagang
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=sync
```

Migrasi, seeding, dan build aset:

```bash
php artisan migrate --seed
npm run build
```

Jalankan lewat web server lokal (misalnya Laragon, `http://projectmagang.test`) atau `php artisan serve`, lalu buka `/login`.

Catatan:

- Jangan pakai `composer setup`: skrip itu memigrasi ke SQLite, bukan MySQL.
- Mengulang dari awal pakai `php artisan migrate:fresh --seed`. Jangan `migrate:refresh`, karena trigger ikut terhapus bersama tabelnya.
- Seeder `DisplayDeviceSeeder` mencetak **token display** satu kali di terminal. Catat token itu, lalu pakai di `/display/pair` pada PC display.
- Setelah mengubah `.env`, jalankan `php artisan config:cache` kalau memakai Apache di Windows.

## Seeding dan akun demo

Isi seeding bergantung pada `APP_ENV`:

| `APP_ENV` | Hasil |
|---|---|
| `local` | Data dasar (OPD, bidang, format nomor, display) + data uji (100 nomor surat, kunjungan, OPD Dinkes) + akun demo di bawah |
| selain `local` | Data dasar saja. Kata sandi super admin **ditanyakan lewat prompt** (minimal 12 karakter), akun `adminfo` tidak dibuat |

Data uji tidak idempoten: menjalankan seeder lagi menambah data uji lagi.

Akun demo (hanya di `APP_ENV=local`):

| Email | Peran | Panel |
|---|---|---|
| `superadmin@example.com` | `super_admin` | `/super-admin` |
| `adminfo@example.com` | `admin_fo` (Diskominfosanti) | `/admin` |
| `dinkes@example.com` | `admin_fo` (Dinkes) | `/admin` |

Kata sandi ketiganya `password`.

### Keamanan kata sandi

`password` hanya kata sandi **awal** dari seeder, bukan untuk dipakai terus. Ganti dengan **minimal 12 karakter** sebelum sistem dipakai selain di mesin development:

1. Login sebagai super admin, buka **Kelola Pengguna**.
2. Ubah pengguna yang dituju (termasuk akun super admin sendiri), isi kolom kata sandi dengan minimal 12 karakter, lalu simpan. Kolom dikosongkan berarti kata sandi tidak berubah.

`.env` produksi yang disalin dari `.env.example` masih berisi `APP_ENV=local`, sehingga seeding memakai `password` dan menanam data uji. Pastikan `APP_ENV=production` dan `APP_DEBUG=false` sebelum menjalankan seeder di server.
