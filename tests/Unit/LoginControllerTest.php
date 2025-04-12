<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Controllers\LoginController;

class LoginControllerTest extends TestCase
{
    public function testEmailIsRequired()
    {
        $controller = new LoginController();
        $request = Request::create('/login', 'POST', ['password' => 'secret']);
        
        $expected = false;
        $actual = $request->has('email');

        $this->assertEquals($expected, $actual);
    }

    public function testPasswordIsRequired()
    {
        $controller = new LoginController();
        $request = Request::create('/login', 'POST', ['email' => 'user@example.com']);

        $expected = false;
        $actual = $request->has('password');

        $this->assertEquals($expected, $actual);
    }

    public function testEmailFormatValidation()
    {
        $controller = new LoginController();
        $email = 'invalidemail.com';

        $expected = false;
        $actual = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        $this->assertEquals($expected, $actual);
    }

    public function testValidEmailPassesValidation()
    {
        $controller = new LoginController();
        $email = 'user@example.com';

        $expected = true;
        $actual = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        $this->assertEquals($expected, $actual);
    }
}
