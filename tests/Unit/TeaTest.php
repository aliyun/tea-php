<?php

namespace AlibabaCloud\Tea\Tests\Unit;

use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Request;
use AlibabaCloud\Tea\Tea;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TeaTest extends TestCase
{
    public static function testAllowRetry()
    {
        $runtime    = [];
        $retryTimes = 2;
        self::assertFalse(Tea::allowRetry($runtime, $retryTimes, time()));

        $runtime['maxAttempts'] = 3;
        self::assertTrue(Tea::allowRetry($runtime, $retryTimes, time()));
    }

    public static function testGetBackoffTime()
    {
        $runtime    = [];
        $retryTimes = 3;
        self::assertEquals(0, Tea::getBackoffTime($runtime, $retryTimes));

        $runtime['policy'] = 'yes';
        self::assertEquals(0, Tea::getBackoffTime($runtime, $retryTimes));

        $runtime['period'] = 0;
        self::assertEquals(3, Tea::getBackoffTime($runtime, $retryTimes));

        $runtime['period'] = -1;
        self::assertEquals(3, Tea::getBackoffTime($runtime, $retryTimes));

        $runtime['period'] = 1;
        self::assertEquals(1, Tea::getBackoffTime($runtime, $retryTimes));
    }

    public static function testSleep()
    {
        $before = time();
        Tea::sleep(1);
        $after = time();

        self::assertTrue($after - $before >= 1);
    }

    public static function testIsRetryable()
    {
        $exception = new TeaError([
            'data'    => [],
            'message' => 'error message',
        ], 'error message', '500');
        self::assertEquals(500, $exception->getCode());

        $retry = ['maxAttempts' => 3];
        self::assertTrue(Tea::isRetryable($retry, 1));

        $errorInfo = $exception->getErrorInfo();
        self::assertEquals('error message', $errorInfo['message']);
    }

    public static function testMerge()
    {
        self::assertEquals([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ], Tea::merge(
            ['a' => 1],
            null,
            ['b' => 2],
            null,
            ['c' => 3]
        ));
        self::assertEquals([
            'A' => 'a',
            'b' => 'b',
            'c' => '',
        ], Tea::merge(
            new ModelMock()
        ));
        self::assertEquals([], Tea::merge(null, 1, true, 'string'));

        self::assertEquals([], Tea::merge());
    }

    public static function testRequest()
    {
        ini_set('xdebug.max_nesting_level', 100);
        $count = 102;
        while ($count > 0) {
            $request                  = new Request();
            $request->method          = 'GET';
            $request->protocol        = 'http';
            $request->headers['host'] = 'example.com';
            $request->port            = 80;
            Tea::send($request);
            --$count;
        }
        // No Exception is OK
        self::assertTrue(true);
    }

    public static function testProxy()
    {
        $request = new Request('GET', 'https://next.api.aliyun.com/home');
        Tea::send($request); // not throw exception

        try {
            Tea::send($request, [
                'httpsProxy'  => 'http://127.0.0.1:1234',
                'readTimeout' => 3000,
            ]);
            // cannot be here
            self::assertTrue(false);
        } catch (\Exception $e) {
            self::assertEquals('cURL error 7: Failed to connect to 127.0.0.1 port 1234: Connection refused (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://next.api.aliyun.com/', $e->getMessage());
        }

        try {
            $request           = new Request('GET', 'http://next.api.aliyun.com/home');
            $request->protocol = 'http';
            Tea::send($request, [
                'httpProxy'   => 'http://127.0.0.1:1234',
                'readTimeout' => 3000,
            ]);
            // cannot be here
            self::assertTrue(false);
        } catch (\Exception $e) {
            self::assertEquals('cURL error 7: Failed to connect to 127.0.0.1 port 1234: Connection refused (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for http://next.api.aliyun.com/', $e->getMessage());
        }
    }
}
