# Konteks Proyek — Sistem Buku Tamu & Penomoran Surat (E-FO)

Terakhir diperbarui: 12 Oktober 2026.

## Cara kerja kita

* Kamu adalah partner software engineer saya, setara senior developer.
* Jangan mengotomasi kode. Jangan langsung membuat atau mengedit file.
* Beri saya instruksi, bukan hasil jadi.
* Kode cukup dikirim sebagai teks di chat.
* Kalau saya salah, bilang saya salah. Kalau saya benar, cukup bilang ya.
* Jangan menambah fitur yang tidak diminta. Kalau menurutmu ada yang kurang, sebutkan sebagai saran terpisah, jangan langsung dimasukkan.
* Jangan langsung menggunakan logo/label/icon dari library seperti google font, etc, buatkan saja placeholder di <img> yang akan anda buat sebagai label/logo/icon.

Pengecualian yang sudah disepakati untuk aturan ikon:

* Font Inter boleh dipakai.
* Di dalam panel Filament, Heroicons bawaan Filament boleh dipakai.
* Ikon atau animasi bawaan sebuah komponen UI utuh (misalnya SweetAlert2) boleh dipakai apa adanya.
* Halaman non-Filament (login, display) tetap memakai placeholder `<img>`.

Konvensi penulisan kode:

* Semua kode (variabel, function, method, scope, class service) memakai bahasa Inggris.
* Bahasa Indonesia hanya untuk nama model, nama tabel, dan nama field tabel.
* Nama method relasi Eloquent mengikuti nama model: `bidang()`, `opd()`, `petugas()`, `pembuat()`, `pembatal()`.
* Nama kolom memakai awalan yang jelas: `kode_opd`, `nama_opd`, `kode_bidang`, `nama_bidang`, `nama_tamu`. Jangan disingkat.

---

## Tentang proyek

Proyek magang di Diskominfo (Diskominfosanti) Kabupaten Buleleng, Bali.

Di lobi kantor ada PC yang menghadap ke tamu, selama ini hanya menampilkan company profile. PC itu dimanfaatkan jadi form buku tamu digital. Sekalian dibuat modul penomoran surat untuk menggantikan buku agenda manual.

Sistem dirancang multi-tenant agar bisa dipakai OPD lain, tapi data awal hanya untuk Diskominfosanti.

**Timeline:** rencana diberikan 7 September 2026. Target 31 Oktober 2026 berupa **prototipe**, belum produksi. Magang berakhir 31 Desember 2026. Sisa waktu setelah sistem jadi dipakai untuk dokumentasi.

**Peran saya:** developer tunggal, fullstack.

**Dua pihak pengarah, jangan tertukar:**

* **Mentor** — pihak kantor. Memutuskan hal operasional dan hak akses, bersama petugas Front Office (FO).
* **Dosbing** — dosen pembimbing kampus. Memberi arahan rancangan (lihat bagian "Arahan dosbing"). Bukan pemutus aturan kantor.

---

## Tech stack

* Laravel 13, PHP 8.3
* **Filament v5** (di atas Livewire 4) untuk panel admin FO dan panel super admin
* Blade + Alpine.js untuk halaman login dan halaman display
* Tailwind v4, Vite, `laravel-vite-plugin` v3 (font Inter diunduh saat build lewat fitur `fonts`, jadi PC display tidak butuh internet)
* SweetAlert2 untuk notifikasi di panel (notifikasi bawaan Filament disembunyikan)
* `signature_pad` untuk tanda tangan tamu
* OpenSpout untuk export Excel
* MySQL 8.4 lokal. Server produksi kemungkinan MySQL atau MariaDB.

Aturan query: hindari raw query yang spesifik vendor di kode aplikasi. Jangan pakai `DATE_FORMAT`/`YEAR()` di raw query (hitung rentang tanggal pakai Carbon lalu `whereBetween`), dan pakai `true`/`false` untuk boolean.

**Satu pengecualian yang disengaja:** trigger penomoran ditulis dalam SQL MySQL/MariaDB dan dikurung di satu migration (`create_numbering_triggers`). Lihat bagian "Penomoran di sisi database".

