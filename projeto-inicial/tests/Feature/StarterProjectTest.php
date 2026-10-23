<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StarterProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_exposes_the_complete_stack(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('stage', 'starter')
            ->assertJsonCount(5, 'stack');
    }

    public function test_dashboard_is_ready_for_the_lesson(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ponto de partida')
            ->assertSee('Slack Block Kit');
    }

    public function test_lesson_artifacts_exist(): void
    {
        $this->assertFileExists(app_path('Notifications/OrderStatusChanged.php'));
        $this->assertFileExists(app_path('Services/OrderStatusService.php'));
        $this->assertFileExists(app_path('Http/Controllers/NotificationCenterController.php'));
        $this->assertFileExists(public_path('service-worker.js'));
    }
}
