# Konteks Proyek — Sistem Buku Tamu & Penomoran Surat

## Cara kerja kita

* Kamu adalah partner software engineer saya, setara senior developer.
* Jangan mengotomasi kode. Jangan langsung membuat atau mengedit file.
* Beri saya instruksi, bukan hasil jadi.
* Kode cukup dikirim sebagai teks di chat.
* Kalau saya salah, bilang saya salah. Kalau saya benar, cukup bilang ya.
* Jangan menambah fitur yang tidak diminta. Kalau menurutmu ada yang kurang, sebutkan sebagai saran terpisah, jangan langsung dimasukkan.
* Jangan langsung menggunakan logo/label/icon dari library seperti google font, etc, buatkan saja placeholder di <img> yang akan anda buat sebagai label/logo/icon.

---

## Tentang proyek

Proyek magang di Diskominfo (Diskominfosanti) Kabupaten Buleleng, Bali.

Di lobi kantor ada PC yang menghadap ke tamu, selama ini hanya menampilkan company profile. PC itu akan dimanfaatkan jadi form buku tamu digital. Sekalian dibuat modul penomoran surat untuk menggantikan buku agenda manual.

Sistem dirancang agar nantinya bisa dipakai OPD lain (multi-tenant), tapi untuk sekarang hanya Diskominfo.

**Timeline:** Rencana diberikan 7 September 2026. Target 31 Oktober 2026 berupa **prototipe**, belum produksi. Magang berakhir 31 Desember 2026. Sisa waktu setelah sistem jadi dipakai untuk dokumentasi.

**Peran saya:** developer tunggal, fullstack. Mentor berperan sebagai pengarah, bukan ikut ngoding.

---

## Tech stack

* Laravel (backend)
* Blade + Alpine.js (frontend). Tidak pakai framework frontend terpisah.
* MySQL lokal untuk development. Server produksi kemungkinan MySQL atau MariaDB.
* Hindari raw query yang spesifik vendor, agar aman kalau nanti pindah ke PostgreSQL. Khususnya: jangan pakai `DATE_FORMAT`/`YEAR()` di raw query (hitung rentang tanggal pakai Carbon lalu `whereBetween`), pakai `true`/`false` untuk boolean, bukan 1/0.

---

## Lingkup yang sudah dikunci

### Modul 1 — Buku Tamu

* Tamu mengisi form di PC display yang menghadap ke lobi.
* **PC admin dan PC display adalah dua komputer terpisah.** Keduanya tidak bisa saling memerintah langsung, komunikasinya lewat database.
* Admin login di PC-nya, menekan tombol "Tampilkan form", lalu PC display berpindah dari layar idle ke form.
* PC display melakukan polling ke server setiap beberapa detik untuk membaca status tampilan. Setiap polling juga memperbarui `terakhir_aktif`.
* Dashboard admin menampilkan dua status terpisah: **status koneksi** (dari `terakhir_aktif`, kurang dari 30 detik = terhubung) dan **status tampilan** (form atau idle).
* PC display dikunci berdasarkan **IP address** + token perangkat opsional. PC admin **tidak** dikunci IP, cukup login biasa, supaya bisa diakses dari mana saja.
* Setelah tamu submit, tampil halaman konfirmasi tanpa menampilkan data yang baru diisi, lalu otomatis kembali ke layar idle.

### Modul 2 — Penomoran Surat

* **Hanya menerbitkan nomor, bukan membuat surat.** Sistem tidak pernah menyentuh file dokumen.
* Surat dibuat sendiri oleh masing-masing bidang di luar sistem. Mereka minta nomor ke FO, admin FO input, nomor keluar, lalu diketik sendiri ke dokumen mereka.
* Fungsinya seperti buku agenda digital.
* Semua input dilakukan admin FO. Pegawai bidang tidak punya akses ke sistem.

### Peran pengguna

| Peran | Akses |
|---|---|
| Tamu | Hanya form di PC display. Tanpa login |
| Admin FO | Seluruh operasional satu OPD: buku tamu, nomor surat, master data, laporan |
| Super Admin | Kelola OPD, kelola user, dashboard rekap, log aktivitas |

Kolom `role` bertipe string dengan nilai `admin_fo` atau `super_admin`. **Tidak pakai kolom `is_super_admin` terpisah** — satu sumber kebenaran saja.

Pembagian pengelolaan master data: yang milik satu OPD (bidang, pegawai, jenis surat) dikelola admin FO. Yang menentukan OPD mana yang ada dan siapa boleh masuk (daftar OPD, user) dikelola super admin.

