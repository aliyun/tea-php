<?php

namespace HttpX\Tea\Exception;

use Throwable;
use RuntimeException;

/**
 * Class TeaUnableRetryError
 *
 * @package HttpX\Tea\Exception
 */
class TeaUnableRetryError extends RuntimeException
{
    /**
     * TeaUnableRetryError constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
