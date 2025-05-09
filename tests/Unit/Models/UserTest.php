<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Expense;
use App\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_has_expenses_relationship()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->assertInstanceOf(Expense::class, $this->user->expenses->first());
        $this->assertCount(1, $this->user->expenses);
    }

    public function test_user_has_budgets_relationship()
    {
        Budget::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->assertInstanceOf(Budget::class, $this->user->budgets->first());
        $this->assertCount(1, $this->user->budgets);
    }

    public function test_user_has_default_currency()
    {
        $this->assertEquals('USD', $this->user->currency);
    }

    public function test_user_can_update_currency()
    {
        $this->user->update(['currency' => 'EUR']);
        $this->assertEquals('EUR', $this->user->fresh()->currency);
    }

    public function test_user_has_total_expenses_method()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 200
        ]);

        $this->assertEquals(300, $this->user->totalExpenses());
    }

    public function test_user_has_total_budget_method()
    {
        Budget::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 500
        ]);

        Budget::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 300
        ]);

        $this->assertEquals(800, $this->user->totalBudget());
    }

    public function test_user_has_budget_status_method()
    {
        Budget::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 500
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 300
        ]);

        $this->assertEquals('under', $this->user->budgetStatus());
    }

    public function test_user_has_category_totals_method()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 100
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 200
        ]);

        $totals = $this->user->categoryTotals();
        $this->assertEquals(300, $totals['food']);
    }
} 