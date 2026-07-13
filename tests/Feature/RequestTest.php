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
    private function skipOnNetworkFailure($e)
    {
        $message = $e->getMessage();
        if (
            false !== stripos($message, 'timed out')
            || false !== stripos($message, 'Could not resolve')
            || false !== stripos($message, 'Failed to connect')
            || false !== stripos($message, 'Connection refused')
            || false !== stripos($message, 'cURL error')
            || false !== stripos($message, 'SSL')
        ) {
            $this->markTestSkipped('External network unavailable: ' . $message);
        }

        throw $e;
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
        try {
            $result = Dara::send($request, [
                'readTimeout' => 300000
            ]);
        } catch (\Exception $e) {
            $this->skipOnNetworkFailure($e);
        }
        self::assertEquals(200, $result->getStatusCode());
    }

    public function testString()
    {
        try {
            $string = Dara::string('get', 'http://www.alibabacloud.com/');
        } catch (\Exception $e) {
            $this->skipOnNetworkFailure($e);
        }
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

        try {
            $res  = Dara::send($request);
            $data = json_decode((string) $res->getBody(), true);
            if (!is_array($data) || !isset($data['data'])) {
                $this->markTestSkipped('httpbin.org returned unexpected response');
            }
            $this->assertEquals('this is body content', $data['data']);

            $bytes = [];
            for ($i = 0; $i < \strlen($data['data']); ++$i) {
                $bytes[] = \ord($data['data'][$i]);
            }
            $request->body = $bytes;
            $res  = Dara::send($request);
            $data = json_decode((string) $res->getBody(), true);
            if (!is_array($data) || !isset($data['data'])) {
                $this->markTestSkipped('httpbin.org returned unexpected response');
            }
            $this->assertEquals('this is body content', $data['data']);
        } catch (\Exception $e) {
            $this->skipOnNetworkFailure($e);
        }
    }
}
