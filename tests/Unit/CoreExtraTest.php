<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Dara;
use AlibabaCloud\Dara\Exception\DaraException;
use AlibabaCloud\Dara\Helper;
use AlibabaCloud\Dara\Model;
use AlibabaCloud\Dara\Request;
use AlibabaCloud\Dara\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;

class CoreExtraTest extends TestCase
{
    public function testRequestBranches()
    {
        $request = new Request('POST', 'http://ignored');
        $request->protocol = 'http';
        $request->pathname = '/v1/test';
        $request->port = 8080;
        $request->query = ['a' => 'b', 'c' => 'd e'];
        $request->headers = [
            'host' => 'example.com',
            'content-type' => 'text/plain',
        ];
        $request->body = 'hello';
        $psr = $request->getPsrRequest();
        $this->assertEquals('http', $psr->getUri()->getScheme());
        $this->assertEquals(8080, $psr->getUri()->getPort());
        $this->assertEquals('/v1/test', $psr->getUri()->getPath());
        $this->assertTrue(false !== strpos($psr->getUri()->getQuery(), 'a=b'));
        $this->assertEquals('hello', (string) $psr->getBody());

        $request->body = [104, 105];
        $this->assertEquals('hi', (string) $request->getPsrRequest()->getBody());

        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'stream-body');
        rewind($resource);
        $request->body = new Stream($resource);
        $this->assertEquals('stream-body', (string) $request->getPsrRequest()->getBody());

        $request->query = null;
        $request->body = '';
        $this->assertEquals('', $request->getPsrRequest()->getUri()->getQuery());

