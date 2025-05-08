<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Transaction;
use App\Models\Category;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MainDashboardController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_dashboard_view_with_data()
    {
        // Setup
        Transaction::factory()->create(['amount' => 1000]);
        Category::factory()->count(3)->create();

        // Act
        $controller = new MainDashboardController();
        $response = $controller->showDashboard();

        // Assert
        $expected = 1000;
        $actual = $response->getData()['netWorth'];
        $this->assertEquals($expected, $actual);

        $expected = 3;
        $actual = count($response->getData()['categories']);
        $this->assertEquals($expected, $actual);

        $expected = 1;
        $actual = count($response->getData()['transactions']);
        $this->assertEquals($expected, $actual);

        $this->assertEquals('dashboard', $response->name());
    }
}
