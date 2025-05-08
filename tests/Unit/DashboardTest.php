<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Auth\Authenticatable;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        /** @var User|Authenticatable $user */
        $this->user = User::factory()->create(['name' => 'Test User']);
    }

    /** @test */
    public function welcome_section_displays_correctly()
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $this->actingAs($user instanceof Authenticatable ? $user : null);
        
        $response = $this->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('Welcome, Test User!')
            ->assertSee('Here\'s a quick overview of your finances')
            ->assertSee('<i class="fas fa-coins fa-3x"></i>', false);
    }

    /** @test */
    public function net_worth_card_displays_correct_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user instanceof Authenticatable ? $user : null);
        
        Transaction::factory(2)->create([
            'user_id' => $user->id,
            'amount' => 1000,
            'date' => now()->subDays(10)
        ]);
        
        $response = $this->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('MY NET WORTH')
            ->assertSee('₱2,000.00')
            ->assertSee('Last 30 Days');
    }

    /** @test */
    public function budget_setting_functionality_works()
    {
        $user = User::factory()->create();
        $this->actingAs($user instanceof Authenticatable ? $user : null);
        
        $response = $this->post('/set-budget', ['budget' => 5000]);
        
        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'budget' => 5000
        ]);
    }

    /** @test */
    public function category_breakdown_displays_correct_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user instanceof Authenticatable ? $user : null);
        
        $foodCategory = Category::factory()->create(['name' => 'Food']);
        $transportCategory = Category::factory()->create(['name' => 'Transport']);
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $foodCategory->id,
            'amount' => 1500
        ]);
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $transportCategory->id,
            'amount' => 800
        ]);
        
        $response = $this->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('CATEGORY BREAKDOWN')
            ->assertSee('Food: ₱1,500.00')
            ->assertSee('Transport: ₱800.00')
            ->assertSee('<div class="fake-pie"></div>', false);
    }

    /** @test */
    public function category_breakdown_shows_message_when_no_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user instanceof Authenticatable ? $user : null);
        
        $response = $this->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('No category data available.');
    }

    /** @test */
    public function monthly_expenses_displays_correct_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user instanceof Authenticatable ? $user : null);
        
        $foodCategory = Category::factory()->create(['name' => 'Food']);
        $transportCategory = Category::factory()->create(['name' => 'Transport']);
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $foodCategory->id,
            'amount' => 2000,
            'date' => now()->subDays(5)
        ]);
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $transportCategory->id,
            'amount' => 1000,
            'date' => now()->subDays(10)
        ]);
        
        $response = $this->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('MONTHLY EXPENSES')
            ->assertSee('<div class="fake-bar-graph">', false)
            ->assertSee('<div class="bar-container">', false);
    }

    /** @test */
    public function monthly_expenses_shows_message_when_no_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user instanceof Authenticatable ? $user : null);
        
        $response = $this->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('No expenses recorded for this month.');
    }

    /** @test */
    public function notifications_show_when_budget_exceeded()
    {
        $user = User::factory()->create(['budget' => 1000]);
        $this->actingAs($user instanceof Authenticatable ? $user : null);
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 1100
        ]);
        
        $response = $this->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('You have exceeded your budget!', false)
            ->assertSee('notification error', false);
    }

    /** @test */
    public function notifications_show_when_near_budget_limit()
    {
        $user = User::factory()->create(['budget' => 1000]);
        $this->actingAs($user instanceof Authenticatable ? $user : null);
        
        Transaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 850
        ]);
        
        $response = $this->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('You are near your budget limit.', false)
            ->assertSee('notification warning', false);
    }

    /** @test */
    public function javascript_functionality_is_present()
    {
        $user = User::factory()->create();
        $this->actingAs($user instanceof Authenticatable ? $user : null);
        
        $response = $this->get('/dashboard');
        
        $response->assertStatus(200)
            ->assertSee('document.addEventListener', false)
            ->assertSee('setBudgetBtn.addEventListener', false)
            ->assertSee('checkBudget', false)
            ->assertSee('showNotification', false);
    }
}