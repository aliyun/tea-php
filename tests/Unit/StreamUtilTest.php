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
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals('test content', (string) $stream);
    }

    public function testStreamForWithResource()
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'test content');
        fseek($resource, 0);
        
        $stream = StreamUtil::streamFor($resource);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals('test content', (string) $stream);
    }

    public function testStreamForWithExistingStream()
    {
        $existingStream = new Stream(fopen('php://memory', 'r+'));
        $existingStream->write('test content');
        $existingStream->rewind();
        
        $stream = StreamUtil::streamFor($existingStream);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame($existingStream, $stream); // Should return the same instance
        $this->assertEquals('test content', (string) $stream);
    }

    public function testStreamForWithCallable()
    {
        $stream = StreamUtil::streamFor(function() {
            return 'test content from callable';
        });
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals('test content from callable', (string) $stream);
    }

    public function testStreamForWithObjectToString()
    {
        $obj = new class {
            public function __toString() {
                return 'test content from object';
            }
        };
        
        $stream = StreamUtil::streamFor($obj);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals('test content from object', (string) $stream);
    }

    public function testStreamForWithNull()
    {
        $stream = StreamUtil::streamFor(null);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals('', (string) $stream);
    }

    public function testStreamForWithScalarValues()
    {
        $stream = StreamUtil::streamFor(123);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals('123', (string) $stream);

        $stream = StreamUtil::streamFor(45.67);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals('45.67', (string) $stream);

        $stream = StreamUtil::streamFor(true);
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertEquals('1', (string) $stream);
    }
}