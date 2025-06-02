<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
class StockAdjustmentTest extends TestCase{
    use RefreshDatabase;
    private string $token;
    protected function setUp():void{parent::setUp();$this->token=auth('api')->login(User::factory()->create());}
    public function test_can_list_adjustments():void{
        $this->withHeader('Authorization','Bearer '.$this->token)
             ->getJson('/api/stock-adjustments')->assertStatus(200)
             ->assertJsonStructure(['success','data'=>['data','meta']]);
    }
    public function test_adjustment_requires_warehouse_and_product():void{
        $this->withHeader('Authorization','Bearer '.$this->token)
             ->postJson('/api/stock-adjustments',[])
             ->assertStatus(422)
             ->assertJsonPath('success',false);
    }
}
