<?php

namespace AlibabaCloud\Dara\Tests;

use AlibabaCloud\Dara\Util\StreamUtil;
use AlibabaCloud\Dara\Request;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class StreamTest extends TestCase
{

    public function getStream()
    {
        return new Stream(fopen('http://httpbin.org/get', 'r'));
    }

    public function testReadAsBytes()
    {
        $bytes = StreamUtil::readAsBytes($this->getStream());
        $this->assertEquals(123, $bytes[0]);
    }

    public function testReadAsString()
    {
        $string = StreamUtil::readAsString($this->getStream());
        $this->assertEquals($string[0], '{');
    }

    public function testReadAsJSON()
    {
        $result = StreamUtil::readAsJSON($this->getStream());
        $this->assertEquals('http://httpbin.org/get', $result['url']);
    }
}