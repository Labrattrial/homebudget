<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Requests\RegisterRequest;

class RegisterRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $rules;

    public function setUp(): void
    {
        parent::setUp();
        $this->rules = (new RegisterRequest())->rules();
    }

    public function test_register_validation_rules()
    {
        $this->assertArrayHasKey('name', $this->rules);
        $this->assertArrayHasKey('email', $this->rules);
        $this->assertArrayHasKey('password', $this->rules);
        $this->assertArrayHasKey('password_confirmation', $this->rules);
        
        $this->assertStringContainsString('required', $this->rules['name']);
        $this->assertStringContainsString('required', $this->rules['email']);
        $this->assertStringContainsString('email', $this->rules['email']);
        $this->assertStringContainsString('unique', $this->rules['email']);
        $this->assertStringContainsString('required', $this->rules['password']);
        $this->assertStringContainsString('min:8', $this->rules['password']);
        $this->assertStringContainsString('confirmed', $this->rules['password']);
    }

    public function test_register_validation_fails_with_invalid_email()
    {
        $validator = $this->app['validator']->make([
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_register_validation_fails_with_existing_email()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $validator = $this->app['validator']->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_register_validation_fails_with_short_password()
    {
        $validator = $this->app['validator']->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short'
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_register_validation_fails_with_mismatched_passwords()
    {
        $validator = $this->app['validator']->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123'
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_register_validation_passes_with_valid_data()
    {
        $validator = $this->app['validator']->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ], $this->rules);

        $this->assertFalse($validator->fails());
    }
} 