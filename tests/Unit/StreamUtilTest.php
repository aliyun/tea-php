<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Util\StreamUtil;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class StreamUtilTest extends TestCase
{
    public function testStreamForWithString()
    {
        $stream = StreamUtil::streamFor('test content');
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertEquals('test content', (string) $stream);
    }

    public function testStreamForWithResource()
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'test content');
        fseek($resource, 0);
        
        $stream = StreamUtil::streamFor($resource);
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertEquals('test content', (string) $stream);
    }

    public function testStreamForWithExistingStream()
    {
        $existingStream = new Stream(fopen('php://memory', 'r+'));
        $existingStream->write('test content');
        $existingStream->rewind();
        
        $stream = StreamUtil::streamFor($existingStream);
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertSame($existingStream, $stream); // Should return the same instance
        $this->assertEquals('test content', (string) $stream);
    }

    public function testStreamForWithCallable()
    {
        $stream = StreamUtil::streamFor(function() {
            return 'test content from callable';
        });
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertEquals('test content from callable', (string) $stream);
    }

    public function testStreamForWithObjectToString()
    {
        $obj = new TestToStringClass();
        
        $stream = StreamUtil::streamFor($obj);
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertEquals('test content from object', (string) $stream);
    }

    public function testStreamForWithNull()
    {
        $stream = StreamUtil::streamFor(null);
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertEquals('', (string) $stream);
    }

    public function testStreamForWithScalarValues()
    {
        $stream = StreamUtil::streamFor(123);
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertEquals('123', (string) $stream);

        $stream = StreamUtil::streamFor(45.67);
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertEquals('45.67', (string) $stream);

        $stream = StreamUtil::streamFor(true);
        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertEquals('1', (string) $stream);
    }
    
    public function testStreamForWithUnsupportedType()
    {
        $this->expectException('InvalidArgumentException');
        StreamUtil::streamFor(array());
    }
}

class TestToStringClass
{
    public function __toString() {
        return 'test content from object';
    }
}