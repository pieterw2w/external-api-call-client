<?php

namespace PaqtCom\ExternalApiCallClient\Exceptions;

use PaqtCom\ExternalApiCallClient\Enums\ExternalClientStateEnum;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

class ErrorWithClientCallException extends RuntimeException
{
    public function __construct(
        Throwable $throwable,
        ExternalClientStateEnum $state,
        ?RequestInterface $request,
        ?ResponseInterface $response
    ) {
        $message = 'A problem occurred when calling an external API: "' . $throwable->getMessage();
        $message .= '" state: ' . $state->value;
        if ($request) {
            $message .= ', request: ' . $request->getUri();
        }
        if ($response) {
            $message .= ', response: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        }

        parent::__construct($message, 0, $throwable);
    }
}
