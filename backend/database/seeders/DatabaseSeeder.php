<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RegionSeeder::class);
        $this->call(HouseholdSeeder::class);
        $this->call(DeviceSeeder::class);
        $this->call(UserTableSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(MeasurementSeeder::class);
    }
}
