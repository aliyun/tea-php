<?php

namespace AlibabaCloud\Dara\Tests;

use AlibabaCloud\Dara\Util\FormUtil;
use AlibabaCloud\Dara\Util\FileFormStream;
use AlibabaCloud\Dara\Models\FileField;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class FormTest extends TestCase
{

    public function testToFormString()
    {
        $query = [
            'foo'            => 'bar',
            'empty'          => '',
            'a'              => null,
            'withWhiteSpace' => 'a b',
        ];
        $this->assertEquals('foo=bar&empty=&withWhiteSpace=a%20b', FormUtil::toFormString($query));

        $object = json_decode(json_encode($query));
        $this->assertEquals('foo=bar&empty=&withWhiteSpace=a%20b', FormUtil::toFormString($object));
    }

    public function testFileFromStream()
    {
        $boundary = FormUtil::getBoundary();
        $stream   = FormUtil::toFileForm([], $boundary);
        $this->assertTrue($stream instanceof FileFormStream);
        $stream->write($boundary);
        $this->assertTrue(\strlen($boundary) === $stream->getSize());
    }

    public function testRead()
    {
        $fileField              = new FileField();
        $fileField->filename    = 'haveContent';
        $fileField->contentType = 'contentType';
        $fileField->content     = new Stream(fopen('data://text/plain;base64,' . base64_encode('This is file test. This sentence must be long'), 'r'));

        $fileFieldNoContent              = new FileField();
        $fileFieldNoContent->filename    = 'noContent';
        $fileFieldNoContent->contentType = 'contentType';
        $fileFieldNoContent->content     = null;

        $map = [
            'key'      => 'value',
            'testKey'  => 'testValue',
            'haveFile' => $fileField,
            'noFile'   => $fileFieldNoContent,
        ];

        $stream = FormUtil::toFileForm($map, 'testBoundary');

        $result = $stream->getContents();
        $target = "--testBoundary\r\nContent-Disposition: form-data; name=\"key\"\r\n\r\nvalue\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"testKey\"\r\n\r\ntestValue\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"haveFile\"; filename=\"haveContent\"\r\nContent-Type: contentType\r\n\r\nThis is file test. This sentence must be long\r\n--testBoundary--\r\n";

        $this->assertEquals($target, $result);
    }

    public function testReadFile()
    {
        $fileField              = new FileField();
        $fileField->filename    = 'composer.json';
        $fileField->contentType = 'application/json';
        $fileField->content     = new Stream(fopen(__DIR__ . '/../../composer.json', 'r'));
        $map                    = [
            'name'      => 'json_file',
            'type'      => 'application/json',
            'json_file' => $fileField,
        ];

        $boundary   = FormUtil::getBoundary();
        $fileStream = FormUtil::toFileForm($map, $boundary);
        $this->assertTrue(false !== strpos($fileStream->getContents(), 'json_file'));
    }
}