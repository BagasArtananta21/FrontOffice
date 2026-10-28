<?php

namespace Tests\Feature\Seeding;

use App\Models\FormatNomorSurat;
use App\Models\User;
use Database\Seeders\FormatNomorSuratSeeder;
use Database\Seeders\OpdSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(OpdSeeder::class);
    }

    private function seederAnswering(?string $password): UserSeeder
    {
        $seeder = new UserSeeder();

        if ($password !== null) {
            $command = Mockery::mock(Command::class);
            $command->shouldReceive('secret')->andReturn($password);
            $seeder->setCommand($command);
        }

        return $seeder;
    }

    public function test_outside_local_password_shorter_than_12_characters_is_rejected(): void
    {
        try {
            $this->seederAnswering('abcdefghijk')->run(); 
            $this->fail('Seeder seharusnya menolak kata sandi 11 karakter.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('users', 0);
        }
    }

    public function test_outside_local_seeding_without_a_command_is_refused(): void
    {
        $this->expectException(RuntimeException::class);

        $this->seederAnswering(null)->run();
    }

    public function test_outside_local_super_admin_gets_typed_password_and_no_dummy_accounts(): void
    {
        $password = 'abcdefghijkl'; 

        $this->seederAnswering($password)->run();

        $superAdmin = User::where('email', 'superadmin@example.com')->firstOrFail();

        $this->assertFalse(Hash::check('password', $superAdmin->password));
        $this->assertTrue(Hash::check($password, $superAdmin->password));
        $this->assertSame('super_admin', $superAdmin->role);
        $this->assertNull($superAdmin->opd_id);
        $this->assertDatabaseMissing('users', ['email' => 'adminfo@example.com']);
    }

    public function test_outside_local_rerun_does_not_prompt_or_overwrite_existing_super_admin(): void
    {
        $first = 'abcdefghijkl';
        $this->seederAnswering($first)->run();

        $command = Mockery::mock(Command::class);
        $command->shouldNotReceive('secret');
        $seeder = new UserSeeder();
        $seeder->setCommand($command);
        $seeder->run();

        $hash = User::where('email', 'superadmin@example.com')->value('password');

        $this->assertTrue(Hash::check($first, $hash));
    }

    public function test_local_keeps_convenience_accounts(): void
    {
        $this->app['env'] = 'local';

        $this->seederAnswering(null)->run();

        $hash = User::where('email', 'superadmin@example.com')->value('password');

        $this->assertTrue(Hash::check('password', $hash));
        $this->assertDatabaseHas('users', ['email' => 'adminfo@example.com']);
    }

    public function test_format_seeder_uses_any_super_admin_not_a_fixed_email(): void
    {
        $creator = new User();
        $creator->forceFill([
            'name' => 'Pembuat',
            'email' => 'pembuat@example.com',
            'password' => 'abcdefghijkl',
            'role' => 'super_admin',
            'opd_id' => null,
            'aktif' => true,
        ])->save();

        $this->seed(FormatNomorSuratSeeder::class);

        $format = FormatNomorSurat::withoutGlobalScope('opd')->firstOrFail();

        $this->assertSame($creator->id, $format->dibuat_oleh);
    }

    public function test_format_seeder_explains_what_is_missing_when_there_is_no_super_admin(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('UserSeeder');

        $this->seed(FormatNomorSuratSeeder::class);
    }
}
