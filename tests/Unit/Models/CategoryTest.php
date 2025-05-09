<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $category;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create([
            'name' => 'Food',
            'icon' => 'utensils'
        ]);
    }

    public function test_category_has_expenses_relationship()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food'
        ]);

        $this->assertCount(1, $this->category->expenses);
        $this->assertInstanceOf(Expense::class, $this->category->expenses->first());
    }

    public function test_category_has_total_expenses_method()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food',
            'amount' => 100
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food',
            'amount' => 200
        ]);

        $this->assertEquals(300, $this->category->totalExpenses());
    }

    public function test_category_has_average_expense_method()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food',
            'amount' => 100
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food',
            'amount' => 200
        ]);

        $this->assertEquals(150, $this->category->averageExpense());
    }

    public function test_category_has_expense_count_method()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food'
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food'
        ]);

        $this->assertEquals(2, $this->category->expenseCount());
    }

    public function test_category_has_most_common_description_method()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food',
            'description' => 'Grocery shopping'
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food',
            'description' => 'Grocery shopping'
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food',
            'description' => 'Restaurant'
        ]);

        $this->assertEquals('Grocery shopping', $this->category->mostCommonDescription());
    }

    public function test_category_has_unique_descriptions_method()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food',
            'description' => 'Grocery shopping'
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'Food',
            'description' => 'Restaurant'
        ]);

        $descriptions = $this->category->uniqueDescriptions();
        $this->assertCount(2, $descriptions);
        $this->assertContains('Grocery shopping', $descriptions);
        $this->assertContains('Restaurant', $descriptions);
    }
} 