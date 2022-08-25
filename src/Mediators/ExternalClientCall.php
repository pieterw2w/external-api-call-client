<?php

namespace PaqtCom\ExternalApiCallClient\Mediators;

use GuzzleHttp\Exception\ClientException;
use PaqtCom\ExternalApiCallClient\Enums\ExternalClientStateEnum;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Used by ExternalClient to make a call to an external API and determines the state it was/is in. This class
 * is used by all classes as a mediator.
 * @phpstan-template T
 * @phpstan-template U
 * @phpstan-template V
 * @see ExternalClient
 */
final class ExternalClientCall
{
    /**
     * State of the call. Basically this class can be seen as a
     * Used because certain setters can not be called until this class is in a certain state.
     */
    private ExternalClientStateEnum $state;

    /**
     * The path request that was made at the start. Used mainly by RequestBuilderContract to create the request uri.
     */
    private string $path;

    /**
     * Raw input data. Used mainly by RequestBuilderContract to serialize raw input data to a request body.
     *
     * @phpstan-var T|null
     */
    private $input;

    /**
     * The request sent to Guzzle. Any middleware that in Guzzle that modified the request is not stored here,
     * so middleware can add Authorization headers without authorization headers appearing in logs.
     */
    private ?RequestInterface $request = null;

    /**
     * The response retrieved from Guzzle.
     *
     * @phpstan-var ResponseInterface|null
     */
    private ?ResponseInterface $response = null;

    /**
     * This identifier is used for identifying the type of call, probably used mainly for logging.
     */
    private string $identifier;

    /**
     * The wanted return type of the client call. Used mainly by ResponseHandlerContract to deserialize a response
     * body to a class instance.
     *
     * @phpstan-var class-string<object>|null
     */
    private ?string $outputClass = null;

    /**
     * The result of ResponseHandlerContract when deserializing the response body.
     *
     * @phpstan-var U
     */
    private $result;

    /**
     * The result that will be returned by the client call if an error occurred.
     *
     * @phpstan-var V
     */
    private $errorResult;

    /**
     * The exception thrown when an error occured. If the error handler threw an exception, that exception will not be
     * logged here.
     *
     * @var Throwable|null
     */
    private ?Throwable $throwable = null;

    /**
     * Whether the call was a mocked client call.
     *
     * @var bool
     */
    private $mocked = false;

    /**
     * @phpstan-param class-string<U>|null $outputClass
     */
    public function __construct(string $identifier, string $path, ?string $outputClass = null)
    {
        $this->identifier = $identifier;
        $this->path = $path;
        $this->outputClass = $outputClass;
        $this->state = ExternalClientStateEnum::CREATE_REQUEST;
    }

    /**
     * Returns an identifier to know the type of call. Used for logging purposes.
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Requested path/url to the external API.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Requests output class that is requested to be returned. This is not checked by ExternalClient but can be used
     * by the class implementing RequestBuilderContract or ResponseHandlerContract.
     *
     * @phpstan-return class-string<object>|null
     */
    public function getOutputClass(): ?string
    {
        return $this->outputClass;
    }

    /**
     * Current state the call is in. If you are not in the right state for example you can not set a request or
     * response.
     */
    public function getState(): ExternalClientStateEnum
    {
        return $this->state;
    }

    /**
     * Returns input data in raw format.
     *
     * @phpstan-return T|null
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Sets the request and moves the state to HTTP_REQUEST.
     *
     * @phpstan-param T $input
     * @phpstan-return ExternalClientCall<T, U, V>
     */
    public function setRequest(RequestInterface $request, bool $mocked, $input = null): self
    {
        if ($this->getState() !== ExternalClientStateEnum::CREATE_REQUEST) {
            throw new RuntimeException('I can only set a request when my state is to create request');
        }
        $this->request = $request;
        $this->mocked = $mocked;
        $this->input = $input;
        $this->state = $this->state->nextState();

        return $this;
    }

    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    public function isMocked(): bool
    {
        return $this->mocked;
    }

    /**
     * Sets the response and moves the state to HANDLE_RESPONSE.
     *
     * @phpstan-return ExternalClientCall<T, U, V>
     */
    public function setResponse(ResponseInterface $response): self
    {
        if ($this->getState() !== ExternalClientStateEnum::HTTP_REQUEST) {
            throw new RuntimeException('I can only set a response when my state is to do HTTP request');
        }
        $this->response = $response;
        $this->state = $this->state->nextState();

        return $this;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Sets the deserialized result of the call and moves the state to RETURN_RESPONSE.
     *
     * @phpstan-param U $result
     * @phpstan-return ExternalClientCall<T, U, V>
     */
    public function setResult(mixed $result): self
    {
        if ($this->getState() !== ExternalClientStateEnum::HANDLE_RESPONSE) {
            throw new RuntimeException('I can only set a result when I handle a response');
        }
        $this->result = $result;
        $this->state = $this->state->nextState();

        return $this;
    }

    /**
     * @phpstan-return U|null
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * Sets the state to error state. Returns the last known state (which is used in case the error handler threw
     * also an exception).
     *
     * @phpstan-param T|null $input
     */
    public function runErrorHandler(Throwable $throwable, mixed $input = null): ExternalClientStateEnum
    {
        // If an error occurs, we do not always already have the original input so in those cases we have the option of
        // passing it here.
        if ($input !== null) {
            $this->input = $input;
        }

        $this->throwable = $throwable;
        // if guzzle option http_errors is true, this exception is thrown, so we extract the request/response
        if ($throwable instanceof ClientException) {
            if (!$this->request) {
                $this->request = $throwable->getRequest();
            }
            if (!$this->response) {
                $this->response = $throwable->getResponse();
            }
        }
        $lastKnownStateBeforeError = $this->getState();
        $this->state = ExternalClientStateEnum::HANDLE_ERROR_RESPONSE;

        return $lastKnownStateBeforeError;
    }

    /**
     * The error handler returns a response and we store that.
     *
     * @phpstan-param V $errorResult
     * @phpstan-return ExternalClientCall<T, U, V>
     */
    public function setErrorResult(mixed $errorResult): self
    {
        if ($this->getState() !== ExternalClientStateEnum::HANDLE_ERROR_RESPONSE) {
            throw new RuntimeException('I can only set an error result when I handle a response');
        }
        $this->errorResult = $errorResult;
        $this->state = $this->state->nextState();

        return $this;
    }

    /**
     * @return V|null
     */
    public function getErrorResult()
    {
        return $this->errorResult;
    }

    /**
     * @return Throwable|null
     */
    public function getThrowable(): ?Throwable
    {
        return $this->throwable;
    }
}
