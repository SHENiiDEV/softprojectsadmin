<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaMobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
    }

    public function test_pwa_manifest_file_exists_and_is_valid_json(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $content = file_get_contents($manifestPath);
        $json = json_decode($content, true);

        $this->assertIsArray($json);
        $this->assertEquals('SoftProject Hub', $json['name']);
        $this->assertEquals('standalone', $json['display']);
        $this->assertNotEmpty($json['icons']);
    }

    public function test_pwa_service_worker_file_exists(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $content = file_get_contents($swPath);
        $this->assertStringContainsString('softproject-pwa', $content);
        $this->assertStringContainsString('addEventListener(\'install\'', $content);
        $this->assertStringContainsString('addEventListener(\'fetch\'', $content);
    }

    public function test_pwa_icons_exist(): void
    {
        $this->assertFileExists(public_path('pwa-192x192.png'));
        $this->assertFileExists(public_path('pwa-512x512.png'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
    }

    public function test_dashboard_renders_pwa_meta_tags_and_service_worker_registration(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('manifest.json');
        $response->assertSee('apple-mobile-web-app-capable');
        $response->assertSee('navigator.serviceWorker.register(\'/sw.js\')', false);
    }

    public function test_mobile_bottom_navigation_bar_renders_all_required_menus(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('aria-label="Mobile Navigation"', false);

        // 1. Dashboard (Left 1)
        $response->assertSee(route('dashboard'));
        $response->assertSee('Dashboard');

        // 2. Companies (Left 2)
        $response->assertSee(route('projects.index'));
        $response->assertSee('Companies');

        // 3. My Work (Center Main)
        $response->assertSee(route('my.work'));
        $response->assertSee('My Work');

        // 4. PCI DSS (Right 1)
        $response->assertSee(route('pci-dss.index'));
        $response->assertSee('PCI DSS');

        // 5. Credentials (Right 2)
        $response->assertSee(route('credentials'));
        $response->assertSee('Credentials');
    }

    public function test_pwa_service_worker_has_push_and_notification_click_handlers(): void
    {
        $swPath = public_path('sw.js');
        $content = file_get_contents($swPath);

        $this->assertStringContainsString('addEventListener(\'push\'', $content);
        $this->assertStringContainsString('addEventListener(\'notificationclick\'', $content);
        $this->assertStringContainsString('registration.showNotification', $content);
    }

    public function test_mobile_header_safe_styles_and_classes_rendered(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('.mobile-header-safe', false);
        $response->assertSee('env(safe-area-inset-top', false);
        $response->assertSee('mobile-header-safe', false);
        $response->assertSee('h-[100dvh]', false);
    }

    public function test_notification_tray_includes_push_notification_handlers(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('showPushNotification', false);
        $response->assertSee('navigator.serviceWorker', false);
        $response->assertSee('Push на телефон', false);
    }

    public function test_new_task_and_comment_notifications_are_recorded_for_user_push_delivery(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole('worker');

        $task = Task::create([
            'title' => 'Urgent Security Task',
            'creator_id' => $this->user->id,
            'assigned_to' => $worker->id,
            'status' => 'todo',
            'priority' => 'high',
        ]);

        // Worker should receive task assignment notification
        $worker->refresh();
        $this->assertCount(1, $worker->unreadNotifications);
        $this->assertEquals('New Task Assigned', $worker->unreadNotifications->first()->data['title']);

        // Now admin adds a comment to the task
        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'content' => 'Please review ASAP!',
        ]);

        NotificationService::sendNewCommentNotification($comment);

        $worker->refresh();
        $this->assertCount(2, $worker->unreadNotifications);
        $latestNotification = $worker->unreadNotifications()->orderByDesc('id')->first();
        $this->assertEquals('New Comment on Task', $latestNotification->data['title']);
        $this->assertStringContainsString('Please review ASAP', $latestNotification->data['message']);
    }
}
