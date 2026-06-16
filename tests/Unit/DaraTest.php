<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Exception\DaraException;
use AlibabaCloud\Dara\Exception\DaraRespException;
use AlibabaCloud\Dara\Models\RuntimeOptions;
use AlibabaCloud\Dara\Models\ExtendsParameters;
use AlibabaCloud\Dara\Request;
use AlibabaCloud\Dara\Dara;
use PHPUnit\Framework\TestCase;
use AlibabaCloud\Dara\Model;

/**
 * @internal
 */
class DaraTest extends TestCase
{
    public static function testAllowRetry()
    {
        $runtime    = [];
        $retryTimes = 2;
        self::assertFalse(Dara::allowRetry($runtime, $retryTimes, time()));

        $runtime['maxAttempts'] = 1;
        self::assertFalse(Dara::allowRetry($runtime, $retryTimes, time()));

        $retryTimes = 0;
        self::assertTrue(Dara::allowRetry($runtime, $retryTimes, time()));

        $retryTimes = null;
        self::assertFalse(Dara::allowRetry($runtime, $retryTimes, time()));

        $retryTimes = 'test';
        self::assertFalse(Dara::allowRetry($runtime, $retryTimes, time()));

        $retryTimes = 2;
        $runtime['retryable'] = false;
        self::assertFalse(Dara::allowRetry($runtime, $retryTimes, time()));

        $runtime['retryable'] = true;
        self::assertFalse(Dara::allowRetry($runtime, $retryTimes, time()));

        $runtime['retryable'] = true;
        $runtime['maxAttempts'] = 3;
        self::assertTrue(Dara::allowRetry($runtime, $retryTimes, time()));
    }

    public static function testGetBackoffTime()
    {
        $runtime    = [];
        $retryTimes = 3;
        self::assertEquals(0, Dara::getBackoffTime($runtime, $retryTimes));

        $runtime['policy'] = 'yes';
        self::assertEquals(0, Dara::getBackoffTime($runtime, $retryTimes));

        $runtime['period'] = 0;
        self::assertEquals(3, Dara::getBackoffTime($runtime, $retryTimes));

        $runtime['period'] = -1;
        self::assertEquals(3, Dara::getBackoffTime($runtime, $retryTimes));

        $runtime['period'] = 1;
        self::assertEquals(1, Dara::getBackoffTime($runtime, $retryTimes));
    }

    public static function testSleep()
    {
        $before = time();
        Dara::sleep(1);
        $after = time();

        self::assertTrue($after - $before >= 1);
    }

    public static function testIsRetryable()
    {
        $exception = new DaraException([
            'message' => 'error message',
            'errCode' => 'TestError'
        ], 'error message');
        self::assertEquals('TestError', $exception->getErrCode());

        $retry = ['maxAttempts' => 3];
        self::assertTrue(Dara::isRetryable($retry, 1));

        $errorInfo = $exception->getErrorInfo();
        self::assertEquals('error message', $errorInfo['message']);
    }

