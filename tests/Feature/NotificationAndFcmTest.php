<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationAndFcmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock FirebaseService to avoid real external network requests during tests
        $mockFirebase = $this->createMock(FirebaseService::class);
        $mockFirebase->method('sendNotification')->willReturn([
            'success' => true,
            'message_id' => 'projects/taskwala-a9ab4/messages/test-msg-123',
        ]);
        $this->app->instance(FirebaseService::class, $mockFirebase);
    }

    public function test_user_can_register_fcm_token_via_api()
    {
        $user = User::create([
            'name' => 'FCM Test Partner',
            'email' => 'fcmtest@test.com',
            'mobile_number' => '9876543219',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/user/fcm-token', [
            'fcm_token' => 'sample_fcm_token_abc_123',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fcm_token' => 'sample_fcm_token_abc_123',
        ]);
    }

    public function test_user_login_stores_fcm_token()
    {
        $user = User::create([
            'name' => 'Login FCM Partner',
            'email' => 'loginfcm@test.com',
            'mobile_number' => '9876543220',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'loginfcm@test.com',
            'password' => 'Password123!',
            'fcm_token' => 'device_token_xyz_999',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fcm_token' => 'device_token_xyz_999',
        ]);
    }

    public function test_admin_can_send_broadcast_notification_with_deeplink()
    {
        $admin = Admin::create([
            'name' => 'Admin Boss',
            'email' => 'adminboss@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user1 = User::create([
            'name' => 'Partner One',
            'email' => 'p1@test.com',
            'mobile_number' => '9876543221',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'fcm_token' => 'token_p1',
            'email_verified_at' => now(),
        ]);

        $user2 = User::create([
            'name' => 'Partner Two',
            'email' => 'p2@test.com',
            'mobile_number' => '9876543222',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'fcm_token' => null,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.notifications.store'), [
            'title' => 'Big Campaign Boost!',
            'body' => 'Earn 2x commissions on all cards today.',
            'deeplink' => 'taskwala://campaigns',
            'target_type' => 'all',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('app_notifications', [
            'title' => 'Big Campaign Boost!',
            'deeplink' => 'taskwala://campaigns',
            'total_targeted' => 2,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user1->id,
            'title' => 'Big Campaign Boost!',
            'fcm_status' => 'sent',
            'is_read' => false,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user2->id,
            'title' => 'Big Campaign Boost!',
            'fcm_status' => 'no_token',
            'is_read' => false,
        ]);
    }

    public function test_user_can_view_and_toggle_read_status_via_api()
    {
        $user = User::create([
            'name' => 'Read Toggle Partner',
            'email' => 'readtoggle@test.com',
            'mobile_number' => '9876543223',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $appNotif = AppNotification::create([
            'title' => 'Important Alert',
            'body' => 'Check your wallet.',
            'target_type' => 'all',
            'total_targeted' => 1,
        ]);

        $userNotif = UserNotification::create([
            'app_notification_id' => $appNotif->id,
            'user_id' => $user->id,
            'title' => 'Important Alert',
            'body' => 'Check your wallet.',
            'is_read' => false,
        ]);

        // Get notifications
        $listRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');
        $listRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 1,
            ]);

        // Mark as read
        $readRes = $this->actingAs($user, 'sanctum')->postJson("/api/v1/notifications/{$userNotif->id}/read");
        $readRes->assertStatus(200)->assertJson(['success' => true]);

        $this->assertTrue($userNotif->fresh()->is_read);

        // Mark as unread
        $unreadRes = $this->actingAs($user, 'sanctum')->postJson("/api/v1/notifications/{$userNotif->id}/unread");
        $unreadRes->assertStatus(200)->assertJson(['success' => true]);

        $this->assertFalse($userNotif->fresh()->is_read);
    }

    public function test_admin_can_view_delivery_and_toggle_user_read_mark()
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Target User',
            'email' => 'target@test.com',
            'mobile_number' => '9876543224',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $appNotif = AppNotification::create([
            'title' => 'Test Notification',
            'body' => 'Test Message',
            'target_type' => 'all',
            'total_targeted' => 1,
        ]);

        $userNotif = UserNotification::create([
            'app_notification_id' => $appNotif->id,
            'user_id' => $user->id,
            'title' => 'Test Notification',
            'body' => 'Test Message',
            'is_read' => false,
        ]);

        // Admin views show page
        $showRes = $this->actingAs($admin, 'admin')->get(route('admin.notifications.show', $appNotif->id));
        $showRes->assertStatus(200)->assertSee('Target User');

        // Admin toggles read mark
        $toggleRes = $this->actingAs($admin, 'admin')->post(route('admin.notifications.toggle_read', $userNotif->id));
        $toggleRes->assertRedirect();

        $this->assertTrue($userNotif->fresh()->is_read);

        // Admin views registered users list
        $usersRes = $this->actingAs($admin, 'admin')->get(route('admin.notifications.users'));
        $usersRes->assertStatus(200)->assertSee('Target User');
    }
}
