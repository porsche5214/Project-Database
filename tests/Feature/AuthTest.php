<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// สมัคร / เข้าสู่ระบบผ่าน API ที่หน้า React ใช้ (/api/register, /api/login)
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertCreated()->assertJsonPath('user.username', 'testuser');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('member', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'member',
        ]);
    }

    public function test_user_cannot_register_with_existing_email()
    {
        Member::create([
            'username' => 'existing',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/register', [
            'username' => 'newuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $member = Member::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonPath('user.email', 'test@example.com');
        $this->assertAuthenticatedAs($member);
    }

    public function test_user_cannot_login_with_incorrect_password()
    {
        Member::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('login');
        $this->assertGuest();
    }

    public function test_guest_cannot_update_profile()
    {
        $this->postJson('/api/profile', ['email' => 'x@example.com'])->assertStatus(401);
    }
}
