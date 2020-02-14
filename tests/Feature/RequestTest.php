<?php

namespace HttpX\Tea\Tests\Feature;

use GuzzleHttp\Exception\GuzzleException;
use HttpX\Tea\Request;
use HttpX\Tea\Tea;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestTest
 *
 * @package HttpX\Tea\Tests\Feature
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

    /**
     * @throws GuzzleException
     */
    public function testString()
    {
        $string = Tea::string('get', 'http://www.alibabacloud.com/');
        self::assertNotFalse(strpos($string, '<link rel="dns-prefetch" href="//g.alicdn.com">'));
    }
}
