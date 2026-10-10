<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->post('/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/profile');
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

        $response = $this->post('/register', [
            'username' => 'newuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $member = Member::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/profile');
        $this->assertAuthenticatedAs($member);
    }

    public function test_user_cannot_login_with_incorrect_password()
    {
        Member::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_when_accessing_profile()
    {
        $response = $this->get('/profile');
        $response->assertRedirect('/login');
    }
}
