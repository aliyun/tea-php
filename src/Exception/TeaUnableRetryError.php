<?php

namespace AlibabaCloud\Tea\Exception;

use RuntimeException;

/**
 * Class TeaUnableRetryError
 *
 * @package AlibabaCloud\Tea\Exception
 */
class TeaUnableRetryError extends RuntimeException
{
    /**
     * TeaUnableRetryError constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
