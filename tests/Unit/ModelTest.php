<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Model;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ModelTest extends TestCase
{
    public function testToMap()
    {
        $model = new ModelMock();
        $arr   = $model->toMap();

        self::assertEquals('a', $arr['A']);
        self::assertEquals('b', $arr['b']);
    }

    public function testValidate()
    {
        $model = new ModelMock();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('c is required.');
        $model->validate();
    }

    public function testInit()
    {
        $config = new Config([
            'accessKeyId'     => 'fakeAccessKeyId',
            'accessKeySecret' => 'fakeAccessKeySecret',
        ]);
        $this->assertEquals('fakeAccessKeyId', $config->accessKeyId);
        $this->assertEquals('fakeAccessKeySecret', $config->accessKeySecret);
    }

    public function testToModel()
    {
        $model = Model::toModel([
            'A' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ], new ModelMock());
        $this->assertEquals(1, $model->a);
        $this->assertEquals(2, $model->b);
        $this->assertEquals(3, $model->c);
    }

    public function testValidateRequired()
    {
        Model::validateRequired('FieldName', null, false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FieldName is required');
        Model::validateRequired('FieldName', null, true);
    }

    public function testValidateMaxLength()
    {
        Model::validateMaxLength('FieldName', 'string', 10);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FieldName is exceed max-length: 5');
        Model::validateMaxLength('FieldName', 'string', 5);
    }

    public function testValidateMinLength()
    {
        Model::validateMinLength('FieldName', 'string', 5);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FieldName is less than min-length: 10');
        Model::validateMinLength('FieldName', 'string', 10);
    }

    public function testValidatePattern()
    {
        Model::validatePattern('FieldName', 'string123', '[a-z0-9A-Z]+');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FieldName is not match [a-z0-9A-Z]+');
        Model::validatePattern('FieldName', '@string', '[a-z0-9A-Z]+');
    }

    public function testValidatePatternWithEmptyValue()
    {
        Model::validatePattern('FieldName', null, '/^[a-zA-Z0-9_-]+$/');
        Model::validatePattern('FieldName', '', '/^[a-zA-Z0-9_-]+$/');
        // No throws is OK
        self::assertTrue(true);
    }

    public function testValidateMaximum()
    {
        Model::validateMaximum('FieldName', 100, 101);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FieldName cannot be greater than 99');
        Model::validateMaximum('FieldName', 100, 99);
    }

    public function testValidateMinimum()
    {
        Model::validateMinimum('FieldName', 100, 99);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FieldName cannot be less than 101');
        Model::validateMinimum('FieldName', 100, 101);
    }

    public function testValidateArray()
    {
        Model::validateArray(['FieldName', 100, 99]);
        $this->assertTrue(true);
        $ma = [new Config([
            'accessKeyId' => '123456'
        ]), new Config([
            'accessKeyId' => '123456',
            'accessKeySecret' => 30
        ])];
        Model::validateArray($ma);
        $this->assertTrue(true);

    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage accessKeyId is required
     * @throws InvalidArgumentException
     */
    public function testValidateArrayErr1() {
        $ma = [new Config([
            'accessKeySecret' => 40
        ]), new Config([
            'accessKeyId' => '123456',
            'accessKeySecret' => 30
        ])];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accessKeyId is required');

        Model::validateArray($ma);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage accessKeyId is less than min-length: 5
     * @throws InvalidArgumentException
     */
    public function testValidateArrayErr2() {
        $ma = [new Config([
            'accessKeyId' => '123456',
            'accessKeySecret' => 40
        ]), new Config([
            'accessKeyId' => '1234',
            'accessKeySecret' => 30
        ])];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accessKeyId is less than min-length: 5');

        Model::validateArray($ma);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage accessKeyId is exceed max-length: 10
     * @throws InvalidArgumentException
     */
    public function testValidateArrayErr3() {
        $ma = [new Config([
            'accessKeyId' => '12345678901',
            'accessKeySecret' => 40
        ]), new Config([
            'accessKeyId' => '1234',
            'accessKeySecret' => 30
        ])];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accessKeyId is exceed max-length: 10');

        Model::validateArray($ma);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage accessKeySecret cannot be greater than 50
     * @throws InvalidArgumentException
     */
    public function testValidateArrayErr4() {
        $ma = [new Config([
            'accessKeyId' => '123456',
            'accessKeySecret' => 60
        ]), new Config([
            'accessKeyId' => '123456',
            'accessKeySecret' => 30
        ])];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accessKeySecret cannot be greater than 50');

        Model::validateArray($ma);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage accessKeySecret cannot be less than 10
     * @throws InvalidArgumentException
     */
    public function testValidateArrayErr5() {
        $ma = [new Config([
            'accessKeyId' => '123456',
            'accessKeySecret' => 20
        ]), new Config([
            'accessKeyId' => '123456',
            'accessKeySecret' => 3
        ])];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accessKeySecret cannot be less than 10');

        Model::validateArray($ma);
    }
}

class Config extends Model
{
    public $accessKeyId;
    public $accessKeySecret;

    public function validate()
    {
        Model::validateRequired('accessKeyId', $this->accessKeyId, true);
        Model::validateMinLength('accessKeyId', $this->accessKeyId, 5);
        Model::validateMaxLength('accessKeyId', $this->accessKeyId, 10);
        Model::validateMinimum('accessKeySecret', $this->accessKeySecret, 10);
        Model::validateMaximum('accessKeySecret', $this->accessKeySecret, 50);

        parent::validate();
    }
}