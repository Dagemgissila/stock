<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
class WarehouseTest extends TestCase{
    use RefreshDatabase;
    private string $token;
    protected function setUp():void{parent::setUp();$this->token=auth('api')->login(User::factory()->create());}
    public function test_can_list_warehouses():void{
        $this->withHeader('Authorization','Bearer '.$this->token)
             ->getJson('/api/warehouses')->assertStatus(200);
    }
    public function test_warehouse_creation_requires_name():void{
        $this->withHeader('Authorization','Bearer '.$this->token)
             ->postJson('/api/warehouses',[])->assertStatus(422);
    }
}