---

## Lingkup

### Modul 1 — Buku Tamu

* Tamu mengisi form di PC display yang menghadap ke lobi.
* **PC admin dan PC display adalah dua komputer terpisah.** Komunikasinya lewat database.
* Admin login di PC-nya, menekan tombol "Tampilkan form", lalu PC display berpindah dari layar idle ke form.
* PC display melakukan polling `/display/status` tiap 3 detik. `terakhir_aktif` ditulis hanya kalau sudah lebih dari 10 detik.
* Panel admin menampilkan dua status terpisah: **status koneksi** (`terakhir_aktif` kurang dari 30 detik = terhubung) dan **status tampilan** (form atau idle).
* PC display dikunci dengan **token**, bukan IP. Pairing lewat `/display/pair`, token disimpan di cookie `display_token`. Token menentukan OPD.
* PC admin tidak dikunci, cukup login biasa.
* Form display muat satu layar tanpa scroll (dua kolom) dan mewajibkan tanda tangan.
* Setelah submit, tampil halaman konfirmasi tanpa data pribadi, lalu kembali ke layar idle.
* Input oleh FO (manual) memakai form di panel admin, tanpa tanda tangan.
* Setiap kunjungan mendapat **nomor kunjungan** otomatis `YYYYMMDD-NNNN`, reset tiap bulan.
* Setiap kunjungan mencatat petugas: untuk input manual adalah FO yang mengetik, untuk input display adalah FO yang terakhir menekan "Tampilkan form".
* Buku tamu hanya mencatat **siapa datang dan perlu apa**. Tidak ada check-out, status kunjungan, maupun tanda "sudah dihubungi".

### Modul 2 — Penomoran Surat

* **Hanya menerbitkan nomor, bukan membuat surat.** Sistem tidak pernah menyentuh file dokumen.
* Surat dibuat masing-masing bidang di luar sistem. Mereka minta nomor ke FO, FO menginput, nomor keluar, lalu diketik sendiri ke dokumen.
* Fungsinya seperti buku agenda digital.
* Modul ini **tidak berhubungan dengan e-Surat** dan pasti dibuat. e-Surat hanya menangani surat masuk, disposisi, dan surat keluar. Penomoran di semua OPD masih memakai buku fisik.

### Peran pengguna

| Peran | Akses |
|---|---|
| Tamu | Hanya form di PC display. Tanpa login |
| `admin_fo` | Panel `/admin`: buku tamu, nomor surat, master bidang, rekap |
| `super_admin` | Panel `/super-admin`: kelola OPD, pengguna, display, format nomor surat |

* Kolom `role` bertipe string. Tidak ada kolom `is_super_admin`.
* Super admin **tidak punya akses** ke panel admin FO, dan `users.opd_id`-nya selalu null. `opd_id` berarti batas data, bukan asal instansi orangnya.
* Akses panel diatur `User::canAccessPanel()`, bukan middleware peran.
* Peran `staff` **belum ada**. Lihat "Keputusan yang belum pasti".

---

## Aturan teknis yang wajib dipegang

**Tenant**

* Semua tabel milik OPD punya kolom `opd_id`.
* **`opd_id` tidak pernah diambil dari input request.** Selalu dari user yang login, atau dari baris `display_devices` berdasarkan token.
* Satu-satunya pengecualian adalah panel super admin (membuat user, OPD, format nomor). Di sana `opd_id` diisi lewat `forceFill`.
* Trait `BelongsToOpd` (`app/Models/Concerns`) memasang global scope bernama `'opd'` dan mengisi `opd_id` saat `creating`. Dipakai di: Bidang, Kunjungan, DisplayDevice, FormatNomorSurat, NomorSurat, NomorCounter. Tidak dipakai di Opd dan User.
* Di tinker tidak ada user login, jadi pakai `withoutGlobalScope('opd')` atau `app()->instance('current_opd_id', ...)`.
* Jangan pakai fitur `->tenant()` Filament.

**Skema**

