<?php

namespace AlibabaCloud\Dara\Tests;

use AlibabaCloud\Dara\Util\BytesUtil;
use AlibabaCloud\Dara\Exception\DaraException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class BytesTest extends TestCase
{

    public function testToBytes()
    {
        $this->assertEquals([
            115, 116, 114, 105, 110, 103,
        ], BytesUtil::from('string'));
        $this->assertEquals([
            115, 116, 114, 105, 110, 103,
        ], BytesUtil::from('string', 'utf8'));
        $this->assertEquals([
            115, 116, 114, 105, 110, 103,
        ], BytesUtil::from([
            115, 116, 114, 105, 110, 103,
        ]));
        $this->assertEquals([
            115, 116, 114, 105, 110, 103,
        ], BytesUtil::from('c3RyaW5n', 'base64'));
        $this->assertEquals([
            115, 116, 114, 105, 110, 103,
        ], BytesUtil::from('737472696e67', 'hex'));
    }

     /**
     * @expectedException DaraException
     * @expectedExceptionMessage  Input must be an bytes or a string.
     * @throws DaraException
     */
    public function testToBytesNull()
    {
        $this->expectException(DaraException::class);
        $this->expectExceptionMessage('Input must be an bytes or a string.');
        BytesUtil::from(null);
    }

    /**
     * @expectedException DaraException
     * @expectedExceptionMessage  Input must be an bytes or a string.
     * @throws DaraException
     */
    public function testToBytesNotBytes()
    {
        $this->expectException(DaraException::class);
        $this->expectExceptionMessage('Input must be an bytes or a string.');
        BytesUtil::from([1111333]);
    }

    public function testToString()
    {
        $this->assertEquals('string', BytesUtil::toString([
            115, 116, 114, 105, 110, 103,
        ]));
        $this->assertEquals('string', BytesUtil::toString('string'));
        $this->assertEquals('string', BytesUtil::toString([
            115, 116, 114, 105, 110, 103,
        ], 'utf8'));
        $this->assertEquals('737472696e67', BytesUtil::toString([
            115, 116, 114, 105, 110, 103,
        ], 'hex'));
        $this->assertEquals('c3RyaW5n', BytesUtil::toString([
            115, 116, 114, 105, 110, 103,
        ], 'base64'));
    }
}