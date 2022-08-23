<?php

namespace PaqtCom\ExternalApiCallClient\Services;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Str;
use PaqtCom\ExternalApiCallClient\Contracts\ErrorHandlerContract;
use PaqtCom\ExternalApiCallClient\Contracts\LogHandlerContract;
use PaqtCom\ExternalApiCallClient\Contracts\RequestFactoryContract;
use PaqtCom\ExternalApiCallClient\Contracts\ResponseHandlerContract;
use PaqtCom\ExternalApiCallClient\Mediators\ExternalClientCall;
use ReflectionFunction;
use ReflectionProperty;
use Throwable;
use ValueError;

/**
 * Class that can be used directly or extended to make an API call in a logical, structured order.
 * It does this in a few predefined steps:
 * - create a guzzle request from an input object.
 * - do the actual client call and retrieve the guzzle response.
 * - parse the guzzle response back into a response object and return this.
 *
 * - if anything goes wrong handle it with an error handler.
 * - always log the client call action with a log handler.
 */
class ExternalClient
{
    private RequestFactoryContract $requestBuilder;

    private Client $client;

    private ResponseHandlerContract $responseHandler;

    private ErrorHandlerContract $errorHandler;

    private LogHandlerContract $logHandler;

    public function __construct(
        RequestFactoryContract $requestBuilder,
        Client $client,
        ResponseHandlerContract $responseHandler,
        ErrorHandlerContract $errorHandler,
        LogHandlerContract $logHandler
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->client = $client;
        $this->responseHandler = $responseHandler;
        $this->errorHandler = $errorHandler;
        $this->logHandler = $logHandler;
    }

    /**
     * Does a POST call with $input as intended request body.
     *
     * @phpstan-template T
     * @phpstan-template U
     * @phpstan-template V
     * @phpstan-param ExternalClientCall<T, U, V>
     * @phpstan-param T
     * @phpstan-return U|V
     */
    final public function post(ExternalClientCall $clientCall, mixed $input): mixed
    {
        return $this->runCall(function (ExternalClientCall $clientCall, $input) {
            return $this->requestBuilder->createPostRequest($clientCall, $input);
        }, $clientCall, $input);
    }

    /**
     * Does a PATCH call with $input as intended request body.
     *
     * @phpstan-template T
     * @phpstan-template U
     * @phpstan-template V
     * @phpstan-param ExternalClientCall<T, U, V>
     * @phpstan-param T
     * @phpstan-return U|V
     */
    final public function patch(ExternalClientCall $clientCall, mixed $input): mixed
    {
        return $this->runCall(function (ExternalClientCall $clientCall, $input) {
            return $this->requestBuilder->createPutRequest($clientCall, $input)->withMethod('PATCH');
        }, $clientCall, $input);
    }

    /**
     * Does a GET call and returns the converted response.
     * @phpstan-template U
     * @phpstan-template V
     * @phpstan-param ExternalClientCall<null, U, V>
     * @phpstan-return U|V
     */
    final public function get(ExternalClientCall $clientCall): mixed
    {
        return $this->runCall(function (ExternalClientCall $clientCall) {
            return $this->requestBuilder->createGetRequest($clientCall);
        }, $clientCall, null);
    }

    /**
     * Does the actual call. The createCallback should return a guzzle request. The rest of the steps is always
     * the same.
     * @phpstan-template T
     * @phpstan-template U
     * @phpstan-param callable(ExternalClientCall<T,U,V>): RequestInterface $createCallback
     * @phpstan-param ExternalClientCall<T,U,V> $clientCall
     * @phpstan-param T $input
     *
     * @phpstan-return U|V
     */
    private function runCall(callable $createCallback, ExternalClientCall $clientCall, $input)
    {
        $state = null;
        $mocked = $this->isGuzzleMocked();
        try {
            $request = $createCallback($clientCall, $input);
            $clientCall->setRequest($request, $mocked, $input);
            $response = $this->client->send($request);
            $clientCall->setResponse($response);
            $result = $this->responseHandler->handleResponse($clientCall);
            $clientCall->setResult($result);

            return $result;
        } catch (Throwable $throwable) {
            // this state assignment is used when the error handler threw an exception, pls do not remove
            $state = $clientCall->runErrorHandler($throwable, $input);

            $result = $this->errorHandler->handleError($clientCall);
            $clientCall->setErrorResult($result);
            $state = null;

            return $result;
        } finally {
            $this->logHandler->logRequest($clientCall, $state);
        }
    }

    /**
     * Returns true if guzzle is using a mock handler. This code is hacky.
     *
     * @return bool
     */
    private function isGuzzleMocked(): bool
    {
        $handler = $this->client->getConfig('handler');
        if (!($handler instanceof HandlerStack)) {
            return true;
        }
        if (!$handler->hasHandler()) {
            return true;
        }
        $reflProp = new ReflectionProperty($handler, 'handler');
        $reflProp->setAccessible(true);
        $value = $reflProp->getValue($handler);
        if (!($value instanceof Closure)) {
            return true;
        }
        $reflFunction = new ReflectionFunction($value);

        return !str_ends_with($reflFunction->getFileName(), 'Handler/Proxy.php');
    }
}