---

## Aturan teknis yang wajib dipegang

* Semua tabel milik OPD punya kolom `opd_id`, meski sekarang hanya satu instansi.
* **`opd_id` tidak pernah diambil dari input request.** Selalu dari sesi user yang login, atau dari baris `display_devices` berdasarkan IP.
* Satu-satunya pengecualian adalah panel super admin saat membuat user atau OPD baru.
* Pakai trait `BelongsToOpd` dengan global scope agar filter tenant tidak perlu ditulis manual di setiap query.
* Nama tabel memakai bahasa Indonesia bentuk tunggal, jadi setiap model **wajib** menyebut `protected $table` secara eksplisit. Laravel akan menebak jamak bahasa Inggris (`Tamu` → `tamus`) kalau tidak disebutkan.
* Penerbitan nomor surat dibungkus transaksi dengan `lockForUpdate` pada baris counter, supaya tidak ada nomor kembar.
* Nomor surat yang dibatalkan tidak dipakai ulang. Nomornya jadi lubang, alasan pembatalan dicatat.
* Nomor urut reset tiap 1 Januari, dihitung per OPD + jenis surat.
* Format nomor surat disimpan sebagai template di database, bukan di kode.
* Token perangkat display di-hash pakai **SHA-256**, bukan bcrypt. Bcrypt menghasilkan nilai berbeda tiap kali sehingga tidak bisa dicari lewat `where`, dan sengaja lambat — tidak cocok untuk token acak yang di-polling tiap 3 detik.
* Nama aplikasi disimpan di config, bukan ditulis langsung di view.
* Halaman konfirmasi tamu tidak menampilkan data pribadi.
* Rate limit pada endpoint submit form tamu.

---

## Struktur tabel

### Modul buku tamu (sudah bisa dibuat)

**`opd`** — id, kode, nama, alamat, logo, aktif

**`users`** (menambah kolom ke tabel bawaan Laravel) — opd_id (nullable), nip, role, sso_subject_id (nullable, disiapkan untuk SSO nanti), aktif

**`bidang`** — id, opd_id, kode (nullable, mungkin dihapus setelah wawancara), nama, aktif. Unique pada (opd_id, nama). Unique-nya pakai nama karena kode nullable — di MySQL, banyak baris NULL tetap lolos unique constraint

**`pegawai`** — id, opd_id, bidang_id, nama, nip, jabatan, aktif. **Status opsional**, menunggu wawancara

**`display_devices`** — id, opd_id, nama, ip_address (unique), token_hash (nullable, unique, SHA-256), tampilkan_form (boolean), terakhir_aktif (timestamp), aktif

**`tamu`** — id, opd_id, nama, no_hp, instansi_asal, alamat, pekerjaan, jenis_kelamin. Dipisah dari kunjungan agar tamu berulang tidak mengetik ulang. **Tidak menyimpan NIK**

**`kategori_kunjungan`** — id, opd_id, nama, urutan, aktif. Isi awal lewat seeder untuk testing, diganti setelah wawancara. Kalau ternyata tidak perlu, tinggal drop

**`kunjungan`** — id, opd_id, tamu_id, bidang_id, pegawai_id, kategori_kunjungan_id, keperluan, jumlah_orang, waktu_datang, waktu_keluar, status, sudah_dihubungkan, catatan_petugas, sumber_input (display/manual), dicatat_oleh, tanda_tangan

### Modul penomoran surat (JANGAN dibuat dulu)

**`jenis_surat`** — id, opd_id, kode_klasifikasi, nama, format_template, panjang_urut, reset_tahunan, aktif

**`nomor_counters`** — id, opd_id, jenis_surat_id, tahun, nomor_terakhir. Unique pada (opd_id, jenis_surat_id, tahun). Baris inilah yang dikunci saat penerbitan nomor

**`nomor_surat`** — id, opd_id, jenis_surat_id, tahun, nomor_urut, nomor_lengkap, perihal, tujuan_surat, bidang_id, nama_peminta, tanggal_surat, status, alasan_batal, dibuat_oleh. Unique pada (opd_id, jenis_surat_id, tahun, nomor_urut)

### Catatan format nomor surat

Mentor sudah menunjukkan contoh surat: **nomor urut ada di posisi paling depan**, jadi polanya kira-kira `{urut}/{klasifikasi}/{kode_opd}/{tahun}`. Bentuk pastinya masih perlu dikonfirmasi dari contoh surat asli beberapa jenis klasifikasi.

