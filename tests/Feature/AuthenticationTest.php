<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_valid_credentials()
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->post(route('admin.login'), [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_affiliate_user_can_login_with_valid_credentials()
    {
        $user = User::create([
            'name' => 'Affiliate User',
            'email' => 'affiliate@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->post(route('user.login'), [
            'email' => 'affiliate@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('user.dashboard'));
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_users_cannot_access_admin_routes()
    {
        $user = User::create([
            'name' => 'Affiliate User',
            'email' => 'affiliate@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->actingAs($user, 'web');

        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.login'));
    }
}
