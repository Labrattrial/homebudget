<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\UserExpensesController;
use App\Models\User;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class ExpensesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->controller = new UserExpensesController();
    }

    public function test_index_returns_correct_view()
    {
        Auth::login($this->user);
        $response = $this->get(route('user.expenses'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.expenses');
    }

    public function test_store_expense_creates_new_expense()
    {
        Auth::login($this->user);
        $expenseData = [
            'amount' => 100.50,
            'category' => 'food',
            'description' => 'Grocery shopping',
            'date' => now()->format('Y-m-d')
        ];

        $response = $this->post(route('user.expenses.store'), $expenseData);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $this->user->id,
            'amount' => 100.50,
            'category' => 'food',
            'description' => 'Grocery shopping'
        ]);
    }

    public function test_update_expense_updates_existing_expense()
    {
        Auth::login($this->user);
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100,
            'category' => 'food',
            'description' => 'Old description'
        ]);

        $updateData = [
            'amount' => 150,
            'category' => 'food',
            'description' => 'Updated description',
            'date' => now()->format('Y-m-d')
        ];

        $response = $this->put("/expenses/{$expense->id}", $updateData);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'amount' => 150,
            'description' => 'Updated description'
        ]);
    }

    public function test_destroy_expense_deletes_expense()
    {
        Auth::login($this->user);
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->delete("/expenses/{$expense->id}");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id
        ]);
    }

    public function test_get_descriptions_by_category_returns_correct_data()
    {
        Auth::login($this->user);
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'description' => 'Grocery shopping'
        ]);

        $response = $this->get(route('user.expenses.descriptions', ['category' => 'food']));
        $response->assertStatus(200);
        $response->assertJsonStructure(['descriptions']);
        $response->assertJson(['descriptions' => ['Grocery shopping']]);
    }
} 