* Semua primary key **UUID** (`HasUuids`, menghasilkan UUID v7). Semua foreign key `foreignUuid`. Termasuk `users` dan `sessions.user_id`.
* Nama tabel bahasa Indonesia bentuk tunggal, jadi setiap model **wajib** menyebut `protected $table`.
* Foreign key ke `opd` dan master data memakai `restrictOnDelete()`. Data dinonaktifkan lewat kolom `aktif`, bukan dihapus.
* Pada `foreignUuid`, **`nullable()` selalu ditulis sebelum `constrained()`**. Kalau terbalik, `nullable()` diabaikan diam-diam.
* `opd_id` selalu ditaruh pertama dalam index gabungan.
* Kolom yang nilainya ditentukan sistem tidak masuk Fillable: `opd_id`, `role`, `aktif`, `sso_subject_id` di User; `status`, `nomor_urut`, `sub_nomor`, `tahun`, `nomor_lengkap` di NomorSurat; `nomor_kunjungan` di Kunjungan. Diisi eksplisit lewat `forceFill`.

**Lain-lain**

* Token perangkat display di-hash **SHA-256**, bukan bcrypt (harus bisa dicari lewat `where`, dan dipolling tiap 3 detik).
* Tanda tangan disimpan sebagai file PNG di disk privat `local`, path `tanda-tangan/Y/m/uuid.png`. Kolom `tanda_tangan` hanya menyimpan path.
* Nama aplikasi dari config (`APP_NAME=E-FO`), tidak ditulis langsung di view.
* Rate limit `display-submit` 5 per menit pada submit form tamu. Ada halaman error 429.
* Timezone `Asia/Makassar`. `SESSION_DRIVER=database`, `QUEUE_CONNECTION=sync`.
* Realtime di panel memakai `->poll()`. Laravel Reverb ditunda.
* Controller tipis, logika di service: `AuthService`, `KunjunganService`, `LetterNumberService`, `LetterNumberFormatter`.

---

## Struktur tabel (sesuai migration)

Semua tabel punya `id` (uuid) dan `timestamps` kecuali disebut lain.

**`opd`** — kode_opd (20, unique), nama_opd, alamat_opd (nullable), logo (nullable), aktif (default true)

**`users`** (tabel bawaan Laravel + tambahan) — name, email (unique), password, opd_id (nullable), nip (30, nullable, unique), role (default `admin_fo`), sso_subject_id (nullable, unique, disiapkan untuk SSO), aktif (default true)

**`bidang`** — opd_id, kode_bidang (20, wajib), nama_bidang, aktif. Unique (opd_id, kode_bidang). `kode_bidang` wajib karena dipakai di nomor surat.

**`display_devices`** — opd_id, nama, token_hash (64, unique), tampilkan_form (default false), ditampilkan_oleh (nullable, FK users), terakhir_aktif (nullable), aktif (default false). Tidak ada kolom `ip_address`.

**`kunjungan`** — opd_id, bidang_id (nullable), nomor_kunjungan (13, diisi trigger), nama_tamu, jenis_kelamin (10), instansi_asal (nullable), no_hp (nullable), alamat (nullable), keperluan, **pegawai_tujuan (nullable, teks bebas)**, tanda_tangan (nullable, path file), waktu_datang, catatan_petugas (nullable, tampil sebagai "Keterangan"), sumber_input (`display` atau `frontoffice`), dicatat_oleh (nullable, FK users). Unique (opd_id, nomor_kunjungan). Index (opd_id, waktu_datang) dan (opd_id, nama_tamu).

> **Belum diterapkan di kode per 12 Oktober:** `pegawai_tujuan`. Migration masih berisi `pegawai_id` (FK ke `pegawai`), dan tabel, model, resource, serta seeder pegawai masih ada. Lihat "Sudah diputuskan, belum dikerjakan".

**`format_nomor_surat`** — opd_id, susunan (json, urutan segmen), berlaku_sejak (date), dasar_perubahan (nullable), dibuat_oleh (FK users). Unique (opd_id, berlaku_sejak). Berversi: format yang berlaku adalah yang `berlaku_sejak`-nya paling baru dan tidak melewati tanggal acuan.

