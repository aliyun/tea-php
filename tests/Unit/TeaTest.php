<?php

namespace AlibabaCloud\Tea\Tests\Unit;

use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Tea;
use PHPUnit\Framework\TestCase;

class TeaTest extends TestCase
{
    public static function testAllowRetry()
    {
        $runtime    = [];
        $retryTimes = 2;
        self::assertFalse(Tea::allowRetry($runtime, $retryTimes, time()));

        $runtime["maxAttempts"] = 3;
        self::assertTrue(Tea::allowRetry($runtime, $retryTimes, time()));
    }

    public static function testGetBackoffTime()
    {
        $runtime    = [];
        $retryTimes = 3;
        self::assertEquals(0, Tea::getBackoffTime($runtime, $retryTimes));

        $runtime["policy"] = "yes";
        self::assertEquals(0, Tea::getBackoffTime($runtime, $retryTimes));

        $runtime["period"] = 0;
        self::assertEquals(3, Tea::getBackoffTime($runtime, $retryTimes));

        $runtime["period"] = -1;
        self::assertEquals(3, Tea::getBackoffTime($runtime, $retryTimes));

        $runtime["period"] = 1;
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
            "data"    => [],
            "message" => "error message"
        ]);
        self::assertTrue(Tea::isRetryable($exception));

        $errorInfo = $exception->getErrorInfo();
        self::assertEquals("error message", $errorInfo["message"]);
    }
}
