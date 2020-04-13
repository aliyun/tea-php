<?php

namespace AlibabaCloud\Tea\Tests\Feature;

use AlibabaCloud\Tea\Request;
use AlibabaCloud\Tea\Tea;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestTest.
 *
 * @internal
 * @coversNothing
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
    }
}