**`nomor_counters`** — opd_id, jenis (`kunjungan` atau `surat`), periode (6 karakter: `202610` untuk kunjungan, `2026` untuk surat), nomor_terakhir. Unique (opd_id, jenis, periode). Hanya ditulis oleh trigger dan oleh `LetterNumberService::setLastNumber()`.

**`nomor_surat`** — opd_id, format_nomor_surat_id, bidang_id (nullable), tahun, nomor_urut (default 0), sub_nomor (default 0), nomor_lengkap (nullable, dirakit aplikasi setelah insert), kode_klasifikasi (20), perihal, tujuan_surat (nullable), nama_peminta (nullable), tanggal_surat (date), tanggal_terbit (timestamp), status (`terbit` atau `batal`), alasan_batal (nullable), dibuat_oleh (FK users), dibatalkan_oleh (nullable, FK users), batal_pada (nullable). Unique (opd_id, tahun, nomor_urut, sub_nomor). Index (opd_id, tanggal_terbit).

**Tabel yang sudah dibatalkan dan tidak ada:** `tamu`, `kategori_kunjungan`, `jenis_surat`. Kolom yang sudah dibuang dari `kunjungan`: `waktu_keluar`, `status`, `jumlah_orang`, `sudah_dihubungi`.

---

## Penomoran di sisi database

Pembuatan nomor urut ditaruh di **trigger**, bukan di aplikasi, mengikuti arahan dosbing. Semua trigger ada di satu migration: `2026_10_06_115813_create_numbering_triggers.php`.

| Trigger | Fungsi |
|---|---|
| `kunjungan_before_insert` | Mengisi `nomor_kunjungan` dari counter bulan `waktu_datang`. Menolak kalau melewati 9999 |
| `nomor_surat_before_insert` | `nomor_urut = 0` → ambil nomor baru dari counter. Selain itu → susulan: kunci counter, pastikan induk ada, `sub_nomor = MAX + 1` |
| `nomor_surat_before_update` | Bagian nomor tidak bisa diubah. Status `batal` tidak bisa dipulihkan |
| `nomor_surat_before_delete` | Nomor surat tidak bisa dihapus |
| `nomor_counters_before_update` | `nomor_terakhir` tidak bisa diturunkan |

Aturan saat menulis atau mengubah trigger:

* Satu `CREATE TRIGGER` per panggilan `DB::unprepared`. Setiap trigger baru harus punya `DROP TRIGGER IF EXISTS` di `down()`.
* **Jangan `DECLARE` variabel string lalu membandingkannya dengan kolom**, dan jangan pakai `CAST(... AS CHAR)`. Keduanya mengikuti collation default database dan memicu error 1267. Tulis ekspresinya langsung (`DATE_FORMAT(...)`, `CONCAT(NEW.tahun)`, string literal). Jangan menulis `COLLATE` secara mati.
* `LPAD` memotong diam-diam, jadi batas kapasitas harus dicek dulu.
* Trigger ikut terhapus saat tabelnya di-drop. **Selalu `migrate:fresh --seed`**, jangan `migrate:refresh --path` pada tabel yang punya trigger.
* Mengedit file migration tidak mengubah trigger yang sudah terpasang. Migration harus dijalankan ulang.
* Error dari `SIGNAL` muncul sebagai `SQLSTATE[45000] <<Unknown error>>`. Pesan aslinya ada sesudahnya.

### Aturan nomor surat

