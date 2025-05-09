<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $budget;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 500
        ]);
    }

    public function test_budget_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->budget->user);
        $this->assertEquals($this->user->id, $this->budget->user->id);
    }

    public function test_budget_has_category_expenses_relationship()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 100
        ]);

        $this->assertCount(1, $this->budget->categoryExpenses);
        $this->assertEquals(100, $this->budget->categoryExpenses->sum('amount'));
    }

    public function test_budget_has_remaining_amount_method()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 200
        ]);

        $this->assertEquals(300, $this->budget->remainingAmount());
    }

    public function test_budget_has_spent_amount_method()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 200
        ]);

        $this->assertEquals(200, $this->budget->spentAmount());
    }

    public function test_budget_has_percentage_used_method()
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 250
        ]);

        $this->assertEquals(50, $this->budget->percentageUsed());
    }

    public function test_budget_has_status_method()
    {
        // Test under budget
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 200
        ]);
        $this->assertEquals('under', $this->budget->status());

        // Test over budget
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 400
        ]);
        $this->assertEquals('over', $this->budget->fresh()->status());
    }

    public function test_budget_has_warning_threshold()
    {
        $this->assertEquals(80, $this->budget->warningThreshold());
    }

    public function test_budget_has_is_near_limit_method()
    {
        // Test not near limit
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 200
        ]);
        $this->assertFalse($this->budget->isNearLimit());

        // Test near limit
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 200
        ]);
        $this->assertTrue($this->budget->fresh()->isNearLimit());
    }
} 