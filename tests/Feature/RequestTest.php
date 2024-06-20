<?php

namespace AlibabaCloud\Dara\Tests\Feature;

use AlibabaCloud\Dara\Request;
use AlibabaCloud\Dara\Dara;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class RequestTest extends TestCase
{
    public function testRequest()
    {
        $request                  = new Request('get', '');
        $request->protocol        = 'https';
        $request->headers['host'] = 'www.alibabacloud.com';
        $request->query           = [
            'a' => 'a',
            'b' => 'b',
        ];
        $result                   = Dara::send($request, [
            'readTimeout' => 300000
        ]);
        self::assertEquals(200, $result->getStatusCode());
    }

    public function testString()
    {
        $string = Dara::string('get', 'http://www.alibabacloud.com/');
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

        $res  = Dara::send($request);
        $data = json_decode((string) $res->getBody(), true);
        $this->assertEquals('this is body content', $data['data']);

        $bytes = [];
        for ($i = 0; $i < \strlen($data['data']); ++$i) {
            $bytes[] = \ord($data['data'][$i]);
        }
        $request->body = $bytes;
        $res  = Dara::send($request);
        $data = json_decode((string) $res->getBody(), true);
        $this->assertEquals('this is body content', $data['data']);
    }
}
