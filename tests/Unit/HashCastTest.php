<?php
namespace Tests\Unit;
use Tests\TestCase;
use App\Casts\Hash;

class HashCastTest extends TestCase
{
    public function test_encodes_integer(): void {
        $cast=new Hash(); $model=new class{};
        $result=$cast->get($model,'id',1,[]);
        $this->assertIsString($result);
        $this->assertNotEquals('1',$result);
    }
    public function test_null_returns_null(): void {
        $cast=new Hash(); $model=new class{};
        $this->assertNull($cast->get($model,'id',null,[]));
    }
}
