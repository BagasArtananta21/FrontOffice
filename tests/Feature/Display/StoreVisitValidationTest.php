<?php

namespace Tests\Feature\Display;

use App\Models\Kunjungan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InteractsWithDisplay;
use Tests\TestCase;

class StoreVisitValidationTest extends TestCase
{
    use InteractsWithDisplay, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDisplay();
        $this->showForm();
    }

    private function assertRejected(): void
    {
        $this->assertSame(0, DB::table('kunjungan')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertTrue((bool) $this->deviceRow()['tampilkan_form']);
    }

    public static function requiredFields(): array
    {
        return [
            'name' => ['nama_tamu'],
            'gender' => ['jenis_kelamin'],
            'purpose' => ['keperluan'],
            'signature' => ['tanda_tangan'],
        ];
    }

    #[DataProvider('requiredFields')]
    public function test_it_requires_the_field_and_keeps_the_form_open(string $field): void
    {
        $payload = $this->visitPayload();
        unset($payload[$field]);

        $this->asDisplay()->post(route('display.kunjungan.store'), $payload)->assertSessionHasErrors($field);

        $this->assertRejected();
    }

    #[DataProvider('requiredFields')]
    public function test_it_treats_an_empty_string_as_missing(string $field): void
    {
        $this->submitVisit([$field => ''])->assertSessionHasErrors($field);

        $this->assertRejected();
    }

    public function test_it_treats_a_whitespace_only_name_as_missing(): void
    {
        $this->submitVisit(['nama_tamu' => '     '])->assertSessionHasErrors('nama_tamu');

        $this->assertRejected();
    }

    public function test_optional_fields_may_all_be_left_out(): void
    {
        $this->submitVisit()->assertRedirect(route('display.confirmation'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kunjungan', [
            'nama_tamu' => 'Budi Santoso',
            'no_hp' => null,
            'instansi_asal' => null,
            'alamat' => null,
            'bidang_id' => null,
            'pegawai_tujuan' => null,
            'catatan_petugas' => null,
        ]);
    }

    public function test_optional_fields_may_be_sent_empty(): void
    {
        $this->submitVisit([
            'no_hp' => '',
            'instansi_asal' => '',
            'alamat' => '',
            'bidang_id' => '',
            'pegawai_tujuan' => '',
        ])->assertRedirect(route('display.confirmation'));

        $this->assertDatabaseHas('kunjungan', ['nama_tamu' => 'Budi Santoso', 'no_hp' => null, 'bidang_id' => null]);
    }

    public function test_it_stores_every_optional_field_that_is_given(): void
    {
        $bidangId = $this->insertBidang($this->opd->id, 'PKP');

        $this->submitVisit([
            'no_hp' => '081234567890',
            'instansi_asal' => 'SMA Negeri 1',
            'alamat' => 'Jalan Veteran 1',
            'bidang_id' => $bidangId,
            'pegawai_tujuan' => 'Pak Wayan',
        ])->assertRedirect(route('display.confirmation'));

        $this->assertDatabaseHas('kunjungan', [
            'no_hp' => '081234567890',
            'instansi_asal' => 'SMA Negeri 1',
            'alamat' => 'Jalan Veteran 1',
            'bidang_id' => $bidangId,
            'pegawai_tujuan' => 'Pak Wayan',
        ]);
    }

    public static function lengthLimits(): array
    {
        return [
            'name 256' => ['nama_tamu', 256, false],
            'name 255' => ['nama_tamu', 255, true],
            'purpose 1001' => ['keperluan', 1001, false],
            'purpose 1000' => ['keperluan', 1000, true],
            'institution 256' => ['instansi_asal', 256, false],
            'address 256' => ['alamat', 256, false],
            'address 255' => ['alamat', 255, true],
            'employee 256' => ['pegawai_tujuan', 256, false],
        ];
    }

    #[DataProvider('lengthLimits')]
    public function test_it_enforces_the_maximum_lengths(string $field, int $length, bool $accepted): void
    {
        $response = $this->submitVisit([$field => str_repeat('a', $length)]);

        if ($accepted) {
            $response->assertRedirect(route('display.confirmation'));
            $this->assertSame(1, DB::table('kunjungan')->count());
        } else {
            $response->assertSessionHasErrors($field);
            $this->assertRejected();
        }
    }

    public static function genders(): array
    {
        return [
            'male' => ['laki_laki', true],
            'female' => ['perempuan', true],
            'display label instead of key' => ['Laki-laki', false],
            'other value' => ['lainnya', false],
            'wrong case' => ['PEREMPUAN', false],
        ];
    }

    #[DataProvider('genders')]
    public function test_it_accepts_only_the_two_gender_keys(string $value, bool $accepted): void
    {
        $response = $this->submitVisit(['jenis_kelamin' => $value]);

        if ($accepted) {
            $response->assertRedirect(route('display.confirmation'));
            $this->assertDatabaseHas('kunjungan', ['jenis_kelamin' => $value]);
        } else {
            $response->assertSessionHasErrors('jenis_kelamin');
            $this->assertRejected();
        }
    }

    public static function acceptedPhones(): array
    {
        return [
            'plain' => ['081234567890', '081234567890'],
            'with dashes' => ['0812-3456-7890', '081234567890'],
            'with spaces' => ['0812 3456 7890', '081234567890'],
            'with dots' => ['0812.3456.7890', '081234567890'],
            'with brackets' => ['(0361) 123456', '0361123456'],
            'plus 62 prefix' => ['+62 812 3456 7890', '081234567890'],
            '62 prefix' => ['6281234567890', '081234567890'],
            '+62 prefix without spaces' => ['+6281234567890', '081234567890'],
            'shortest allowed (0 + 8 digits)' => ['012345678', '012345678'],
            'longest allowed (0 + 13 digits)' => ['01234567890123', '01234567890123'],
            'only spaces become empty' => ['   ', null],
        ];
    }

    #[DataProvider('acceptedPhones')]
    public function test_it_normalizes_and_stores_a_valid_phone_number(string $input, ?string $expected): void
    {
        $this->submitVisit(['no_hp' => $input])->assertRedirect(route('display.confirmation'));

        $this->assertSame($expected, DB::table('kunjungan')->value('no_hp'));
    }

    public static function rejectedPhones(): array
    {
        return [
            'letters inside' => ['08123abc4567'],
            'only letters' => ['abcdefghij'],
            'too short (0 + 7 digits)' => ['01234567'],
            'too long (0 + 14 digits)' => ['012345678901234'],
            'does not start with zero' => ['812345678901'],
            'foreign country code' => ['+1 555 123 4567'],
            'symbol that is not removed' => ['0812#3456789'],
            'slash' => ['0812/3456789'],
        ];
    }

    #[DataProvider('rejectedPhones')]
    public function test_it_rejects_an_invalid_phone_number(string $input): void
    {
        $this->submitVisit(['no_hp' => $input])->assertSessionHasErrors('no_hp');

        $this->assertRejected();
    }

    public static function normalizedPhones(): array
    {
        return [
            'null' => [null, null],
            'empty' => ['', null],
            'spaces only' => ['  ', null],
            'dashes and spaces' => ['0812-3456 7890', '081234567890'],
            'brackets and dots' => ['(0361).123.456', '0361123456'],
            '+62' => ['+62812345678', '0812345678'],
            '62' => ['62812345678', '0812345678'],
            'letters are kept so the pattern can reject them' => ['08ab1', '08ab1'],
            '62 only at the start' => ['0812621234', '0812621234'],
        ];
    }

    #[DataProvider('normalizedPhones')]
    public function test_normalize_phone(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, Kunjungan::normalizePhone($input));
    }

    public static function pattern(): array
    {
        return [
            ['012345678', 1],
            ['01234567890123', 1],
            ['01234567', 0],
            ['012345678901234', 0],
            ['812345678', 0],
            ['0123456a89', 0],
        ];
    }

    #[DataProvider('pattern')]
    public function test_no_hp_pattern(string $value, int $expected): void
    {
        $this->assertSame($expected, preg_match(Kunjungan::NO_HP_PATTERN, $value));
    }

    public static function badSignatures(): array
    {
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return [
            'jpeg data url' => ['data:image/jpeg;base64,'.base64_encode($pngBytes)],
            'svg data url' => ['data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg"/>')],
            'png header but gif bytes' => ['data:image/png;base64,'.base64_encode("GIF89a\x01\x00\x01\x00")],
            'png header but text bytes' => ['data:image/png;base64,'.base64_encode('<?php echo 1;')],
            'png header but not base64' => ['data:image/png;base64,@@@@!!!!'],
            'png header with no data' => ['data:image/png;base64,'],
            'bare base64 without the data url prefix' => [base64_encode($pngBytes)],
            'plain text' => ['tanda tangan saya'],
            'uppercase prefix' => ['DATA:IMAGE/PNG;BASE64,'.base64_encode($pngBytes)],
            'prefix without base64 marker' => ['data:image/png,'.rawurlencode($pngBytes)],
        ];
    }

    #[DataProvider('badSignatures')]
    public function test_it_rejects_an_invalid_signature_image(string $signature): void
    {
        $this->submitVisit(['tanda_tangan' => $signature])->assertSessionHasErrors('tanda_tangan');

        $this->assertRejected();
    }

    public function test_it_rejects_a_signature_array_instead_of_a_string(): void
    {
        $this->submitVisit(['tanda_tangan' => [self::PNG]])->assertSessionHasErrors('tanda_tangan');

        $this->assertRejected();
    }

    public function test_it_rejects_an_oversized_signature_and_accepts_a_large_one_under_the_limit(): void
    {
        $large = fn (int $base64Length) => 'data:image/png;base64,'.base64_encode("\x89PNG\r\n\x1a\n".str_repeat('x', intdiv($base64Length * 3, 4) - 8));

        $this->submitVisit(['tanda_tangan' => $large(500_020)])->assertSessionHasErrors('tanda_tangan');
        $this->assertRejected();

        $this->submitVisit(['tanda_tangan' => $large(499_900)])->assertRedirect(route('display.confirmation'));
        $this->assertSame(1, DB::table('kunjungan')->count());
    }

    public function test_it_rejects_a_bidang_that_does_not_exist(): void
    {
        $this->submitVisit(['bidang_id' => $this->newId()])->assertSessionHasErrors('bidang_id');

        $this->assertRejected();
    }

    public function test_errors_are_shown_in_indonesian_with_the_field_label(): void
    {
        $this->submitVisit(['nama_tamu' => ''])->assertSessionHasErrors(['nama_tamu' => 'Nama wajib diisi.']);
    }
}