    public static function testMerge()
    {
        self::assertEquals([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ], Dara::merge(
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
        ], Dara::merge(
            new ModelMock()
        ));
        self::assertEquals([], Dara::merge(null, 1, true, 'string'));

        self::assertEquals([], Dara::merge());
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
            Dara::send($request);
            --$count;
        }
        // No Exception is OK
        self::assertTrue(true);
    }

    public static function testProxy()
    {
        $request = new Request('GET', 'https://next.api.aliyun.com/home');
        Dara::send($request); // not throw exception

        try {
            Dara::send($request, [
                'httpsProxy'  => 'http://127.0.0.1:1234',
                'readTimeout' => 3000,
            ]);
            // cannot be here
            self::assertTrue(false);
        } catch (\Exception $e) {
            self::assertTrue(0 === strpos($e->getMessage(), 'cURL error 7: Failed to connect to 127.0.0.1 port 1234'));
        }

        try {
            $request           = new Request('GET', 'http://next.api.aliyun.com/home');
            $request->protocol = 'http';
            Dara::send($request, [
                'httpProxy'   => 'http://127.0.0.1:1234',
                'readTimeout' => 3000,
            ]);
            // cannot be here
            self::assertTrue(false);
        } catch (\Exception $e) {
            self::assertTrue(0 === strpos($e->getMessage(), 'cURL error 7: Failed to connect to 127.0.0.1 port 1234'));
        }
    }
    public static function testIgnoreSSL()
    {
        $request = new Request('GET', 'https://next.api.aliyun.com/home');
        //When ignoreSSL is '', null, 0, false, GuzzleHttp verify is true, otherwise GuzzleHttp verify is false.
        Dara::send($request,['ignoreSSL' => true]);
        Dara::send($request,['ignoreSSL' => '']);
        Dara::send($request,['ignoreSSL' => 'true']);
        Dara::send($request,['ignoreSSL' => null]);
        Dara::send($request,['ignoreSSL' => 1]);
        Dara::send($request,['ignoreSSL' => 0]);
        Dara::send($request,['ignoreSSL' => [true]]);
        Dara::send($request,['ignoreSSL' => [false]]);
    }

    public static function testDaraException()
    {
        $exception = new DaraException([
            'message' => 'error message',
            'errCode' => 'TestErr',
        ], 'error message');
        self::assertEquals('TestErr', $exception->getErrCode());
        self::assertEquals('error message', $exception->getMessage());

        $exception = new DaraRespException([
            'errCode'               => 'error code',
            'message'            => 'error message',
            'data'               => [
                'statusCode'  => 200,
                'description' => 'description'
            ],
            'description'        => 'error description',
            'accessDeniedDetail' => [
                'AuthAction'        => 'ram:ListUsers',
                'AuthPrincipalType' => 'SubUser',
                'PolicyType'        => 'ResourceGroupLevelIdentityBassdPolicy',
                'NoPermissionType'  => 'ImplicitDeny'
            ]
        ]);
        $data = $exception->getData();
        $detail = $exception->getAccessDeniedDetail();
        self::assertEquals('error code', $exception->getErrCode());
        self::assertEquals('error message', $exception->getMessage());
        self::assertEquals(200, $exception->getStatusCode());
        self::assertEquals(200, $data['statusCode']);
        self::assertEquals('error description', $exception->getDescription());
        self::assertEquals('ImplicitDeny', $detail['NoPermissionType']);
    }

    public function testRuntimeOptions()
    {
        $opts = new RuntimeOptions([
            "autoretry" => false,
            "ignoreSSL" => false,
            "key" => "key",
            "cert" => "cert",
            "ca" => "ca",
            "maxAttempts" => 3,
            "backoffPolicy" => "backoffPolicy",
            "backoffPeriod" => 10,
            "readTimeout" => 3000,
            "connectTimeout" => 3000,
            "httpProxy" => "httpProxy",
            "httpsProxy" => "httpsProxy",
            "noProxy" => "noProxy",
            "maxIdleConns" => 300,
            "keepAlive" => true,
            "extendsParameters" => new ExtendsParameters([
                "headers" => ['key' => 'value'],
            ]),
        ]);
        $this->assertEquals(false, $opts->autoretry);
        $this->assertEquals(false, $opts->ignoreSSL);
        $this->assertEquals("key", $opts->key);
        $this->assertEquals("cert", $opts->cert);
        $this->assertEquals("ca", $opts->ca);
        $this->assertEquals(3, $opts->maxAttempts);
        $this->assertEquals("backoffPolicy", $opts->backoffPolicy);
        $this->assertEquals(10, $opts->backoffPeriod);
        $this->assertEquals(3000, $opts->readTimeout);
        $this->assertEquals(3000, $opts->connectTimeout);
        $this->assertEquals("httpProxy", $opts->httpProxy);
        $this->assertEquals("httpsProxy", $opts->httpsProxy);
        $this->assertEquals("noProxy", $opts->noProxy);
        $this->assertEquals(300, $opts->maxIdleConns);
        $this->assertEquals(true, $opts->keepAlive);
        $this->assertEquals('value', $opts->extendsParameters->headers['key']);
    }
}

class ModelMock extends Model
{
    public $a = 'a';
    public $b = 'b';
    public $c = '';

    public function __construct()
    {
        $this->_name['a']     = 'A';
        $this->_required['c'] = true;
        parent::__construct();
    }
}