<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_member_and_logs_in(): void
    {
        $response = $this->postJson('/api/register', [
            'username' => 'newuser',
            'email' => 'newuser@naemak.test',
            'password' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '0899999999',
            'address' => '999 Sukhumvit Road',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('user.username', 'newuser');
        $response->assertJsonPath('user.email', 'newuser@naemak.test');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('member', [
            'username' => 'newuser',
            'email' => 'newuser@naemak.test',
        ]);

        $this->assertDatabaseHas('address', [
            'address_line' => '999 Sukhumvit Road',
        ]);
    }

    public function test_register_validates_unique_email_and_username(): void
    {
        Member::create([
            'username' => 'existinguser',
            'email' => 'existing@naemak.test',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/register', [
            'username' => 'existinguser',
            'email' => 'existing@naemak.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username', 'email']);
    }

    public function test_login_with_email(): void
    {
        Member::create([
            'username' => 'somchai',
            'email' => 'somchai@naemak.test',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'somchai@naemak.test',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.username', 'somchai');
        $this->assertAuthenticated();
    }

    public function test_login_with_username(): void
    {
        Member::create([
            'username' => 'somchai',
            'email' => 'somchai@naemak.test',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'somchai',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.username', 'somchai');
        $this->assertAuthenticated();
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        Member::create([
            'username' => 'somchai',
            'email' => 'somchai@naemak.test',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'somchai',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['login']);
        $this->assertGuest();
    }

    public function test_logout_invalidates_session(): void
    {
        $member = Member::create([
            'username' => 'somchai',
            'email' => 'somchai@naemak.test',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($member)->postJson('/api/logout');

        $response->assertOk();
        $this->assertGuest();
    }

    public function test_unauthenticated_user_accessing_profile_gets_401(): void
    {
        $response = $this->putJson('/api/profile', [
            'email' => 'test@naemak.test',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_profile_and_address(): void
    {
        $member = Member::create([
            'username' => 'somchai',
            'email' => 'somchai@naemak.test',
            'password_hash' => Hash::make('password123'),
            'first_name' => 'Somchai',
            'last_name' => 'Dee',
        ]);

        $response = $this->actingAs($member)->putJson('/api/profile', [
            'first_name' => 'SomchaiUpdated',
            'last_name' => 'DeeUpdated',
            'email' => 'somchai@naemak.test',
            'phone' => '0812345678',
            'address' => '123 New Road',
            'city' => 'Phaya Thai',
            'province' => 'Bangkok',
            'postal_code' => '10400',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.first_name', 'SomchaiUpdated');
        $response->assertJsonPath('user.last_name', 'DeeUpdated');
        $response->assertJsonPath('user.phone', '0812345678');
        $this->assertDatabaseHas('address', [
            'member_id' => $member->member_id,
            'address_line' => '123 New Road',
        ]);
    }
}