* Format Diskominfosanti: `KLASIFIKASI/URUT/BIDANG/KODEOPD/BULAN_ROMAWI/TAHUN`, contoh `443.32/102/PKP/DISKOMINFOSANTI/X/2026`. Pemisah selalu `/`, tanpa padding nol.
* Segmen yang tersedia: `klasifikasi`, `urut`, `bidang` (kode bidang), `kode_opd`, `bulan_romawi`, `tahun`, `teks`. Format wajib punya tepat satu `urut`.
* Satu deret nomor per OPD per tahun, reset 1 Januari. Tidak ada master jenis surat. Kode klasifikasi diketik FO.
* Form penerbitan menyesuaikan susunan format yang berlaku. Segmen yang ditentukan sistem tampil terkunci.
* **Tanggal surat diisi FO: boleh mundur, tidak boleh maju.** Bulan romawi, tahun di nomor, dan tahun counter diambil dari tanggal surat, bukan tanggal terbit.
* **Susulan otomatis.** Kalau tanggal surat mundur dan sudah ada nomor induk yang terbit sesudah tanggal itu, nomornya menjadi susulan (`102.1`, `102.2`). Induknya adalah nomor induk terakhir yang terbit pada atau sebelum tanggal itu. Nomor yang batal tetap dihitung sebagai induk.
* **Tidak ada edit, draf, maupun hapus.** Nomor yang salah dibatalkan dengan alasan, lalu diterbitkan ulang. Nomor batal menjadi lubang dan tidak dipakai ulang.
* **Nomor terakhir** bisa diatur FO untuk melanjutkan dari buku agenda. Hanya boleh naik.
* Urutkan selalu lewat kolom `(tahun, nomor_urut, sub_nomor)`. **Jangan pernah `orderBy('nomor_lengkap')`.**
* `LetterNumberFormatter` melempar exception kalau ada segmen di template yang nilainya kosong, supaya nomor cacat tidak terbit.

---

## Arahan dosbing (6 Oktober 2026)

Pegangan rancangan, bukan aturan kantor:

* Nomor di-generate sistem, tidak diketik atau dipilih staf. Format tetap fleksibel lewat template.
* Elemen yang jarang berubah di kiri, yang sering berubah di kanan. `yyyymmdd-9999` ditafsirkan sebagai kunci urut di database, bukan format nomor surat yang tercetak.
* Pengurutan disimulasikan (naik dan turun) sebelum format dikunci.
* Sequence ditaruh di sisi database supaya tidak perlu ditulis ulang kalau bahasa pemrograman berganti.
* Penerbitan nomor terpusat di petugas, bukan setiap pegawai punya akun sendiri.
* Buku tamu punya dua antarmuka yang mirip (display dan petugas). Isian tamu tetap disupervisi petugas.
* Kedua modul mencatat petugas yang bertugas.
* Partitioning: **tidak diimplementasikan**. Bentrok dengan foreign key dan primary key UUID, dan volumenya kecil. Cukup disebut di dokumentasi.

---

## Sudah diputuskan, belum dikerjakan

| Keputusan | Yang perlu dilakukan |
|---|---|
| **Hapus master pegawai** (12 Okt). Beban input terlalu besar bagi OPD lain, karena hanya Diskominfosanti yang punya datanya | Hapus migration, model, resource, seeder, dan CSV pegawai. Ganti `kunjungan.pegawai_id` dengan `pegawai_tujuan` (string, nullable). Sesuaikan Kunjungan, KunjunganService, StoreKunjunganRequest, DisplayController, view display, KunjunganResource, VisitHistoryResource, VisitHistoryExport, DatabaseSeeder, KunjunganSeeder |
| Test untuk trigger | Pasang dulu pengaman di `phpunit.xml` (lihat "Lingkungan development"), baru tulis test: nomor baru, susulan, reset bulanan dan tahunan, immutability, pembatalan |
| Rekap surat di dashboard | Dashboard yang sama dengan rekap kunjungan. Nomor batal dipisahkan dari jumlah terbit |

---

## Keputusan yang belum pasti

Jangan diputuskan sendiri. Semuanya menunggu pihak yang disebut.

