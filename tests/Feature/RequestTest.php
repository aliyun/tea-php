<?php

namespace HttpX\Tea\Tests\Feature;

use HttpX\Tea\Tea;
use HttpX\Tea\Request;
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
        $request                  = new Request();
        $request->method          = 'get';
        $request->protocol        = 'https';
        $request->headers['host'] = 'www.alibaba.com';
        $result                   = Tea::doRequest($request);

        self::assertEquals(200, $result->getStatusCode());
    }
}
