<?php

namespace PaqtCom\ExternalApiCallClient\ErrorHandlers;

use Exception;
use PaqtCom\ExternalApiCallClient\Contracts\ErrorHandlerContract;
use PaqtCom\ExternalApiCallClient\Exceptions\ErrorWithClientCallException;
use PaqtCom\ExternalApiCallClient\Mediators\ExternalClientCall;

class ThrowExceptionOnError implements ErrorHandlerContract
{
    public function handleError(
        ExternalClientCall $call
    ) {
        throw new ErrorWithClientCallException(
            $call->getThrowable() ?? new Exception('Unknown error'),
            $call->getState(),
            $call->getRequest(),
            $call->getResponse()
        );
    }
}
