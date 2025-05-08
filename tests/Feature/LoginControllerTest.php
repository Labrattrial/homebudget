<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    // ✅ Successful login
    public function testLoginSuccess()
    {
        $user = User::create([
            'name' => 'Jane Tester',
            'email' => 'jane@example.com',
            'password' => Hash::make('TestPass123'),
        ]);

        $expectedResult = true;

        $response = $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'TestPass123',
        ]);

        $actualResult = session()->has('user');

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals($expectedResult, $actualResult);
    }

    // ❌ Email is required
    public function testLoginFailsWhenEmailIsMissing()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'TestPass123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ❌ Email format is invalid
    public function testLoginFailsWithInvalidEmailFormat()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'TestPass123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ❌ Email does not include full domain
    public function testLoginFailsWithShortDomainEmail()
    {
        $response = $this->post('/login', [
            'email' => 'user@mail',
            'password' => 'TestPass123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ❌ Password is required
    public function testLoginFailsWhenPasswordIsMissing()
    {
        $response = $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // ❌ Email not found
    public function testLoginFailsWhenEmailDoesNotExist()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'SomePass123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ❌ Incorrect password
    public function testLoginFailsWhenPasswordIsIncorrect()
    {
        User::create([
            'name' => 'Jane Tester',
            'email' => 'jane@example.com',
            'password' => Hash::make('CorrectPassword123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'WrongPassword321',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // ✅ Logout test
    public function testLogoutClearsSession()
    {
        $user = User::create([
            'name' => 'Jane Tester',
            'email' => 'jane@example.com',
            'password' => Hash::make('TestPass123'),
        ]);

        $this->withSession(['user' => $user]);

        $response = $this->get('/logout');

        $expectedResult = false;
        $actualResult = session()->has('user');

        $response->assertRedirect(route('login'));
        $this->assertEquals($expectedResult, $actualResult);
    }
}
