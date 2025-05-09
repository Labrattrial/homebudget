<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $transaction;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100,
            'type' => 'expense',
            'description' => 'Test transaction'
        ]);
    }

    public function test_transaction_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->transaction->user);
        $this->assertEquals($this->user->id, $this->transaction->user->id);
    }

    public function test_transaction_has_type_attribute()
    {
        $this->assertEquals('expense', $this->transaction->type);
    }

    public function test_transaction_has_amount_attribute()
    {
        $this->assertEquals(100, $this->transaction->amount);
    }

    public function test_transaction_has_description_attribute()
    {
        $this->assertEquals('Test transaction', $this->transaction->description);
    }

    public function test_transaction_has_date_attribute()
    {
        $this->assertNotNull($this->transaction->date);
    }

    public function test_transaction_has_formatted_amount_method()
    {
        $this->user->update(['currency' => 'USD']);
        $this->assertEquals('$100.00', $this->transaction->formattedAmount());

        $this->user->update(['currency' => 'EUR']);
        $this->assertEquals('€100.00', $this->transaction->fresh()->formattedAmount());
    }

    public function test_transaction_has_formatted_date_method()
    {
        $this->assertIsString($this->transaction->formattedDate());
    }

    public function test_transaction_has_is_expense_method()
    {
        $this->assertTrue($this->transaction->isExpense());

        $income = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income'
        ]);

        $this->assertFalse($income->isExpense());
    }

    public function test_transaction_has_is_income_method()
    {
        $this->assertFalse($this->transaction->isIncome());

        $income = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income'
        ]);

        $this->assertTrue($income->isIncome());
    }

    public function test_transaction_has_scope_for_user()
    {
        $otherUser = User::factory()->create();
        Transaction::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $this->assertCount(1, Transaction::forUser($this->user)->get());
    }

    public function test_transaction_has_scope_for_type()
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'income'
        ]);

        $this->assertCount(1, Transaction::forType('expense')->get());
        $this->assertCount(1, Transaction::forType('income')->get());
    }
} 