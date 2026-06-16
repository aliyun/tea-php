<?php

namespace AlibabaCloud\Dara\Tests;

use AlibabaCloud\Dara\Util\StringUtil;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class StringTest extends TestCase
{

    public function testToBytes()
    {
        $this->assertEquals([
            115, 116, 114, 105, 110, 103,
        ], StringUtil::toBytes('string'));
        $this->assertEquals([
            115, 116, 114, 105, 110, 103,
        ], StringUtil::toBytes([
            115, 116, 114, 105, 110, 103,
        ]));
        $this->assertEquals([
            115, 116, 114, 105, 110, 103,
        ], StringUtil::toBytes('c3RyaW5n', 'base64'));
        $this->assertEquals([
            115, 116, 114, 105, 110, 103,
        ], StringUtil::toBytes('737472696e67', 'hex'));
    }

    public function testHasPrefix()
    {
        $this->assertEquals(true, StringUtil::hasPrefix('testAbc', 'test'));
        $this->assertEquals(true, StringUtil::hasPrefix('testAbc', ''));
        $this->assertEquals(false, StringUtil::hasPrefix('testAbc', null));
        $this->assertEquals(false, StringUtil::hasPrefix(null, ''));
        $this->assertEquals(false, StringUtil::hasPrefix('testAbc', 'Abc'));
    }

    public function testHasSuffix()
    {
        $this->assertEquals(false, StringUtil::hasSuffix('testAbc', 'test'));
        $this->assertEquals(true, StringUtil::hasSuffix('testAbc', ''));
        $this->assertEquals(false, StringUtil::hasSuffix('testAbc', null));
        $this->assertEquals(false, StringUtil::hasSuffix(null, ''));
        $this->assertEquals(true, StringUtil::hasSuffix('testAbc', 'Abc'));
    }
}