<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Model;
use AlibabaCloud\Dara\Util\ArrayToXml;
use AlibabaCloud\Dara\Util\BytesUtil;
use AlibabaCloud\Dara\Util\FormUtil;
use AlibabaCloud\Dara\Util\StreamUtil;
use AlibabaCloud\Dara\Util\XML;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;

class UtilExtraTest extends TestCase
{
    public function testArrayToXmlBranches()
    {
        $builder = new ArrayToXml('1.0', 'UTF-8');
        $xml = $builder->buildXML([
            '@id' => '1',
            '%' => 'text',
            '#note' => '<ok>',
            '!raw' => 'cdata',
            'nested' => [
                '@type' => 'n',
                '%' => ['inner' => 'v'],
                '#skip' => ['a' => 'b'],
                '!skip' => ['c' => 'd'],
            ],
            'items' => [
                ['name' => 'a'],
                'plain',
            ],
        ], 'root');
        $this->assertTrue(false !== strpos($xml, 'id="1"'));
        $this->assertTrue(false !== strpos($xml, '<name>a</name>'));
        $this->assertTrue(false !== strpos($xml, '<items>plain</items>'));

        set_error_handler(function () {
            return true;
        });
        try {
            $this->assertFalse($builder->buildXML('not-an-array'));
        } finally {
            restore_error_handler();
        }
    }

    public function testXmlStringResponseClass()
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<root><name>tea</name><extra>skip</extra></root>";
        $parsed = XML::parseXml($xml, 'AlibabaCloud\\Dara\\Tests\\Unit\\XmlHolder');
        $this->assertEquals('tea', $parsed['name']);
        $this->assertFalse(isset($parsed['extra']));
    }

    public function testBytesEdgeCases()
    {
        set_error_handler(function () {
            return true;
        });
        try {
            BytesUtil::from('zz', 'hex');
            $this->fail('expected invalid hex');
        } catch (\Exception $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'Invalid hex'));
        } finally {
            restore_error_handler();
        }

        try {
            BytesUtil::from('abc', 'ascii');
            $this->fail('expected unsupported encoding');
        } catch (\Exception $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'Unsupported encoding'));
        }

        foreach ([[1 => 65], ['x'], [256]] as $input) {
            try {
                BytesUtil::from($input);
                $this->fail('expected invalid bytes');
            } catch (\Exception $e) {
                $this->assertTrue(false !== strpos($e->getMessage(), 'Input must be an bytes'));
            }
        }
    }

    public function testFormStringAndStreamEdges()
    {
        $this->assertSame('', FormUtil::toFormString(null));
        $model = new FormModel();
        $model->name = 'tea';
        $this->assertSame('name=tea', FormUtil::toFormString($model));

        $long = str_repeat('x', 2048);
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $long);
        rewind($resource);
        $file = new \AlibabaCloud\Dara\Models\FileField();
        $file->filename = 'big.txt';
        $file->contentType = 'text/plain';
        $file->content = new Stream($resource);

        $empty = fopen('php://memory', 'r+');
        rewind($empty);
        $emptyFile = new \AlibabaCloud\Dara\Models\FileField();
        $emptyFile->filename = 'empty.txt';
        $emptyFile->contentType = 'text/plain';
        $emptyFile->content = new Stream($empty);

        $stream = FormUtil::toFileForm([
            'big' => $file,
            'emptyFile' => $emptyFile,
            'plain' => 'value',
        ], 'bound');
        $body = (string) $stream;
        $this->assertTrue(false !== strpos($body, $long));
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
        $stream->rewind();
        $this->assertSame(0, $stream->tell());
        $this->assertSame('', $stream->read(0));
        $this->assertSame($stream->getSize(), $stream->getSize());
        $this->assertNull($stream->readForm(8));
        $meta = $stream->getMetadata('uri');
        $this->assertNotNull($meta);
        $this->assertNull($stream->getMetadata(null));

        $this->setStreamProp($stream, 'readable', false);
        try {
            $stream->read(1);
            $this->fail('expected unreadable');
        } catch (\RuntimeException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'non-readable'));
        }
        $this->setStreamProp($stream, 'readable', true);

        $this->setStreamProp($stream, 'writable', false);
        try {
            $stream->write('x');
            $this->fail('expected unwritable');
        } catch (\RuntimeException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'non-writable'));
        }
        $this->setStreamProp($stream, 'writable', true);

        $this->setStreamProp($stream, 'seekable', false);
        try {
            $stream->seek(0);
            $this->fail('expected not seekable');
        } catch (\RuntimeException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'not seekable'));
        }

        try {
            $stream->read(-1);
            $this->fail('expected negative length');
        } catch (\RuntimeException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'negative'));
        }

        $detached = $stream->detach();
        $this->assertTrue(is_resource($detached));
        fclose($detached);
        $this->assertNull($stream->detach());
        $this->assertNull($stream->getSize());
        $this->assertSame([], $stream->getMetadata(null));
        $this->assertNull($stream->getMetadata('uri'));
        $this->assertSame('', (string) $stream);

        foreach (['getContents', 'eof', 'tell', 'read', 'write'] as $method) {
            try {
                if ($method === 'read') {
                    $stream->read(1);
                } elseif ($method === 'write') {
                    $stream->write('x');
                } else {
                    $stream->{$method}();
                }
                $this->fail($method . ' should fail');
            } catch (\RuntimeException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
        $stream->close();
    }

    public function testStreamUtilMemoryEvents()
    {
        set_error_handler(function () {
            return true;
        });
        try {
            $this->assertFalse(StreamUtil::streamFor('text'));
        } finally {
            restore_error_handler();
        }

        $payload = ": keep-alive\n\ndata: hello\nevent: ping\nid: 7\nretry: 12\n\npartial";
        $events = [];
        foreach (StreamUtil::readAsSSE($this->memoryStream($payload)) as $event) {
            $events[] = $event;
        }
        $this->assertCount(3, $events);
        $this->assertEquals('hello', $events[1]->data);
        $this->assertEquals('ping', $events[1]->event);
        $this->assertEquals('7', $events[1]->id);
        $this->assertEquals(12, $events[1]->retry);
        $this->assertEquals('partial', $events[2]->data);

        $empty = [];
        foreach (StreamUtil::readAsSSE($this->memoryStream('')) as $event) {
            $empty[] = $event;
        }
        $this->assertSame([], $empty);
    }

    private function memoryStream($contents)
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $contents);
        rewind($resource);

        return new Stream($resource);
    }

    private function setStreamProp($stream, $name, $value)
    {
        $property = new \ReflectionProperty('AlibabaCloud\\Dara\\Util\\FileFormStream', $name);
        $property->setAccessible(true);
        $property->setValue($stream, $value);
    }
}

class XmlHolder
{
    public $name = '';
}

class FormModel extends Model
{
    public $name;

    public function toArray($ignoreStream = false)
    {
        return ['name' => $this->name];
    }
}
