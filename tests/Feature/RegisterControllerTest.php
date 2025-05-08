<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    // ✅ Test successful registration
    public function testSuccessfulRegistration()
    {
        $expectedResult = true;

        $response = $this->post('/signup', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $actualResult = Auth::check();

        $response->assertRedirect(route('login'));
        $this->assertEquals($expectedResult, $actualResult);
    }

    // ❌ Name required
    public function testRegistrationFailsWhenNameIsMissing()
    {
        $response = $this->post('/signup', [
            'name' => '',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    // ❌ Name has invalid characters
    public function testRegistrationFailsWithInvalidNameCharacters()
    {
        $response = $this->post('/signup', [
            'name' => 'John123!',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    // ❌ Email required
    public function testRegistrationFailsWhenEmailIsMissing()
    {
        $response = $this->post('/signup', [
            'name' => 'John Doe',
            'email' => '',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ❌ Email has invalid format
    public function testRegistrationFailsWithInvalidEmailFormat()
    {
        $response = $this->post('/signup', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ❌ Email already exists
    public function testRegistrationFailsWhenEmailIsAlreadyTaken()
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'john@example.com',
            'password' => Hash::make('Password123'),
        ]);

        $response = $this->post('/signup', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ❌ Password required
    public function testRegistrationFailsWhenPasswordIsMissing()
    {
        $response = $this->post('/signup', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // ❌ Password too short
    public function testRegistrationFailsWhenPasswordIsTooShort()
    {
        $response = $this->post('/signup', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Abc1',
            'password_confirmation' => 'Abc1',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // ❌ Password missing uppercase
    public function testRegistrationFailsWhenPasswordMissingUppercase()
    {
        $response = $this->post('/signup', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // ❌ Password missing lowercase
    public function testRegistrationFailsWhenPasswordMissingLowercase()
    {
        $response = $this->post('/signup', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'PASSWORD123',
            'password_confirmation' => 'PASSWORD123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // ❌ Password missing digit
    public function testRegistrationFailsWhenPasswordMissingDigit()
    {
        $response = $this->post('/signup', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password',
            'password_confirmation' => 'Password',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // ❌ Password confirmation mismatch
    public function testRegistrationFailsWhenPasswordsDoNotMatch()
    {
        $response = $this->post('/signup', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'WrongPassword123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
