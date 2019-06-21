<?php

namespace HttpX\Tea;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use Songshenzong\Support\Arrays;
use HttpX\Tea\Exception\TeaError;
use Psr\Http\Message\UriInterface;
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
     * @param array $config
     *
     * @return Client
     */
    public static function client(array $config = [])
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::mapResponse(static function (ResponseInterface $response) {
            return new Response($response);
        }));

        self::$config['handler'] = $stack;

        $new_config = Arrays::merge([self::$config, $config]);

        return new Client($new_config);
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
     * @param string              $method
     * @param string|UriInterface $uri
     * @param array               $options
     *
     * @return Response
     * @throws GuzzleException
     */
    public static function request($method, $uri, $options = [])
    {
        return self::client()->request($method, $uri, $options);
    }

    /**
     * @param string              $method
     * @param string|UriInterface $uri
     * @param array               $options
     *
     * @return PromiseInterface
     */
    public static function requestAsync($method, $uri, $options = [])
    {
        return self::client()->requestAsync($method, $uri, $options);
    }

    /**
     * @param string|UriInterface $uri
     * @param array               $options
     *
     * @return mixed|null
     * @throws GuzzleException
     */
    public static function getHeaders($uri, $options = [])
    {
        return self::request('HEAD', $uri, $options)->getHeaders();
    }

    /**
     * @param string|UriInterface $uri
     * @param string              $key
     * @param mixed|null          $default
     *
     * @return mixed|null
     * @throws GuzzleException
     */
    public static function getHeader($uri, $key, $default = null)
    {
        $headers = self::getHeaders($uri);

        return isset($headers[$key][0]) ? $headers[$key][0] : $default;
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
