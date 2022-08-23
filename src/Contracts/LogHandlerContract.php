<?php

namespace PaqtCom\ExternalApiCallClient\Contracts;

use PaqtCom\ExternalApiCallClient\Enums\ExternalClientStateEnum;
use PaqtCom\ExternalApiCallClient\Mediators\ExternalClientCall;

/**
 * Interface used to log an external client call.
 */
interface LogHandlerContract
{
    /**
     * Logs an external client call.
     * The second argument is only provided if the error handler threw an error. ExternalClientCall state would be
     * in state 'HANDLE_ERROR_RESPONSE' if this happens.
     *
     * @phpstan-param ExternalClientCall<mixed, mixed, mixed> $clientCall
     */
    public function logRequest(ExternalClientCall $clientCall, ?ExternalClientStateEnum $errorState): void;
}
