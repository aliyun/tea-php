<?php

namespace AlibabaCloud\Tea;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\TransferStats;
use Songshenzong\Support\Arrays;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Tea
 *
 * @package AlibabaCloud\Tea
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
     * @param RequestInterface $request
     * @param array            $config
     *
     * @return Response
     */
    public static function send(RequestInterface $request, array $config = [])
    {
        if (method_exists($request, 'getPsrRequest')) {
            $request = $request->getPsrRequest();
        }

        $config['http_errors'] = false;

        $res = self::client()->send(
            $request,
            $config
        );
        return new Response($res);
    }

    /**
     * @param RequestInterface $request
     * @param array            $config
     *
     * @return PromiseInterface
     */
    public static function sendAsync(RequestInterface $request, array $config = [])
    {
        if (method_exists($request, 'getPsrRequest')) {
            $request = $request->getPsrRequest();
        }

        $config['http_errors'] = false;

        return self::client()->sendAsync(
            $request,
            $config
        );
    }

    /**
     * @param array $config
     *
     * @return Client
     */
    public static function client(array $config = [])
    {
        if (isset(self::$config['handler'])) {
            $stack = self::$config['handler'];
        } else {
            $stack = HandlerStack::create();
        }

        $stack->push(Middleware::mapResponse(static function (ResponseInterface $response) {
            return new Response($response);
        }));

        self::$config['handler'] = $stack;

        if (!isset(self::$config['on_stats'])) {
            self::$config['on_stats'] = function (TransferStats $stats) {
                Response::$info = $stats->getHandlerStats();
            };
        }

        $new_config = Arrays::merge([self::$config, $config]);

        return new Client($new_config);
    }

    /**
     * @param string              $method
     * @param string|UriInterface $uri
     * @param array               $options
     *
     * @return ResponseInterface
     */
    public static function request($method, $uri, $options = [])
    {
        return self::client()->request($method, $uri, $options);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return string
     */
    public static function string($method, $uri, $options = [])
    {
        return (string)self::client()->request($method, $uri, $options)
            ->getBody();
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
     * @param array $runtime
     * @param int   $retryTimes
     * @param float $now
     *
     * @return bool
     */
    public static function allowRetry(array $runtime, $retryTimes, $now)
    {
        unset($now);
        if (empty($runtime) || !isset($runtime['maxAttempts'])) {
            return false;
        }
        $maxAttempts = $runtime['maxAttempts'];
        $retry       = empty($maxAttempts) ? 0 : intval($maxAttempts);
        return $retry >= $retryTimes;
    }

    /**
     * @param array $runtime
     * @param int   $retryTimes
     *
     * @return int
     */
    public static function getBackoffTime(array $runtime, $retryTimes)
    {
        $backOffTime = 0;
        $policy      = isset($runtime["policy"]) ? $runtime["policy"] : "";

        if (empty($policy) || $policy == "no") {
            return $backOffTime;
        }

        $period = isset($runtime["period"]) ? $runtime["period"] : "";
        if (null !== $period && "" !== $period) {
            $backOffTime = intval($period);
            if ($backOffTime <= 0) {
                return $retryTimes;
            }
        }
        return $backOffTime;
    }

    public static function sleep($time)
    {
        sleep($time);
    }

    public static function isRetryable($retry, $retryTimes)
    {
        $max = isset($retry["maxAttempts"]) ? intval($retry["maxAttempts"]) : 3;
        return $retryTimes <= $max;
    }

    public static function merge(...$item)
    {
        $tmp = [];
        $n   = 0;
        foreach ($item as $i) {
            if (is_object($i)) {
                $i = json_decode(json_encode($i), true);
            }
            if (!is_array($i)) {
                throw new \InvalidArgumentException($i);
            }
            $tmp[$n++] = $i;
        }
        return call_user_func_array('array_merge', $tmp);
    }
}
