<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\AnalysisController;
use App\Models\User;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class AnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->controller = new AnalysisController();
    }

    public function test_index_returns_correct_view()
    {
        Auth::login($this->user);
        $response = $this->get(route('user.analysis'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.analysis');
    }

    public function test_get_data_returns_correct_data()
    {
        Auth::login($this->user);
        $response = $this->get(route('analysis.data'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'categories',
            'totals',
            'trends'
        ]);
    }

    public function test_custom_date_range_data_returns_correct_data()
    {
        Auth::login($this->user);
        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->get(route('analysis.customData', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'categories',
            'totals',
            'trends'
        ]);
    }

    public function test_get_week_data_returns_correct_data()
    {
        Auth::login($this->user);
        $weekNumber = now()->weekOfYear;

        $response = $this->get("/analysis/data/week/{$weekNumber}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'categories',
            'totals',
            'trends'
        ]);
    }

    public function test_get_weekly_data_returns_correct_data()
    {
        Auth::login($this->user);
        $response = $this->get(route('analysis.weekly'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'labels',
            'data'
        ]);
    }

    public function test_get_monthly_data_returns_correct_data()
    {
        Auth::login($this->user);
        $response = $this->get(route('analysis.monthly'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'labels',
            'data'
        ]);
    }

    public function test_analysis_data_includes_correct_categories()
    {
        Auth::login($this->user);
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'food',
            'amount' => 100
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'transport',
            'amount' => 50
        ]);

        $response = $this->get(route('analysis.data'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'categories' => ['food', 'transport'],
            'totals' => ['food', 'transport']
        ]);
    }
} 