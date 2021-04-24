<?php

namespace Database\Seeders;

use App\Models\Status;
use App\Models\Type;
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
        Type::create([
            'name' => 'file'
        ]);
        Type::create([
            'name' => 'directory'
        ]);
        Status::create([
            'name' => 'identified'
        ]);
        Status::create([
            'name' => 'copied'
        ]);
        Status::create([
            'name' => 'deleted'
        ]);
    }
}
