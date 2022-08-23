<?php

namespace PaqtCom\ExternalApiCallClient\Contracts;

use PaqtCom\ExternalApiCallClient\Mediators\ExternalClientCall;
use PaqtCom\ExternalApiCallClient\Services\ExternalClient;

/**
 * Interface used by ExternalClient to handle an error.
 *
 * @see ExternalClient
 */
interface ErrorHandlerContract
{
    /**
     * @phpstan-template V
     * @phpstan-param ExternalClientCall<mixed, mixed, V> $clientCall
     *
     * @phpstan-return V
     */
    public function handleError(ExternalClientCall $clientCall);
}
