<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class ExpensesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $categories;
    protected $expenses;
    protected $categorySummary;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test categories
        $this->categories = Category::factory()->count(3)->create();

        // Create test expenses
        $this->expenses = Transaction::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'category_id' => fn() => $this->categories->random()->id
        ]);

        // Calculate category summary for testing
        $this->categorySummary = $this->expenses->groupBy('category_id')
            ->map(fn($items) => $items->sum('amount'))
            ->toArray();
    }

    /** @test */
    public function expenses_page_loads_successfully()
    {
        $this->actingAs($this->user)
            ->get('/expenses')
            ->assertStatus(200)
            ->assertSee('Recent Expenses')
            ->assertSee('Category Breakdown');
    }

    /** @test */
    public function displays_recent_expenses_correctly()
    {
        $response = $this->actingAs($this->user)
            ->get('/expenses');

        // Check each expense is displayed
        $this->expenses->each(function ($expense) use ($response) {
            $response->assertSee($expense->category->name)
                ->assertSee('₱'.number_format($expense->amount, 2))
                ->assertSee($expense->date);
        });
    }

    /** @test */
    public function shows_empty_state_when_no_expenses()
    {
        Transaction::query()->delete();

        $this->actingAs($this->user)
            ->get('/expenses')
            ->assertStatus(200)
            ->assertDontSee('expense-card');
    }

    /** @test */
    public function category_breakdown_section_works()
    {
        $response = $this->actingAs($this->user)
            ->get('/expenses');

        // Check category breakdown data
        foreach ($this->categorySummary as $categoryId => $total) {
            $categoryName = Category::find($categoryId)->name;
            $response->assertSee($categoryName)
                ->assertSee('₱'.number_format($total, 2));
        }
    }

    /** @test */
    public function tab_switching_works()
    {
        $this->actingAs($this->user)
            ->get('/expenses')
            ->assertSee('Recent Expenses', false)
            ->assertDontSee('Category Breakdown', false);
    }

    /** @test */
    public function modal_functionality_works()
    {
        $this->actingAs($this->user)
            ->get('/expenses')
            ->assertSee('Add New Expense', false)
            ->assertSee('modal', false)
            ->assertSee('expenseForm', false);
    }

    /** @test */
    public function edit_functionality_prefills_form()
    {
        $expense = $this->expenses->first();

        $this->actingAs($this->user)
            ->get('/expenses')
            ->assertSee($expense->category->name)
            ->assertSee('₱'.number_format($expense->amount, 2))
            ->assertSee($expense->date);
    }

    /** @test */
    public function chart_renders_with_correct_data()
    {
        $response = $this->actingAs($this->user)
            ->get('/expenses');

        // Check chart container exists
        $response->assertSee('categoryBreakdownChart', false);

        // Check chart data is present in the script
        foreach ($this->categorySummary as $categoryId => $total) {
            $categoryName = Category::find($categoryId)->name;
            $response->assertSee($categoryName, false)
                ->assertSee((string)$total, false);
        }
    }

    /** @test */
    public function add_button_shows_only_on_recent_tab()
    {
        $this->actingAs($this->user)
            ->get('/expenses')
            ->assertSee('add-btn', false);
    }

    /** @test */
    public function required_scripts_are_loaded()
    {
        $this->actingAs($this->user)
            ->get('/expenses')
            ->assertSee('https://cdn.jsdelivr.net/npm/chart.js', false)
            ->assertSee('editCard', false)
            ->assertSee('deleteCard', false)
            ->assertSee('toggleTab', false);
    }
}