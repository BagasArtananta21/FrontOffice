<?php

namespace Database\Seeders;

use App;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
                OpdSeeder::class,
                UserSeeder::class,
                BidangSeeder::class,
                DisplayDeviceSeeder::class,
                FormatNomorSuratSeeder::class,
            ]);

        if(app()->isLocal()) {
            $this->call([
                NomorSuratSeeder::class,
                KunjunganSeeder::class,
                DinkesSeeder::class,
            ]);
        }

    }

    
}

