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
        $request                  = new Request('get', '');
        $request->protocol        = 'https';
        $request->headers['host'] = 'www.baidu.com';
        $request->query           = [
            'a' => 'a',
            'b' => 'b',
        ];
        $result                   = Tea::send($request);
        self::assertEquals(200, $result->getStatusCode());
    }
}
