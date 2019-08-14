<?php

namespace HttpX\Tea;

use InvalidArgumentException;
use GuzzleHttp\Psr7\Request as PsrRequest;

/**
 * Class Request
 *
 * @package Tea
 */
class Request extends PsrRequest
{
    /**
     * @var string
     */
    public $protocol = 'https';

    /**
     * @var string
     */
    public $pathname = '/';

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
     * @var int
     */
    public $port;

    /**
     * These fields are compatible if you define other fields.
     * Mainly for compatibility situations where the code generator cannot generate set properties.
     *
     * @return PsrRequest
     */
    public function getPsrRequest()
    {
        $this->assertQuery($this->query);

        $request = clone $this;

        $uri = $request->getUri();
        if ($this->query) {
            $uri = $uri->withQuery(http_build_query($this->query));
        }

        if ($this->port) {
            $uri = $uri->withPort($this->port);
        }

        if ($this->protocol) {
            $uri = $uri->withScheme($this->protocol);
        }

        if ($this->pathname) {
            $uri = $uri->withPath($this->pathname);
        }

        if (isset($this->headers['host'])) {
            $uri = $uri->withHost($this->headers['host']);
        }

        $request = $request->withUri($uri);

        if ($this->body !== '' && $this->body !== null) {
            $request = $request->withBody(\GuzzleHttp\Psr7\stream_for($this->body));
        }

        if ($this->headers) {
            foreach ($this->headers as $key => $value) {
                $request = $request->withHeader($key, $value);
            }
        }

        return $request;
    }

    /**
     * @param array $query
     */
    private function assertQuery($query)
    {
        if (!is_array($query)) {
            throw new InvalidArgumentException('Query must be array.');
        }
    }
}
