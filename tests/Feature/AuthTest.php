<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login(): void {
        $user = User::factory()->create(['password'=>bcrypt('password123')]);
        $this->postJson('/api/auth/login',['email'=>$user->email,'password'=>'password123'])
             ->assertStatus(200)
             ->assertJsonStructure(['success','data'=>['access_token','token_type','expires_in']]);
    }

    public function test_login_fails_wrong_credentials(): void {
        $this->postJson('/api/auth/login',['email'=>'bad@x.com','password'=>'wrong'])
             ->assertStatus(401)->assertJson(['success'=>false]);
    }

    public function test_can_fetch_profile(): void {
        $user  = User::factory()->create();
        $token = auth('api')->login($user);
        $this->withHeader('Authorization','Bearer '.$token)
             ->getJson('/api/auth/profile')
             ->assertStatus(200)
             ->assertJsonPath('data.email',$user->email);
    }

    public function test_logout_invalidates_token(): void {
        $user  = User::factory()->create();
        $token = auth('api')->login($user);
        $this->withHeader('Authorization','Bearer '.$token)->postJson('/api/auth/logout')->assertStatus(200);
        $this->withHeader('Authorization','Bearer '.$token)->getJson('/api/auth/profile')->assertStatus(401);
    }
}
