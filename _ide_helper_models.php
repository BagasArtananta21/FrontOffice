<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property string $id
 * @property string $opd_id
 * @property string $kode_bidang
 * @property string $nama_bidang
 * @property bool $aktif
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Opd $opd
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang acrossOpd()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang whereAktif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang whereKodeBidang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang whereNamaBidang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang whereOpdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bidang whereUpdatedAt($value)
 */
	class Bidang extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $opd_id
 * @property string $nama
 * @property string|null $token_hash
 * @property string|null $pin
 * @property string|null $pin_hmac
 * @property bool $tampilkan_form
 * @property string|null $ditampilkan_oleh
 * @property string|null $kunjungan_menunggu_id
 * @property \Illuminate\Support\Carbon|null $terakhir_aktif
 * @property \Illuminate\Support\Carbon|null $muat_ulang_diminta_pada
 * @property bool $aktif
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Kunjungan|null $kunjunganMenunggu
 * @property-read \App\Models\Opd $opd
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice acrossOpd()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice availableForSignature()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice connected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereAktif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereDitampilkanOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereKunjunganMenungguId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereMuatUlangDimintaPada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereOpdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice wherePin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice wherePinHmac($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereTampilkanForm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereTerakhirAktif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereTokenHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisplayDevice whereUpdatedAt($value)
 */
	class DisplayDevice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $opd_id
 * @property array<array-key, mixed> $susunan
 * @property \Illuminate\Support\Carbon $berlaku_sejak
 * @property string|null $dasar_perubahan
 * @property bool $izinkan_susulan
 * @property string $dibuat_oleh
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NomorSurat> $nomorSurat
 * @property-read int|null $nomor_surat_count
 * @property-read \App\Models\Opd $opd
 * @property-read \App\Models\User $pembuat
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat acrossOpd()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat berlakuPada(\Carbon\CarbonInterface $tanggal)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat whereBerlakuSejak($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat whereDasarPerubahan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat whereDibuatOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat whereIzinkanSusulan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat whereOpdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat whereSusunan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormatNomorSurat whereUpdatedAt($value)
 */
	class FormatNomorSurat extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $opd_id
 * @property string|null $bidang_id
 * @property string $nomor_kunjungan
 * @property string $nama_tamu
 * @property string $jenis_kelamin
 * @property string|null $instansi_asal
 * @property string|null $no_hp
 * @property string|null $alamat
 * @property string|null $pegawai_tujuan
 * @property string|null $keperluan
 * @property string|null $tanda_tangan
 * @property string|null $alasan_tanpa_ttd
 * @property \Illuminate\Support\Carbon $waktu_datang
 * @property string|null $catatan_petugas
 * @property string $sumber_input
 * @property string|null $dicatat_oleh
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Bidang|null $bidang
 * @property-read \App\Models\DisplayDevice|null $displayMenunggu
 * @property-read \App\Models\User|null $petugas
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan acrossOpd()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereAlamat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereAlasanTanpaTtd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereBidangId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereCatatanPetugas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereDicatatOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereInstansiAsal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereJenisKelamin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereKeperluan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereNamaTamu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereNoHp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereNomorKunjungan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereOpdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan wherePegawaiTujuan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereSumberInput($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereTandaTangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kunjungan whereWaktuDatang($value)
 */
	class Kunjungan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $opd_id
 * @property string $jenis
 * @property string $periode
 * @property int $nomor_terakhir
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter acrossOpd()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter whereJenis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter whereNomorTerakhir($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter whereOpdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter wherePeriode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorCounter whereUpdatedAt($value)
 */
	class NomorCounter extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $opd_id
 * @property string $format_nomor_surat_id
 * @property string|null $bidang_id
 * @property int $tahun
 * @property int $nomor_urut
 * @property int $sub_nomor
 * @property string|null $nomor_lengkap
 * @property string $kode_klasifikasi
 * @property string $perihal
 * @property string|null $tujuan_surat
 * @property string|null $nama_peminta
 * @property \Illuminate\Support\Carbon $tanggal_surat
 * @property \Illuminate\Support\Carbon $tanggal_terbit
 * @property string $status
 * @property string|null $alasan_batal
 * @property string $dibuat_oleh
 * @property string|null $dibatalkan_oleh
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $batal_pada
 * @property-read \App\Models\Bidang|null $bidang
 * @property-read \App\Models\FormatNomorSurat $format
 * @property-read \App\Models\Opd $opd
 * @property-read \App\Models\User|null $pembatal
 * @property-read \App\Models\User $pembuat
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat acrossOpd()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereAlasanBatal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereBatalPada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereBidangId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereDibatalkanOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereDibuatOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereFormatNomorSuratId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereKodeKlasifikasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereNamaPeminta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereNomorLengkap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereNomorUrut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereOpdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat wherePerihal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereSubNomor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereTahun($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereTanggalSurat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereTanggalTerbit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereTujuanSurat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NomorSurat whereUpdatedAt($value)
 */
	class NomorSurat extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $kode_opd
 * @property string $nama_opd
 * @property string|null $alamat_opd
 * @property string|null $logo
 * @property bool $aktif
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Bidang> $bidang
 * @property-read int|null $bidang_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DisplayDevice> $displayDevices
 * @property-read int|null $display_devices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FormatNomorSurat> $formatNomorSurat
 * @property-read int|null $format_nomor_surat_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kunjungan> $kunjungan
 * @property-read int|null $kunjungan_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NomorSurat> $nomorSurat
 * @property-read int|null $nomor_surat_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd whereAktif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd whereAlamatOpd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd whereKodeOpd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd whereNamaOpd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opd whereUpdatedAt($value)
 */
	class Opd extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string|null $opd_id
 * @property string $name
 * @property string|null $nip
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property string|null $sso_subject_id
 * @property bool $aktif
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Opd|null $opd
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAktif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNip($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOpdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSsoSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent implements \Filament\Models\Contracts\FilamentUser {}
}

