<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Exception\DaraException;
use AlibabaCloud\Dara\Exception\DaraRespException;
use AlibabaCloud\Dara\Exception\DaraUnableRetryException;
use AlibabaCloud\Dara\Request;
use AlibabaCloud\Dara\RetryPolicy\RetryPolicyContext;
use PHPUnit\Framework\TestCase;

class ExceptionExtraTest extends TestCase
{
    public function testDaraExceptionAccessors()
    {
        $exception = new DaraException([
            'name' => 'Custom',
            'message' => 'failed',
            'errCode' => 'E1',
            'data' => ['statusCode' => 400],
            'description' => 'desc',
            'accessDeniedDetail' => ['NoPermissionType' => 'Deny'],
        ], 'failed', 400);
        $this->assertEquals('Custom', $exception->getName());
        $this->assertEquals('E1', $exception->getErrCode());
        $this->assertEquals('failed', $exception->getErrorInfo()['message']);

        $response = new DaraRespException([
            'message' => 'limited',
            'errCode' => 'Throttling',
            'retryAfter' => 8,
            'statusCode' => 429,
            'description' => 'slow down',
            'accessDeniedDetail' => ['PolicyType' => 'Account'],
            'data' => ['statusCode' => 429, 'description' => 'body'],
        ], 'limited', 429);
        $this->assertEquals(8, $response->getRetryAfter());
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals('slow down', $response->getDescription());
        $this->assertEquals('Account', $response->getAccessDeniedDetail()['PolicyType']);
        $this->assertEquals(429, $response->getData()['statusCode']);
        $this->assertEquals('limited', $response->getErrorInfo()['message']);
    }

    public function testUnableRetryException()
    {
        $request = new Request('GET', 'http://example.com');
        $cause = new DaraException([
            'message' => 'boom',
            'errCode' => 'Boom',
        ], 'boom', 500);

        $wrapped = new DaraUnableRetryException($request, $cause);
        $this->assertSame($request, $wrapped->getLastRequest());
        $this->assertSame($cause, $wrapped->getLastException());
        $this->assertEquals('Boom', $wrapped->getErrCode());

        $plain = new DaraUnableRetryException($request, new \RuntimeException('plain', 12));
        $this->assertEquals('plain', $plain->getLastException()->getMessage());

        $context = new RetryPolicyContext([
            'httpRequest' => $request,
            'exception' => $cause,
            'retriesAttempted' => 2,
            'key' => 'retry-key',
        ]);
        $fromContext = new DaraUnableRetryException($context);
        $this->assertSame($request, $fromContext->getLastRequest());
        $this->assertSame($cause, $fromContext->getLastException());
        $this->assertEquals('retry-key', $context->getKey());
        $this->assertSame($request, $context->getHttpRequest());
        $this->assertNull($context->getHttpResponse());
        $this->assertEquals(2, $context->getRetryCount());
    }
}
