<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            ZonaTiendaSeeder::class,
            CampanaSeeder::class,
            UserSeeder::class,
            ClienteCarteraSeeder::class,
        ]);
    }
}