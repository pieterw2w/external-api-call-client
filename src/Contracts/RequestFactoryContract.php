<?php

namespace PaqtCom\ExternalApiCallClient\Contracts;

use PaqtCom\ExternalApiCallClient\Mediators\ExternalClientCall;
use Psr\Http\Message\RequestInterface;

/**
 * Interface to create a Request object that will be used for Guzzle.
 */
interface RequestFactoryContract
{
    /**
     * Creates a POST request.
     * It's important that this method returns the request and does not call setRequest directly.
     *
     * @phpstan-template T
     * @phpstan-param ExternalClientCall<T, mixed, mixed> $externalClientCall
     * @phpstan-param T $input
     */
    public function createPostRequest(ExternalClientCall $externalClientCall, $input): RequestInterface;

    /**
     * Creates a GET request.
     * It's important that this method returns the request and does not call setRequest directly.
     *
     * @phpstan-param ExternalClientCall<null, mixed, mixed> $externalClientCall
     */
    public function createGetRequest(ExternalClientCall $externalClientCall): RequestInterface;

    /**
     * Creates a PUT request.
     * It's important that this method returns the request and does not call setRequest directly.
     *
     * @phpstan-template T
     * @phpstan-param ExternalClientCall<T, mixed, mixed> $externalClientCall
     * @phpstan-param T $input
     */
    public function createPutRequest(ExternalClientCall $externalClientCall, $input): RequestInterface;
}
