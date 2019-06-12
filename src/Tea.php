<?php

namespace HttpX\Tea;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use HttpX\Tea\Exception\TeaError;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Tea
 *
 * @package HttpX\Tea
 */
class Tea
{
    /**
     * @var array
     */
    private static $config = [];

    /**
     * @param array $config
     */
    public static function config(array $config)
    {
        self::$config = $config;
    }

    /**
     * @param Request $request
     * @param array   $config
     *
     * @return Response
     */
    public static function doRequest(Request $request, array $config = [])
    {
        return self::doPsrRequest($request->getPsrRequest(), $config);
    }

    /**
     * @param Request $request
     * @param array   $config
     *
     * @return PromiseInterface
     */
    public static function doRequestAsync(Request $request, array $config = [])
    {
        return self::doPsrRequestAsync($request->getPsrRequest(), $config);
    }

    /**
     * @param RequestInterface $request
     *
     * @param array            $config
     *
     * @return Response
     */
    public static function doPsrRequest(RequestInterface $request, array $config = [])
    {
        $config['http_errors'] = false;

        try {
            return self::client()->send(
                $request,
                $config
            );
        } catch (GuzzleException $e) {
            throw new TeaError(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param RequestInterface $request
     *
     * @param array            $config
     *
     * @return PromiseInterface
     */
    public static function doPsrRequestAsync(RequestInterface $request, array $config = [])
    {
        $config['http_errors'] = false;

        return self::client()->sendAsync(
            $request,
            $config
        );
    }

    /**
     * @return Client
     */
    public static function client()
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::mapResponse(static function (ResponseInterface $response) {
            return new Response($response);
        }));

        self::$config['handler'] = $stack;

        return new Client(self::$config);
    }

    /**
     * @param array $retry
     * @param int   $retryTimes
     * @param float $now
     *
     * @return bool
     */
    public static function allowRetry(array $retry, $retryTimes, $now)
    {
        $retryable   = $retry['retryable'];
        $policy      = $retry['policy'];
        $maxAttempts = $retry['max-attempts'];

        if ($retryable !== true) {
            return false;
        }

        if ($maxAttempts <= $retryTimes) {
            return false;
        }

        return true;
    }

    /**
     * @param array $backoff
     * @param int   $retryTimes
     *
     * @return int
     */
    public static function getBackoffTime(array $backoff, $retryTimes)
    {
        list($policy, $period) = $backoff;

        return 1 * 1000;
    }
}
