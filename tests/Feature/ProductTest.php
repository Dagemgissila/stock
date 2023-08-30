<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $user        = User::factory()->create();
        $this->token = auth('api')->login($user);
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create();
        $this->withHeader('Authorization','Bearer '.$this->token)
             ->getJson('/api/products')
             ->assertStatus(200)
             ->assertJsonStructure(['success','data'=>['data','meta']]);
    }

    public function test_can_create_product(): void
    {
        $category = Category::factory()->create();
        $unit     = Unit::factory()->create();

        $this->withHeader('Authorization','Bearer '.$this->token)
             ->postJson('/api/products', [
                 'name'           => 'Test Product',
                 'category_id'    => $category->id,
                 'unit_id'        => $unit->id,
                 'sales_price'    => 100,
                 'purchase_price' => 80,
             ])
             ->assertStatus(201)
             ->assertJsonPath('data.name','Test Product');
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();
        $this->withHeader('Authorization','Bearer '.$this->token)
             ->deleteJson('/api/products/'.$product->id)
             ->assertStatus(200);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
