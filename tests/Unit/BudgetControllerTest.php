<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\BudgetController;
use App\Models\User;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->controller = new BudgetController();
    }

    public function test_index_returns_correct_view()
    {
        Auth::login($this->user);
        $response = $this->get(route('budget.index'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.budget');
    }

    public function test_store_creates_new_budget()
    {
        Auth::login($this->user);
        $budgetData = [
            'category' => 'food',
            'amount' => 500,
            'period' => 'monthly'
        ];

        $response = $this->post(route('budget.store'), $budgetData);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 500,
            'period' => 'monthly'
        ]);
    }

    public function test_update_modifies_existing_budget()
    {
        Auth::login($this->user);
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 500
        ]);

        $updateData = [
            'amount' => 600,
            'period' => 'weekly'
        ];

        $response = $this->put(route('budget.update', $budget), $updateData);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'amount' => 600,
            'period' => 'weekly'
        ]);
    }

    public function test_destroy_deletes_budget()
    {
        Auth::login($this->user);
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->delete(route('budget.destroy', $budget));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('budgets', [
            'id' => $budget->id
        ]);
    }

    public function test_get_budget_status_returns_correct_data()
    {
        Auth::login($this->user);
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 500
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 300
        ]);

        $response = $this->get(route('budget.status', $budget));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'remaining',
            'spent',
            'percentage'
        ]);
    }

    public function test_get_budget_summary_returns_correct_data()
    {
        Auth::login($this->user);
        Budget::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 500
        ]);

        Budget::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'transport',
            'amount' => 300
        ]);

        $response = $this->get(route('budget.summary'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_budget',
            'total_spent',
            'remaining',
            'categories'
        ]);
    }

    public function test_store_validates_required_fields()
    {
        Auth::login($this->user);
        $response = $this->post(route('budget.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category', 'amount', 'period']);
    }

    public function test_update_validates_required_fields()
    {
        Auth::login($this->user);
        $budget = Budget::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->put(route('budget.update', $budget), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount', 'period']);
    }

    public function test_cannot_access_other_users_budget()
    {
        Auth::login($this->user);
        $otherUser = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->get(route('budget.show', $budget));
        $response->assertStatus(403);
    }
} 