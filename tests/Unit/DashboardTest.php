<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\MainDashboardController;
use App\Models\User;
use App\Models\Expense;
use App\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->controller = new MainDashboardController();
    }

    public function test_show_dashboard_returns_correct_view()
    {
        Auth::login($this->user);
        $response = $this->get(route('user.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.dashboard');
    }

    public function test_get_dashboard_data_returns_correct_data()
    {
        Auth::login($this->user);
        $response = $this->get(route('user.dashboard.data'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'totalExpenses',
            'budgetStatus',
            'recentTransactions',
            'categorySummary'
        ]);
    }

    public function test_get_budget_status_returns_correct_data()
    {
        Auth::login($this->user);
        $response = $this->get(route('budget.status'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message'
        ]);
    }

    public function test_get_category_summary_returns_correct_data()
    {
        Auth::login($this->user);
        $response = $this->get(route('dashboard.category-summary'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'categories',
            'totals'
        ]);
    }

    public function test_save_budgets_saves_correctly()
    {
        Auth::login($this->user);
        $budgetData = [
            'food' => 500,
            'transport' => 200,
            'entertainment' => 300
        ];

        $response = $this->post(route('saveBudgets'), $budgetData);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 500
        ]);
    }

    public function test_get_spending_trend_returns_correct_data()
    {
        Auth::login($this->user);
        $response = $this->get(route('spending.trend'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'labels',
            'data'
        ]);
    }
}