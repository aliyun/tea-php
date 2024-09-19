<?php

namespace AlibabaCloud\Tea\Tests\Unit;

use AlibabaCloud\Tea\Request;
use PHPUnit\Framework\TestCase;
use AlibabaCloud\Tea\Tea;

/**
 * Class RequestTest.
 *
 * @internal
 */
class RequestTest extends TestCase
{
    public static function testGetPsrRequest()
    {
        $request                  = new Request('get', '');
        $request->protocol        = 'https';
        $request->headers['host'] = 'www.alibaba.com';
        $psrRequest               = $request->getPsrRequest();
        self::assertEquals('https://www.alibaba.com/', (string) $psrRequest->getUri());
        self::assertInstanceOf(\GuzzleHttp\Psr7\Request::class, $psrRequest);
    }

    public function testRequest()
    {
        $request                  = new Request('get', '');
        $request->protocol        = 'https';
        $request->headers['host'] = 'www.alibabacloud.com';
        $request->query           = [
            'a' => 'a',
            'b' => 'b',
        ];
        $result                   = Tea::send($request);
        self::assertEquals(200, $result->getStatusCode());
    }

    public function testString()
    {
        $string = Tea::string('get', 'http://www.alibabacloud.com/');
        self::assertNotFalse(strpos($string, '<link rel="dns-prefetch" href="//g.alicdn.com">'));
    }

    public function testRequestWithBody()
    {
        $request                  = new Request();
        $request->method          = 'POST';
        $request->protocol        = 'https';
        $request->headers['host'] = 'httpbin.org';
        $request->body            = 'this is body content';
        $request->pathname        = '/post';

        $res  = Tea::send($request);
        $data = json_decode((string) $res->getBody(), true);
        $this->assertEquals('this is body content', $data['data']);

        $bytes = [];
        for ($i = 0; $i < \strlen($data['data']); ++$i) {
            $bytes[] = \ord($data['data'][$i]);
        }
        $request->body = $bytes;
        $res  = Tea::send($request);
        $data = json_decode((string) $res->getBody(), true);
        $this->assertEquals('this is body content', $data['data']);
    }
}
