<?php

namespace AlibabaCloud\Tea\Tests\Unit;

use AlibabaCloud\Tea\Model;
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

    public function testInit()
    {
        $config = new Config([
            "accessKeyId"     => "fakeAccessKeyId",
            "accessKeySecret" => "fakeAccessKeySecret"
        ]);
        $this->assertEquals("fakeAccessKeyId", $config->accessKeyId);
        $this->assertEquals("fakeAccessKeySecret", $config->accessKeySecret);
    }
}

class Config extends Model
{
    public $accessKeyId;
    public $accessKeySecret;
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
        parent::__construct();
    }
}
