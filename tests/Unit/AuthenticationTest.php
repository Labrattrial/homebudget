<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $loginController;
    protected $registerController;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginController = new LoginController();
        $this->registerController = new RegisterController();
    }

    public function test_show_login_form_returns_correct_view()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_show_signup_form_returns_correct_view()
    {
        $response = $this->get(route('signup'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect(route('user.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_incorrect_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_register_with_valid_data()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->post(route('signup'), $userData);

        $response->assertRedirect(route('user.dashboard'));
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        $this->assertAuthenticated();
    }

    public function test_user_cannot_register_with_invalid_data()
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different'
        ];

        $response = $this->post(route('signup'), $userData);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email'
        ]);
        $this->assertGuest();
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $response = $this->get(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_access_login_page()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $response = $this->get(route('login'));
        $response->assertRedirect(route('user.dashboard'));
    }

    public function test_authenticated_user_cannot_access_signup_page()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $response = $this->get(route('signup'));
        $response->assertRedirect(route('user.dashboard'));
    }
} 