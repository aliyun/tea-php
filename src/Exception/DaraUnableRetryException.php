<?php

namespace AlibabaCloud\Tea\Exception;

use AlibabaCloud\Tea\Request;

/**
 * Class DaraUnableRetryException.
 */
class DaraUnableRetryException extends DaraException
{
    private $lastRequest;
    private $lastException;

    /**
     * DaraUnableRetryException constructor.
     *
     * @param Request         $lastRequest
     * @param null|\Exception $lastException
     */
    public function __construct($lastRequest, $lastException = null)
    {
        $error_info = [];
        if (null !== $lastException && $lastException instanceof TeaError) {
            $error_info = $lastException->getErrorInfo();
        }
        parent::__construct($error_info, $lastException->getMessage(), $lastException->getCode(), $lastException);
        $this->lastRequest   = $lastRequest;
        $this->lastException = $lastException;
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    public function getLastException()
    {
        return $this->lastException;
    }
}
