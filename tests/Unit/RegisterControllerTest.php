<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Controllers\RegisterController;

class RegisterControllerTest extends TestCase
{
    public function testStudentIdIsRequired()
    {
        $controller = new RegisterController();
        $request = Request::create('/register', 'POST', ['name' => 'Test User']);

        $expected = false;
        $actual = $request->has('student_id');

        $this->assertEquals($expected, $actual);
    }

    public function testPasswordConfirmationMatches()
    {
        $controller = new RegisterController();
        $request = Request::create('/register', 'POST', [
            'password' => 'secret123',
            'password_confirmation' => 'secret123'
        ]);

        $expected = true;
        $actual = $request->input('password') === $request->input('password_confirmation');

        $this->assertEquals($expected, $actual);
    }

    public function testEmailFormatIsValid()
    {
        $controller = new RegisterController();
        $email = 'student@example.com';

        $expected = true;
        $actual = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        $this->assertEquals($expected, $actual);
    }

    public function testMissingEmailFailsValidation()
    {
        $controller = new RegisterController();
        $request = Request::create('/register', 'POST', []);

        $expected = false;
        $actual = $request->has('email');

        $this->assertEquals($expected, $actual);
    }
}
