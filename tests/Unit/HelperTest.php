<?php

namespace AlibabaCloud\Tea\Tests\Unit;

use AlibabaCloud\Tea\Helper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class HelperTest extends TestCase
{
    public function testFindFromString()
    {
        $this->assertEquals('key', Helper::findFromString("@real key\n", '@real', "\n"));
    }

    public function testIsJson()
    {
        $tmp = ['a' => 'b'];
        $this->assertTrue(Helper::isJson(json_encode($tmp)));
        $this->assertFalse(Helper::isJson('not json string'));
    }

    public function testMerge()
    {
        $data1 = [
            0   => 'foo',
            'a' => [
                'b' => 1,
            ],
            1   => 'bar',
        ];
        $data2 = [
            'f' => [
                'e' => [
                    'x' => 1,
                ],
            ],
        ];
        $this->assertEquals([
            0   => 'foo',
            'a' => [
                'b' => 1,
            ],
            1   => 'bar',
            'f' => [
                'e' => [
                    'x' => 1,
                ],
            ],
        ], Helper::merge([$data1, $data2]));
    }
}