`{klasifikasi}` = kode klasifikasi arsip (000 umum, 005 undangan, 800 kepegawaian, dst), bukan nomor urut. Daftar resminya ada di Peraturan Bupati tentang Tata Naskah Dinas.

`panjang_urut` untuk padding nol (4 → `0501`, 0 → `501`).

Penanda yang disediakan: `{urut}`, `{klasifikasi}`, `{kode_opd}`, `{tahun}`, dan `{bulan_romawi}` bila ternyata dipakai.

---

## Catatan implementasi

### Lokasi trait

`app/Models/Concerns/BelongsToOpd.php`, namespace `App\Models\Concerns`. Folder ini tidak ada bawaan Laravel, dibuat sendiri — PSR-4 membacanya selama namespace cocok dengan struktur folder.

Trait butuh docblock `@mixin \Illuminate\Database\Eloquent\Model` di atasnya agar IDE tidak menandai `addGlobalScope` dan `creating` sebagai undefined. Setelah ditambahkan, **reload window** supaya Intelephense menyegarkan indeks.

Dipakai di: Bidang, Pegawai, Tamu, Kunjungan, KategoriKunjungan, DisplayDevice, JenisSurat, NomorSurat, NomorCounter. Tidak dipakai di Opd dan User.

### Simpan form tamu

Satu form di layar, dua tabel di belakangnya. Urutannya tamu dulu baru kunjungan, dibungkus transaksi supaya tidak ada baris tamu tercipta tanpa kunjungan.

**Belum diputuskan:** kalau nomor HP sudah terdaftar tapi nama berbeda (misal satu nomor kantor dipakai beberapa orang), `firstOrCreate` akan mempertahankan nama lama. Perlu ditanyakan saat wawancara apakah ada tamu yang berbagi nomor telepon.

Untuk halaman daftar tamu pakai eager loading (`with(['tamu', 'bidang'])`), jangan panggil dua model terpisah lalu digabung di Blade.

### Index

Dipasang pada kolom yang sering muncul di `where`, `order by`, atau `join`. Foreign key sudah otomatis di-index oleh `constrained()`.

`opd_id` selalu ditaruh pertama dalam index gabungan, karena berkat global scope setiap query pasti menyertakannya.

Index tidak membantu `like '%kata%'` (wildcard di depan). Untuk skala proyek ini tidak masalah.

---

## Daftar halaman

### Display (tanpa login, dikunci IP)
1. Layar idle — company profile
2. Form tamu
3. Halaman konfirmasi

### Admin FO (login)
1. Login
2. Dashboard — jumlah tamu harian, status display (terhubung/tidak), tombol tampilkan form
3. Daftar tamu hari ini — check-out, tandai sudah dihubungkan
4. Detail kunjungan
5. Riwayat kunjungan — filter, cari, export
6. Input tamu manual
7. Master bidang
8. Master pegawai *(opsional)*
9. Master jenis surat *(tunda)*
10. Buat nomor surat *(tunda)*
11. Daftar nomor surat *(tunda)*
12. Detail nomor surat *(tunda)*
13. Rekap laporan *(menyusul)*

### Super Admin (login)
1. Kelola OPD
2. Kelola user
3. Dashboard rekap
4. Log aktivitas

---

## Status pekerjaan

### Sudah selesai

* Migration modul buku tamu
* Trait `BelongsToOpd`

### Sedang dikerjakan

* Model beserta `protected $table` dan pemasangan trait
* Seeder data awal untuk testing

### Berikutnya

* Layout dasar, sidebar, header
* Login lokal dengan dua peran
* Middleware peran dan penentu tenant
* Master bidang (CRUD pertama, jadi pola untuk CRUD lainnya)

### Belum bisa dikerjakan

| Hal | Menunggu |
|---|---|
| Field final form tamu | Wawancara FO, rencana minggu depan |
| Perlu tidaknya master pegawai | Wawancara FO |
| Perlu tidaknya fitur check-out | Wawancara FO |
| Perlu tidaknya `sudah_dihubungkan` dan `catatan_petugas` | Wawancara FO — keduanya masih dugaan |
| Perlu tidaknya kolom kode bidang | Wawancara FO |
| Perlu tidaknya kolom `urutan` di kategori kunjungan | Wawancara FO |
| Penanganan nomor HP sama dengan nama berbeda | Wawancara FO |
| Format nomor surat & daftar jenis surat | Wawancara FO |
| **Seluruh modul penomoran surat** | Konfirmasi mentor — lihat catatan e-Surat di bawah |
| Integrasi SSO | Izin dari pengelola SSO |
| Deploy | Akses server |