| Hal | Menunggu | Keadaan sekarang di kode |
|---|---|---|
| **Susulan tanpa induk**: surat bertanggal sebelum sistem dipakai, atau sebelum nomor pertama tahun itu | Mentor dan FO | Ditolak lewat `LetterBaseNumberNotFoundException` dengan pesan netral. Exception ini sengaja dipertahankan sebagai penanda |
| **Tanggal mulai pakai sistem.** Usulan: 1 Januari 2027, supaya riwayat setahun penuh ada di sistem | Mentor | Belum ditentukan |
| **Apakah `staff` boleh menerbitkan nomor surat** | Mentor dan FO | Hanya `admin_fo`. Peran `staff` belum dibuat. Helper akses per modul sengaja **tidak** dibuat sebelum ada keputusan |
| Integrasi SSO | Izin pengelola SSO | Login lokal dengan email. Kolom `sso_subject_id` sudah disiapkan |
| Deploy | Akses server | Belum ada |
| Posisi akhir modul penomoran terhadap e-Surat | Mentor | Berdiri sendiri. Kalau kelak digabung, itu pekerjaan tim e-Surat |

Dua opsi yang sedang ditimbang untuk susulan tanpa induk:

1. Mulai pakai 1 Januari sehingga kasusnya hampir tidak muncul, dan sisanya diberi nomor baru biasa (cukup menghapus `throw`).
2. Tetap dilayani dengan nomor baru disertai alasan yang tercatat.

Usulan "FO mengetik nomor dari buku agenda" sudah ditimbang dan tidak disarankan: masalahnya sementara, kompleksitasnya permanen, dan bergantung pada buku yang justru sering tidak tersedia.

**Yang perlu ditanyakan saat dapat akses server:**

1. MySQL atau MariaDB, versi berapa.
2. Apakah user database punya privilege `TRIGGER`.
3. Apakah binary log aktif. Kalau aktif, perlu `log_bin_trust_function_creators = 1` (kalau tidak, error 1419).
4. Apakah boleh menjalankan `php artisan migrate` di server. Database **jangan dipindah lewat dump**, karena trigger menyimpan DEFINER `root@localhost`.

Rencana cadangan kalau server menolak trigger: logika counter dipindah ke service dengan `lockForUpdate()` pada tabel `nomor_counters` yang sama. Skema tidak berubah.

---

## Backlog

* Halaman kelola display di panel admin FO (buat ulang token, lepas pairing) beserta kolom `dipasangkan_pada`
* Dashboard rekap lintas OPD dan log aktivitas di panel super admin. Log aktivitas sekaligus mencatat siapa yang mengubah nomor terakhir
* Filter tabel nomor surat (tahun, bidang, status)
* Validasi "segmen klasifikasi wajib ada" di master format. Tanpa itu, format tanpa klasifikasi selalu gagal menyimpan karena `kode_klasifikasi` wajib diisi
* Pratinjau satu baris nomor surat di form penerbitan
* Persiapan deploy: `optimize`, OPcache

---

## Daftar halaman

### Display (tanpa login, dikunci token)

| Halaman | Status |
|---|---|
| Pairing (`/display/pair`) | Selesai |
| Layar idle (video profil) | Selesai |
| Form tamu dengan tanda tangan | Selesai |
| Halaman konfirmasi | Selesai |

### Admin FO (`/admin`)

| Halaman | Status |
|---|---|
| Login (halaman custom, bukan login Filament) | Selesai |
| Rekap Kunjungan (dashboard, filter tanggal, export Excel) | Selesai |
| Tamu Hari Ini (termasuk input manual dan status display) | Selesai |
| Riwayat Kunjungan (filter, cari, detail) | Selesai |
| Nomor Surat (terbitkan, susulan, batalkan, atur nomor terakhir) | Selesai |
| Kelola Bidang | Selesai |
| Kelola Pegawai | **Akan dihapus** |
| Rekap surat | Belum |

### Super Admin (`/super-admin`)

| Halaman | Status |
|---|---|
| Kelola OPD | Selesai |
| Kelola Pengguna | Selesai |
| Kelola Display | Selesai |
| Format Nomor Surat | Selesai |
| Dashboard rekap | Belum |
| Log aktivitas | Belum |

---

## Lingkungan development

