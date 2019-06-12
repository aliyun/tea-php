<?php

namespace HttpX\Tea\Tests\Unit;

use HttpX\Tea\Request;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestTest
 *
 * @package HttpX\Tea\Tests\Unit
 */
class RequestTest extends TestCase
{
    public static function testGetPsrRequest()
    {
        $request                  = new Request();
        $request->method          = 'get';
        $request->protocol        = 'https';
        $request->headers['host'] = 'www.alibaba.com';
        $psrRequest               = $request->getPsrRequest();
        self::assertEquals('https://www.alibaba.com/', (string)$psrRequest->getUri());
        self::assertInstanceOf(\GuzzleHttp\Psr7\Request::class, $psrRequest);
    }
}
