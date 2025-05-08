<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Contracts\Auth\Authenticatable;

class ExpensesCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @var User|Authenticatable */
    protected User $user;
    
    /** @var \Illuminate\Database\Eloquent\Collection<int, Category> */
    protected $categories;
    
    /** @var Transaction */
    protected $expense;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->categories = Category::factory()->count(5)->create();
        $this->expense = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categories->first()->id,
            'amount' => 1000.00,
            'date' => '2025-04-22'
        ]);
    }

    /**
     * Test ID: TC_17
     * Description: Verifying if user can add expense with valid inputs
     * Pre-Condition: User logged in, on expense page at recent expenses tab
     * Steps: 
     *  1. Click the add button
     *  2. Select category
     *  3. Set total expense
     *  4. Set Date
     * Test Data: 
     *  Category: Bills
     *  Total Expense: 1,000
     *  Date: 4/22/2025
     */
    public function test_add_expense_with_valid_inputs()
    {
        $category = $this->categories->first();
        $testData = [
            'category_id' => $category->id,
            'amount' => 1000.00,
            'date' => '2025-04-22'
        ];
        
        $response = $this->post('/expenses', $testData);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'amount' => 1000.00,
            'date' => '2025-04-22'
        ]);
        
        // Verify the response contains the new expense data
        $response->assertJsonFragment([
            'category_id' => $category->id,
            'amount' => 1000.00,
            'date' => '2025-04-22'
        ]);
    }

    /**
     * Test ID: TC_18
     * Description: Verifying if user can delete expenses
     * Pre-Condition: User logged in, on expense page at recent expenses tab, has existing expense
     * Steps: 
     *  1. Click the Delete button
     * Test Data: 
     *  Category: Bills
     *  Total Expense: 1,000
     *  Date: 4/22/2025
     */
    public function test_delete_expense()
    {
        $response = $this->delete("/expenses/{$this->expense->id}");
        
        $response->assertStatus(200);
        $this->assertDatabaseMissing('transactions', ['id' => $this->expense->id]);
        
        // Verify the response contains success message
        $response->assertJson(['success' => true]);
    }

    /**
     * Test ID: TC_19
     * Description: Verifying if user can update expenses with valid input
     * Pre-Condition: User logged in, on expense page at recent expenses tab, has existing expense
     * Steps: 
     *  1. Click the Edit Button
     * Test Data: 
     *  Original:
     *    Category: Bills
     *    Total Expense: 1,000
     *    Date: 4/22/2025
     *  Updated:
     *    Category: Bills
     *    Total Expense: 2,000
     *    Date: 4/25/2025
     */
    public function test_update_expense_with_valid_input()
    {
        $updatedData = [
            'category_id' => $this->categories->first()->id,
            'amount' => 2000.00,
            'date' => '2025-04-25',
            '_method' => 'PUT'
        ];
        
        $response = $this->post("/expenses/{$this->expense->id}", $updatedData);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('transactions', [
            'id' => $this->expense->id,
            'amount' => 2000.00,
            'date' => '2025-04-25'
        ]);
        
        // Verify the response contains the updated data
        $response->assertJsonFragment([
            'amount' => 2000.00,
            'date' => '2025-04-25'
        ]);
    }

    /**
     * Test ID: TC_20
     * Description: Verifying expense creation with invalid amount
     * Pre-Condition: User logged in, on expense page
     * Steps:
     *  1. Click add button
     *  2. Enter invalid amount
     *  3. Submit form
     * Test Data:
     *  Category: Bills
     *  Total Expense: -100
     *  Date: 4/22/2025
     */
    public function test_add_expense_with_invalid_amount()
    {
        $testData = [
            'category_id' => $this->categories->first()->id,
            'amount' => -100,
            'date' => '2025-04-22'
        ];
        
        $response = $this->post('/expenses', $testData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    /**
     * Test ID: TC_21
     * Description: Verifying expense creation with missing category
     * Pre-Condition: User logged in, on expense page
     * Steps:
     *  1. Click add button
     *  2. Leave category empty
     *  3. Submit form
     * Test Data:
     *  Category: (empty)
     *  Total Expense: 1000
     *  Date: 4/22/2025
     */
    public function test_add_expense_with_missing_category()
    {
        $testData = [
            'category_id' => '',
            'amount' => 1000,
            'date' => '2025-04-22'
        ];
        
        $response = $this->post('/expenses', $testData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    /**
     * Test ID: TC_22
     * Description: Verifying expense creation with future date
     * Pre-Condition: User logged in, on expense page
     * Steps:
     *  1. Click add button
     *  2. Enter future date
     *  3. Submit form
     * Test Data:
     *  Category: Bills
     *  Total Expense: 1000
     *  Date: (future date)
     */
    public function test_add_expense_with_future_date()
    {
        $futureDate = now()->addYear()->format('Y-m-d');
        $testData = [
            'category_id' => $this->categories->first()->id,
            'amount' => 1000,
            'date' => $futureDate
        ];
        
        $response = $this->post('/expenses', $testData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date']);
    }

    /**
     * Test ID: TC_23
     * Description: Verifying category breakdown chart displays correctly
     * Pre-Condition: User logged in, has expenses
     * Steps:
     *  1. Navigate to category breakdown tab
     * Test Data: None
     */
    public function test_category_breakdown_display()
    {
        $response = $this->get('/expenses');
        
        $response->assertStatus(200);
        $response->assertSee('Category Breakdown');
        $response->assertSee('id="categoryBreakdownChart"', false);
        $response->assertSee($this->categories->first()->name, false);
    }

    /**
     * Test ID: TC_24
     * Description: Verifying unauthorized user cannot access expenses
     * Pre-Condition: User not logged in
     * Steps:
     *  1. Try to access expenses page
     * Test Data: None
     */
    public function test_unauthorized_access_to_expenses()
    {
        $this->post('/logout');
        
        $response = $this->get('/expenses');
        $response->assertRedirect('/login');
    }
}