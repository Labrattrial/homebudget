<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

class TransactionsSeeder extends Seeder
{
    public function run()
    {
        $categories = DB::table('categories')->get();
        
        // Get all existing users
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->info('No users found. Please create a user account first.');
            return;
        }
        
        foreach ($users as $user) {
            // Generate transactions for the last 90 days for each user
            for ($i = 0; $i < 90; $i++) {
                $date = Carbon::now()->subDays($i);
                
                // Generate 1-3 transactions per day
                $transactionsPerDay = rand(1, 3);
                
                for ($j = 0; $j < $transactionsPerDay; $j++) {
                    $category = $categories->random();
                    $amount = rand(100, 5000); // Random amount between 100 and 5000
                    
                    DB::table('transactions')->insert([
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'amount' => $amount,
                        'date' => $date->format('Y-m-d'),
                        'description' => "Transaction for {$category->name}",
                        'type' => 'expense',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
} 