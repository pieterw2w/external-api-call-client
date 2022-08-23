<?php

namespace PaqtCom\ExternalApiCallClient\ErrorHandlers;

use PaqtCom\ExternalApiCallClient\Contracts\ErrorHandlerContract;
use PaqtCom\ExternalApiCallClient\Exceptions\ErrorWithClientCallException;
use PaqtCom\ExternalApiCallClient\Mediators\ExternalClientCall;

class ThrowExceptionOnError implements ErrorHandlerContract
{
    public function handleError(
        ExternalClientCall $call
    ) {
        throw new ErrorWithClientCallException(
            $call->getThrowable(),
            $call->getState(),
            $call->getRequest(),
            $call->getResponse()
        );
    }
}
