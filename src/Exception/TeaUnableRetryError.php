<?php

namespace AlibabaCloud\Tea\Exception;

use AlibabaCloud\Tea\Request;
use RuntimeException;

/**
 * Class TeaUnableRetryError
 *
 * @package AlibabaCloud\Tea\Exception
 */
class TeaUnableRetryError extends RuntimeException
{
    private $lastRequest;

    /**
     * TeaUnableRetryError constructor.
     *
     * @param Request         $lastRequest
     */
    public function __construct($lastRequest)
    {
        parent::__construct('TeaUnableRetryError', 0, null);
        $this->lastRequest = $lastRequest;
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }
}
