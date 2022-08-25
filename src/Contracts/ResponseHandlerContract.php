<?php

namespace PaqtCom\ExternalApiCallClient\Contracts;

use PaqtCom\ExternalApiCallClient\Mediators\ExternalClientCall;

/**
 * Interface for class that parses a guzzle response.
 */
interface ResponseHandlerContract
{
    /**
     * Parse a valid response and returns an object that comes from the response.
     *
     * @phpstan-template T
     * @phpstan-param ExternalClientCall<T, mixed, mixed> $externalClientCall
     *
     * @phpstan-return T
     */
    public function handleResponse(ExternalClientCall $externalClientCall);
}
