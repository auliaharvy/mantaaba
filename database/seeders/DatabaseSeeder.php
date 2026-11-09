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
        $this->call([
            OrganizationSeeder::class,
            DivisionSeeder::class,
            PositionSeeder::class,
            UserSeeder::class,
            MemberSeeder::class,
            ContentTaxonomySeeder::class,
            BlogSeeder::class,
            ResearchSeeder::class,
            ThreadSeeder::class,
            CommentSeeder::class,
            EventSeeder::class,
            ParticipantSeeder::class,
            SettingSeeder::class
        ]);
    }
}
