<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        DB::table('categories')->insert([
            ['id' => 1, 'name' => 'Food'],
            ['id' => 2, 'name' => 'Transportation'],
            ['id' => 3, 'name' => 'Bills'],
            ['id' => 4, 'name' => 'Entertainment'],
            ['id' => 5, 'name' => 'Health'],
            ['id' => 6, 'name' => 'Education'],
        ]);
    }
}