### Catatan penting soal modul penomoran surat

Kabupaten Buleleng sudah punya aplikasi **e-Surat** sejak Desember 2021 yang menangani surat masuk dan surat keluar, sudah dipakai seluruh OPD sampai tingkat desa, dan sudah terintegrasi tanda tangan elektronik. Yang membangunnya adalah Diskominfo sendiri.

Artinya modul penomoran surat berisiko duplikat. **Perlu dikonfirmasi ke mentor** posisinya melengkapi di bagian mana sebelum dikerjakan. Struktur tabelnya sudah disiapkan, tapi migration-nya jangan dijalankan dulu.

Kalau ternyata benar-benar duplikat, fokus penuh ke modul buku tamu dan perdalam bagian laporannya.

### Pertanyaan yang harus dibawa ke wawancara FO

1. Boleh foto buku tamu fisik yang sekarang dipakai? Kolomnya apa saja?
2. Tamu biasanya menyebut nama orang, atau cukup nama bidang?
3. Ada tamu rombongan? Sekarang dicatat satu orang atau semua?
4. Setelah tamu mengisi, apa yang petugas lakukan?
5. Apakah tamu pulang dicatat juga, atau hanya kedatangan?
6. Laporan seperti apa yang diminta pimpinan selama ini?
7. Apa yang paling merepotkan dari cara pencatatan yang sekarang?
8. Layar display sekarang layar sentuh atau pakai keyboard?
9. Bidang di sini punya kode resmi atau cukup nama?
10. Minta contoh surat keluar dari beberapa klasifikasi berbeda untuk memastikan format nomornya.

## Panduan Styling

### Warna

| Peran | Hex | Dipakai untuk |
|---|---|---|
| Primary | `#0F2C59` | Sidebar, header, tombol utama, judul |
| Secondary | `#CA8A04` | Aksen, badge, tombol sekunder, highlight |
| Tertiary | `#1E3A8A` | Tautan, status aktif, elemen pendukung |
| Neutral | `#334155` | Teks utama, border, ikon |

Background halaman pakai putih atau abu sangat terang (`#F8FAFC`). Kartu dan panel putih dengan border tipis neutral, jangan pakai shadow tebal.

Warna status: hijau untuk terhubung dan tamu selesai, abu untuk tidak terhubung, merah untuk nomor batal dan tombol hapus, kuning untuk peringatan.

### Tipografi

| Peran | Font | Catatan |
|---|---|---|
| Headline | Inter | Judul halaman, angka besar di dashboard |
| Body | Inter | Teks isi, tabel, paragraf |
| Label | Inter | Label form, header tabel, teks kecil |

Keduanya tersedia di Google Fonts. Muat hanya bobot yang dipakai — 400, 500, 600, 700 — supaya halaman tidak berat.

### Aturan komponen

**Tombol** — empat varian: primary (isi biru tua), secondary (isi terang), inverted (biru tua di latar terang), outlined (hanya garis). Sudut membulat sedang, jangan pill. Tinggi seragam di seluruh aplikasi.

**Tabel** — header dengan latar abu terang, baris dipisah garis tipis, tanpa garis vertikal. Baris genap boleh diberi latar sangat terang.

**Form** — label di atas input, bukan di samping. Pesan error di bawah input dengan warna merah. Input wajib ditandai tanda bintang.

**Kartu statistik dashboard** — angka besar pakai Plus Jakarta Sans, label kecil di bawahnya pakai Inter, ikon di pojok kanan atas dengan warna sesuai konteks.

### Halaman display (berbeda aturannya)

Halaman yang menghadap tamu tidak mengikuti ukuran panel admin. Tamu berdiri, jaraknya jauh dari layar, dan belum tentu terbiasa.

Ukuran teks minimal dua kali lipat panel admin. Tombol minimal 56px tinggi, lebar penuh atau mendekati. Satu layar satu fokus, jangan padat. Tanpa sidebar, tanpa menu, tanpa navigasi apa pun. Kontras tinggi, jangan pakai abu tipis untuk teks.

Layar idle memakai warna primary sebagai latar penuh dengan logo instansi di tengah — berbeda jelas dari form supaya petugas bisa melihat statusnya dari kejauhan.