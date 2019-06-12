<?php

namespace HttpX\Tea;

use InvalidArgumentException;
use GuzzleHttp\Psr7\Request as PsrRequest;

/**
 * Class Request
 *
 * @package Tea
 */
class Request
{
    /**
     * @var string
     */
    public $protocol;

    /**
     * @var string
     */
    public $method;

    /**
     * @var string
     */
    public $pathname;

    /**
     * @var array
     */
    public $headers = [];

    /**
     * @var array
     */
    public $query = [];

    /**
     * @var string
     */
    public $body;

    /**
     * @return PsrRequest
     */
    public function getPsrRequest()
    {
        if (!isset($this->headers['host'])) {
            throw new InvalidArgumentException('Host can not be empty.');
        }

        $uri     = $this->protocol . '://' . $this->headers['host'] . $this->pathname;
        $request = new PsrRequest(
            $this->method,
            $uri,
            $this->headers,
            $this->body
        );

        if ($this->query) {
            $request = $request->withUri(
                $request->getUri()->withQuery(http_build_query($this->query))
            );
        }

        return $request;
    }
}
