<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class GuestMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_can_access_login_page()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    public function test_guest_can_access_register_page()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
    }

    public function test_authenticated_user_cannot_access_login_page()
    {
        Auth::login($this->user);
        
        $response = $this->get(route('login'));
        $response->assertRedirect(route('user.dashboard'));
    }

    public function test_authenticated_user_cannot_access_register_page()
    {
        Auth::login($this->user);
        
        $response = $this->get(route('register'));
        $response->assertRedirect(route('user.dashboard'));
    }

    public function test_authenticated_user_gets_proper_message_when_accessing_login()
    {
        Auth::login($this->user);
        
        $response = $this->get(route('login'));
        $response->assertSessionHas('info', 'You are already logged in.');
    }

    public function test_authenticated_user_gets_proper_message_when_accessing_register()
    {
        Auth::login($this->user);
        
        $response = $this->get(route('register'));
        $response->assertSessionHas('info', 'You are already logged in.');
    }
} 