        try {
            $request->query = 'bad';
            $request->getPsrRequest();
            $this->fail('expected invalid query');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'Query must be array'));
        }
    }

    public function testResponseRemainingBranches()
    {
        $sse = new Response(new PsrResponse(200, [
            'Content-Type' => 'text/event-stream',
        ], "data: hi\n\n"));
        $this->assertEquals(200, $sse->statusCode);

        $plain = new Response(new PsrResponse(200, [], 'not-json'));
        $this->assertNull($plain->__get('missing'));
        $this->assertEquals('not-json', (string) $plain);

        $json = new Response(new PsrResponse(200, [], '{"AppId":"test","list":[1]}'));
        $json->mergeRecursive('list', [2]);
        $this->assertEquals([1, 2], $json->get('list'));
        $items = ['replaced' => true];
        $json->setReference($items);
        $this->assertTrue($json->get('replaced'));
        $seen = [];
        foreach ($json->getIterator() as $key => $value) {
            $seen[$key] = $value;
        }
        $this->assertArrayHasKey('replaced', $seen);
        $this->assertEquals(1, $json->count());
    }

    public function testHelperAndModelGaps()
    {
        $this->assertSame('', Helper::findFromString('no prefix', '@real', "\n"));
        $this->assertFalse(Helper::isBytes([1 => 65]));
        $this->assertFalse(Helper::isBytes(['x']));
        $this->assertFalse(Helper::isBytes([300]));
        $this->assertTrue(Helper::isBytes([]));
        $this->assertEquals([
            'nested' => ['a' => 1, 'b' => 2],
        ], Helper::merge([
            ['nested' => ['a' => 1]],
            ['nested' => ['b' => 2]],
        ]));

        $model = new CoverageMapModel();
        $this->assertEquals('A', $model->getName('a'));
        $this->assertEquals('missing', $model->getName('missing'));
        $this->assertTrue(is_array($model->getName()));

        $model->name = 'tea';
        $copy = $model->copyWithoutStream();
        $this->assertEquals('tea', $copy->name);

        $plain = new CoveragePlainModel();
        $this->assertNull($plain->copyWithoutStream());

        Model::validateArray(null);
        Model::validateArray([
            [new CoverageMapModel()],
        ]);
        $this->assertTrue(true);
    }

    public function testDaraRetryAndMockHttp()
    {
        $this->assertTrue(Dara::isRetryable(new DaraException(['message' => 'x'], 'x')));
        $this->assertTrue(Dara::isRetryable([]));
        $this->assertFalse(Dara::isRetryable(['maxAttempts' => 1], 2));
        $this->assertFalse(Dara::isRetryable('nope'));
        $this->assertEquals(0, Dara::getBackoffTime(['policy' => 'no'], 2));
        $this->assertFalse(Dara::allowRetry([
            'retryable' => true,
            'maxAttempts' => 0,
        ], 1, time()));

        $object = new \stdClass();
        $object->a = 1;
        $this->assertEquals(['a' => 1], Dara::merge($object));

        $mock = new MockHandler([
            new PsrResponse(200, ['X-Foo' => 'bar'], '{"ok":1}'),
            new PsrResponse(200, ['X-Foo' => 'bar'], 'body-text'),
            new PsrResponse(200, ['X-Foo' => 'bar'], ''),
            new PsrResponse(200, ['X-Foo' => 'bar'], 'async-body'),
            new PsrResponse(200, ['X-Foo' => 'bar'], 'requested'),
            new PsrResponse(200, ['X-Foo' => 'bar'], 'string-body'),
            new PsrResponse(200, ['X-Foo' => 'bar'], 'async'),
            new PsrResponse(200, ['X-Foo' => 'bar'], ''),
            new PsrResponse(200, ['X-Foo' => 'bar'], ''),
            new PsrResponse(204, [], ''),
        ]);
        Dara::config([
            'handler' => HandlerStack::create($mock),
            'on_stats' => function () {
            },
        ]);

        try {
            $request = new Request('GET', 'http://example.com/item');
            $request->headers['host'] = 'example.com';
            $response = Dara::send($request, [
                'httpProxy' => 'http://127.0.0.1:9',
                'httpsProxy' => 'http://127.0.0.1:9',
                'noProxy' => 'example.com',
                'ignoreSSL' => true,
                'stream' => true,
                'readTimeout' => 1500,
                'connectTimeout' => 1500,
            ]);
            $this->assertEquals(1, $response->get('ok'));

            Dara::send($request, [
                'httpProxy' => '',
                'httpsProxy' => '',
                'noProxy' => '',
                'ignoreSSL' => false,
                'stream' => false,
                'readTimeout' => 0,
                'connectTimeout' => 0,
            ]);
            Dara::send(new Request('GET', 'http://example.com/plain'));

            $async = Dara::sendAsync(new \GuzzleHttp\Psr7\Request('GET', 'http://example.com/async'));
            $this->assertEquals('async-body', (string) $async->wait()->getBody());

            $this->assertEquals(200, Dara::request('GET', 'http://example.com/r')->getStatusCode());
            $this->assertEquals('string-body', Dara::string('GET', 'http://example.com/s'));
            $this->assertEquals(200, Dara::requestAsync('GET', 'http://example.com/ra')->wait()->getStatusCode());
            $headers = Dara::getHeaders('http://example.com/h');
            $this->assertTrue(isset($headers['X-Foo']) || isset($headers['x-foo']));
            $headerName = isset($headers['X-Foo']) ? 'X-Foo' : 'x-foo';
            $this->assertEquals('bar', Dara::getHeader('http://example.com/h2', $headerName, 'default'));
            $this->assertEquals('fallback', Dara::getHeader('http://example.com/h3', 'Missing', 'fallback'));
            $this->assertInstanceOf(\GuzzleHttp\Client::class, Dara::client());
        } finally {
            Dara::config([]);
        }
    }
}

class CoverageMapModel extends Model
{
    public $a = 'a';
    public $name;

    public function __construct()
    {
        $this->_name['a'] = 'A';
        parent::__construct();
    }

    public function toArray($ignoreStream = false)
    {
        return ['name' => $this->name];
    }

    public static function fromMap($map = [])
    {
        $model = new self();
        $model->name = isset($map['name']) ? $map['name'] : null;

        return $model;
    }
}

class CoveragePlainModel extends Model
{
    public function toArray($ignoreStream = false)
    {
        return [];
    }
}
