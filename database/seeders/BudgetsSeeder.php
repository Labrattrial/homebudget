<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

class BudgetsSeeder extends Seeder
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
            $currentMonth = Carbon::now()->format('Y-m');
            
            // Check if user already has a budget for this month
            $existingBudget = DB::table('budgets')
                ->where('user_id', $user->id)
                ->where('month', $currentMonth)
                ->whereNull('category_id')
                ->first();
            
            if (!$existingBudget) {
                // Create a total monthly budget if it doesn't exist
                DB::table('budgets')->insert([
                    'user_id' => $user->id,
                    'category_id' => null, // This is the total budget
                    'amount_limit' => 50000, // ₱50,000 total monthly budget
                    'month' => $currentMonth,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Create category-specific budgets if they don't exist
            foreach ($categories as $category) {
                $existingCategoryBudget = DB::table('budgets')
                    ->where('user_id', $user->id)
                    ->where('category_id', $category->id)
                    ->where('month', $currentMonth)
                    ->first();
                
                if (!$existingCategoryBudget) {
                    DB::table('budgets')->insert([
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'amount_limit' => rand(5000, 15000), // Random budget between ₱5,000 and ₱15,000
                        'month' => $currentMonth,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
} 