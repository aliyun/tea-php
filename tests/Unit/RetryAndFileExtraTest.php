<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Date;
use AlibabaCloud\Dara\Dara;
use AlibabaCloud\Dara\Exception\DaraException;
use AlibabaCloud\Dara\File;
use AlibabaCloud\Dara\RetryPolicy\BackoffPolicy;
use AlibabaCloud\Dara\RetryPolicy\ExponentialBackoffPolicy;
use AlibabaCloud\Dara\RetryPolicy\FixedBackoffPolicy;
use AlibabaCloud\Dara\RetryPolicy\FullJitterBackoffPolicy;
use AlibabaCloud\Dara\RetryPolicy\RandomBackoffPolicy;
use AlibabaCloud\Dara\RetryPolicy\RetryOptions;
use AlibabaCloud\Dara\RetryPolicy\RetryPolicyContext;
use PHPUnit\Framework\TestCase;

class RetryAndFileExtraTest extends TestCase
{
    public function testBackoffPolicies()
    {
        $context = new RetryPolicyContext([
            'retriesAttempted' => 2,
            'key' => 'k',
        ]);

        $fixed = new FixedBackoffPolicy(['policy' => 'Fixed', 'period' => 15]);
        $this->assertEquals(15, $fixed->getDelayTime($context));

        $random = new RandomBackoffPolicy(['policy' => 'Random', 'period' => 10]);
        $delay = $random->getDelayTime($context);
        $this->assertTrue($delay >= 0 && $delay <= 20000);

        $exponential = new ExponentialBackoffPolicy(['policy' => 'Exponential', 'period' => 3]);
        $this->assertEquals(12, $exponential->getDelayTime($context));

        $full = new FullJitterBackoffPolicy(['policy' => 'FullJitter', 'period' => 4]);
        $fullDelay = $full->getDelayTime($context);
        $this->assertTrue($fullDelay >= 0 && $fullDelay <= 16);

        $equal = BackoffPolicy::newBackoffPolicy([
            'policy' => 'ExponentialWithEqualJitter',
            'period' => 5,
        ]);
        $equalDelay = $equal->getDelayTime($context);
        $this->assertTrue($equalDelay >= 10 && $equalDelay <= 20);

        $this->expectException(DaraException::class);
        BackoffPolicy::newBackoffPolicy(['policy' => 'Unknown']);
    }

    public function testMissingPeriod()
    {
        foreach ([
            'AlibabaCloud\\Dara\\RetryPolicy\\FixedBackoffPolicy',
            'AlibabaCloud\\Dara\\RetryPolicy\\RandomBackoffPolicy',
        ] as $class) {
            try {
                new $class(['policy' => 'Fixed']);
                $this->fail($class . ' should require period');
            } catch (DaraException $e) {
                $this->assertTrue(false !== strpos($e->getMessage(), 'Period'));
            }
        }

        set_error_handler(function () {
            return true;
        });
        try {
            foreach ([
                'AlibabaCloud\\Dara\\RetryPolicy\\ExponentialBackoffPolicy',
                'AlibabaCloud\\Dara\\RetryPolicy\\FullJitterBackoffPolicy',
            ] as $class) {
                try {
                    new $class(['policy' => 'Exponential']);
                    $this->fail($class . ' should require period');
                } catch (\Exception $e) {
                    $this->assertTrue($e instanceof \Exception);
                }
            }
        } finally {
            restore_error_handler();
        }
    }

    public function testRetryOptionsFromArrays()
    {
        $options = new RetryOptions([
            'retryable' => false,
            'retryCondition' => [[
                'maxAttempts' => 1,
                'exception' => ['AEx'],
                'errorCode' => ['E'],
                'maxDelay' => 10,
            ]],
            'noRetryCondition' => [[
                'exception' => ['BEx'],
            ]],
        ]);
        $this->assertFalse($options->getRetryable());
        $this->assertEquals(1, $options->getRetryCondition()[0]->getMaxAttempts());
        $this->assertEquals(['BEx'], $options->getNoRetryCondition()[0]->getException());
        $this->assertFalse(Dara::shouldRetry($options, new RetryPolicyContext([
            'retriesAttempted' => 1,
            'exception' => new DaraException(['name' => 'AEx', 'errCode' => 'E'], 'e'),
        ])));
    }

    public function testDateDiffAndTimestamp()
    {
        $start = new Date(1700000000);
        $this->assertEquals(1700000000, $start->unix());
        $numeric = new Date('1700000000');
        $this->assertEquals(1700000000, $numeric->unix());

        $end = new Date('2024-03-02 13:14:15');
        $begin = new Date('2023-01-01 10:11:12');
        $this->assertEquals(1, $begin->diff($end, 'year'));
        $this->assertEquals(2, $begin->diff($end, 'month'));
        $this->assertTrue($begin->diff($end, 'day') >= 0);
        $this->assertEquals(3, $begin->diff($end, 'hour'));
        $this->assertEquals(3, $begin->diff($end, 'minute'));
        $this->assertEquals(3, $begin->diff($end, 'second'));
        $this->assertInstanceOf(\DateTime::class, $begin->getDateObject());
    }

    public function testFileCachedStatAndClose()
    {
        $path = tempnam(sys_get_temp_dir(), 'dara');
        file_put_contents($path, 'abc');
        try {
            $file = new File($path);
            $this->assertEquals(3, $file->length());
            $this->assertInstanceOf(Date::class, $file->createTime());
            $this->assertInstanceOf(Date::class, $file->modifyTime());
            $this->assertEquals('a', $file->read(1));
            $file->close();
            $file->close();
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
