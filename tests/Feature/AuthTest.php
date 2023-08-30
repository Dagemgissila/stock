<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success','data' => ['access_token','token_type','expires_in']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'wrong@example.com',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/auth/profile');

        $response->assertStatus(200)->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_invalidates_token(): void
    {
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/auth/logout')
             ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/auth/profile')
             ->assertStatus(401);
    }
}
