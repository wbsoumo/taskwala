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

    public function test_registration_requires_email_verification()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'mobile_number' => '9998887776',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'requires_verification' => true,
                'email' => 'newuser@test.com',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@test.com',
            'email_verified_at' => null,
        ]);
    }

    public function test_unverified_user_cannot_login_and_receives_verification_prompt()
    {
        User::create([
            'name' => 'Unverified Person',
            'email' => 'unverified@test.com',
            'mobile_number' => '9991112233',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'unverified@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'requires_verification' => true,
                'email' => 'unverified@test.com',
            ]);
    }

    public function test_user_can_verify_email_and_login()
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Verify User',
            'email' => 'verifyuser@test.com',
            'mobile_number' => '9992223344',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $otp = $regResponse->json('debug_code');

        $verifyResponse = $this->postJson('/api/v1/auth/verify-email', [
            'email' => 'verifyuser@test.com',
            'otp' => $otp,
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure(['token', 'user']);

        $this->assertNotNull(User::where('email', 'verifyuser@test.com')->first()->email_verified_at);
    }

    public function test_forgot_password_can_lookup_by_name_and_sends_to_email()
    {
        $user = User::create([
            'name' => 'Soumojit Partner',
            'email' => 'soumojit@test.com',
            'mobile_number' => '9876543210',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Search using name
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'login' => 'Soumojit Partner',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotEmpty($response->json('debug_code'));
        $otp = $response->json('debug_code');

        // Verify OTP with name
        $verifyRes = $this->postJson('/api/v1/auth/verify-otp', [
            'login' => 'Soumojit Partner',
            'otp' => $otp,
        ]);

        $verifyRes->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_web_forgot_password_flow_has_no_debug_mode()
    {
        $user = User::create([
            'name' => 'Web Partner',
            'email' => 'webpartner@test.com',
            'mobile_number' => '9876543211',
            'password' => Hash::make('OldPassword123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Request reset code
        $postResponse = $this->post(route('user.password.email'), [
            'login' => 'webpartner@test.com',
        ]);

        $postResponse->assertRedirect(route('user.password.reset'));
        $this->assertNull(session('debug_otp'));

        // Visit reset form
        $resetFormResponse = $this->get(route('user.password.reset'));
        $resetFormResponse->assertStatus(200);
        $resetFormResponse->assertDontSee('Dev Code');
        $resetFormResponse->assertDontSee('debug_otp');
    }

    public function test_web_registration_page_loads_and_registers_user()
    {
        $getResponse = $this->get(route('user.register'));
        $getResponse->assertStatus(200);
        $getResponse->assertSee('Join Taskwala');

        $postResponse = $this->post(route('user.register'), [
            'name' => 'New Web Partner',
            'email' => 'newwebpartner@test.com',
            'mobile_number' => '9876543299',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'upi_id' => 'partner@okhdfc',
            'upi_holder_name' => 'New Web Partner',
        ]);

        $postResponse->assertRedirect(route('user.dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'newwebpartner@test.com',
            'mobile_number' => '9876543299',
            'upi_id' => 'partner@okhdfc',
        ]);
    }
}
