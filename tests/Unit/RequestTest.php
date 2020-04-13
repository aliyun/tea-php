<?php

namespace AlibabaCloud\Tea\Tests\Unit;

use AlibabaCloud\Tea\Request;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestTest.
 *
 * @internal
 * @coversNothing
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
}
