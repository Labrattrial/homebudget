<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class AuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_access_protected_routes()
    {
        Auth::login($this->user);
        
        $response = $this->get(route('user.dashboard'));
        $response->assertStatus(200);
        
        $response = $this->get(route('user.expenses'));
        $response->assertStatus(200);
        
        $response = $this->get(route('user.analysis'));
        $response->assertStatus(200);
        
        $response = $this->get(route('user.settings'));
        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->get(route('user.dashboard'));
        $response->assertRedirect(route('login'));
        
        $response = $this->get(route('user.expenses'));
        $response->assertRedirect(route('login'));
        
        $response = $this->get(route('user.analysis'));
        $response->assertRedirect(route('login'));
        
        $response = $this->get(route('user.settings'));
        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_gets_proper_error_message()
    {
        $response = $this->get(route('user.dashboard'));
        $response->assertSessionHas('error', 'Please login to access this page.');
    }

    public function test_authenticated_user_can_access_api_routes()
    {
        Auth::login($this->user);
        
        $response = $this->get(route('user.dashboard.data'));
        $response->assertStatus(200);
        
        $response = $this->get(route('analysis.data'));
        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_api_routes()
    {
        $response = $this->get(route('user.dashboard.data'));
        $response->assertStatus(401);
        
        $response = $this->get(route('analysis.data'));
        $response->assertStatus(401);
    }
} 