<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReproLessonPlanTest extends TestCase
{
    public function test_principal_lesson_plans_page()
    {
        $this->withoutExceptionHandling();
        $principal = Employee::find(25);
        $response = $this->actingAs($principal)->get('/lesson-plans');
        $response->assertStatus(200);
        $response->assertDontSee('Page not found');
    }

    public function test_admin_lesson_plans_page()
    {
        $this->withoutExceptionHandling();
        $admin = Employee::find(1);
        $response = $this->actingAs($admin)->get('/lesson-plans');
        $response->assertStatus(200);
    }
}