* Diuji lewat Apache Laragon di Windows (`http://projectmagang.test`), dengan PC display kedua di jaringan yang sama.
* **`php artisan config:cache` wajib** setiap kali `.env` atau `config/` diubah. Apache Windows multi-thread, dan pembacaan `.env` tidak aman antar-thread. Gejala kalau cache tidak ada: error acak "No application encryption key", dan **modal 404 acak** di panel (Livewire menurunkan alamat endpoint-nya dari `APP_KEY`).
* **`npm run build` wajib** setiap kali JS atau CSS diubah. Aset dilayani dari `public/build`. `npm run dev` tidak cocok untuk PC display, karena alamatnya menunjuk ke localhost PC development.
* Error `rename(...storage/framework/views...): Access is denied` dan Apache lambat: kecualikan `C:\laragon` dari Windows Defender, lalu `php artisan view:cache` dan `php artisan icons:cache`.
* **Jangan jalankan `optimize` atau `filament:optimize`** saat development. Resource baru jadi tidak terdeteksi.
* Log aplikasi: `storage/logs/laravel.log`. Log request: `C:\laragon\bin\apache\...\logs\access.log`.
* Cookie display terikat host. Berganti cara akses berarti pairing ulang.
* `database/data/data_pegawai.csv` dan file video tidak boleh masuk git.

**Peringatan test.** `phpunit.xml` menyetel SQLite `:memory:`, tetapi selama `bootstrap/cache/config.php` ada, setelan itu **diabaikan** dan test berjalan di database development. Test yang memakai `RefreshDatabase` akan mengosongkannya. SQLite juga tidak bisa menjalankan trigger, dan mengabaikan `lockForUpdate`. Sebelum menulis test pertama:

1. Buat database `projectmagang_test`.
2. Di `phpunit.xml`, ubah `DB_CONNECTION` jadi `mysql` dan `DB_DATABASE` jadi `projectmagang_test`.
3. Tambahkan `<env name="APP_CONFIG_CACHE" value="bootstrap/cache/config.testing.php"/>`.
4. Pastikan `config('database.connections.mysql.database')` di dalam test menghasilkan `projectmagang_test`.

---

## Panduan Styling

### Warna

| Peran | Hex | Dipakai untuk |
|---|---|---|
| Primary | `#0F2C59` | Sidebar, header, tombol utama, judul |
| Secondary | `#CA8A04` | Aksen, badge, tombol sekunder, highlight |
| Tertiary | `#1E3A8A` | Tautan, status aktif, elemen pendukung |
| Neutral | `#334155` | Teks utama, border, ikon |

Background halaman pakai putih atau abu sangat terang (`#F8FAFC`). Kartu dan panel putih dengan border tipis, jangan pakai shadow tebal.

Warna status: hijau untuk terhubung dan nomor terbit, abu untuk tidak terhubung, merah untuk nomor batal dan tombol batalkan, kuning untuk peringatan.

Warna panel Filament diatur di `->colors()` PanelProvider (success `#16A34A`, warning `#F59E0B`, danger `#DC2626`, gray Slate). Token yang sama ada di `resources/css/app.css`. Keduanya harus tetap sinkron.

### Tipografi

**Inter untuk semua peran** (judul, isi, label), bobot 400, 500, 600, 700. Hierarki dibedakan lewat bobot dan ukuran.

### Aturan komponen

**Tombol** — sudut membulat sedang, jangan pill. Tinggi seragam.

**Tabel** — header dengan latar abu terang, baris dipisah garis tipis, tanpa garis vertikal. Placeholder sel kosong memakai `-`.

**Form** — label di atas input. Pesan error di bawah input dengan warna merah. Input wajib ditandai tanda bintang.

### Halaman display (berbeda aturannya)

Halaman yang menghadap tamu tidak mengikuti ukuran panel admin. Tamu berdiri, jaraknya jauh dari layar, dan belum tentu terbiasa.

Ukuran teks minimal dua kali lipat panel admin. Tombol minimal 56px tinggi, lebar penuh atau mendekati. Satu layar satu fokus. Tanpa sidebar, tanpa menu, tanpa navigasi apa pun. Kontras tinggi, jangan pakai abu tipis untuk teks.

Layar idle berbeda jelas dari form supaya petugas bisa melihat statusnya dari kejauhan.
