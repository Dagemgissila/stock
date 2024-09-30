<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    private string $token;
    protected function setUp(): void {
        parent::setUp();
        $this->token=auth('api')->login(User::factory()->create());
    }
    public function test_can_list_products(): void {
        $this->withHeader('Authorization','Bearer '.$this->token)
             ->getJson('/api/products')
             ->assertStatus(200)
             ->assertJsonStructure(['success','data'=>['data','meta']]);
    }
    public function test_requires_auth(): void {
        $this->getJson('/api/products')->assertStatus(401);
    }
}
