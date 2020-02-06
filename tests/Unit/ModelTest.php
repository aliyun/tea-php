<?php

namespace HttpX\Tea\Tests\Unit;

use HttpX\Tea\Model;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public function testToMap()
    {
        $model = new ModelMock();
        $arr   = $model->toMap();

        self::assertEquals("a", $arr["a"]);
        self::assertEquals("b", $arr["b"]);
    }

    public function testValidate()
    {
        $model = new ModelMock();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("c is required.");
        $model->validate();
    }
}

class ModelMock extends Model
{
    public $a = "a";
    public $b = "b";
    public $c = "";

    public function __construct()
    {
        $this->_name["a"]     = "A";
        $this->_required["c"] = true;
    }
}
