<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Food'],
            ['name' => 'Transportation'],
            ['name' => 'Bills'],
            ['name' => 'Entertainment'],
            ['name' => 'Health'],
            ['name' => 'Education'],
        ];

        foreach ($categories as $category) {
            // Check if category already exists
            $exists = DB::table('categories')
                ->where('name', $category['name'])
                ->exists();

            if (!$exists) {
        DB::table('categories')->insert([
                    'name' => $category['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
        ]);
            }
        }
    }
}
