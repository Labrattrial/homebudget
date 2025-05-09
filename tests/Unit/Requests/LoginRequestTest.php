<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;

class LoginRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $rules;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);
        $this->rules = (new LoginRequest())->rules();
    }

    public function test_login_validation_rules()
    {
        $this->assertArrayHasKey('email', $this->rules);
        $this->assertArrayHasKey('password', $this->rules);
        
        $this->assertStringContainsString('required', $this->rules['email']);
        $this->assertStringContainsString('email', $this->rules['email']);
        $this->assertStringContainsString('required', $this->rules['password']);
    }

    public function test_login_validation_fails_with_invalid_email()
    {
        $validator = $this->app['validator']->make([
            'email' => 'not-an-email',
            'password' => 'password123'
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_login_validation_fails_with_empty_password()
    {
        $validator = $this->app['validator']->make([
            'email' => 'test@example.com',
            'password' => ''
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_login_validation_passes_with_valid_credentials()
    {
        $validator = $this->app['validator']->make([
            'email' => 'test@example.com',
            'password' => 'password123'
        ], $this->rules);

        $this->assertFalse($validator->fails());
    }

    public function test_login_validation_fails_with_nonexistent_email()
    {
        $validator = $this->app['validator']->make([
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }
} 