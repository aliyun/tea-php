<?php

namespace HttpX\Tea;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
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
     * @return Client
     */
    public static function client()
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::mapResponse(static function(ResponseInterface $response) {
            return new Response($response);
        }));

        self::$config['handler'] = $stack;

        return new Client(self::$config);
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
     * @param string     $uri
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public static function getHeader($uri, $key, $default = null)
    {
        $headers = self::getHeaders($uri);

        return isset($headers[$key][0]) ? $headers[$key][0] : $default;
    }

    /**
     * @param string $uri
     *
     * @return mixed|null
     */
    public static function getHeaders($uri)
    {
        return self::request('HEAD', $uri)->getHeaders();
    }

    /**
     * @param string $method
     * @param string $uri
     *
     * @return Response
     */
    public static function request($method, $uri)
    {
        $request = new Request($method, $uri);

        return self::doPsrRequest($request);
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
