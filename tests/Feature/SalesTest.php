<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
class SalesTest extends TestCase{
    use RefreshDatabase;
    private string $token;
    protected function setUp():void{parent::setUp();$this->token=auth('api')->login(User::factory()->create());}
    public function test_can_list_sales():void{
        $this->withHeader('Authorization','Bearer '.$this->token)
             ->getJson('/api/sales')->assertStatus(200)
             ->assertJsonStructure(['success','data'=>['data','meta']]);
    }
    public function test_date_filters_accepted():void{
        $this->withHeader('Authorization','Bearer '.$this->token)
             ->getJson('/api/sales?from_date=2024-01-01&to_date=2024-12-31')->assertStatus(200);
    }
}
