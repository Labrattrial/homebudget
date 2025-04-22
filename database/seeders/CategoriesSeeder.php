<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        DB::table('categories')->insert([
            ['name' => 'Groceries'],
            ['name' => 'Transportation'],
            ['name' => 'Bills'],
            ['name' => 'Entertainment'],
            ['name' => 'Health'],
            ['name' => 'Education'],
        ]);
    }
}

