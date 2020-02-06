<?php

namespace HttpX\Tea\Exception;

use Throwable;
use RuntimeException;

/**
 * Class TeaError
 *
 * @package HttpX\Tea\Exception
 */
class TeaError extends RuntimeException
{
    private $errorInfo = [];

    /**
     * TeaError constructor.
     *
     * @param array          $errorInfo
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($errorInfo = [], $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorInfo = $errorInfo;
    }

    public function getErrorInfo()
    {
        return $this->errorInfo;
    }
}
