<?php

namespace PaqtCom\ExternalApiCallClient\Enums;

use PaqtCom\ExternalApiCallClient\Mediators\ExternalClientCall;

/**
 * Enum for all possible states in making a call to an external client.
 *
 * @see ExternalClientCall
 */
enum ExternalClientStateEnum: string
{
    /**
     * Creates Request object. This often means converting some DTO, entity or value object into a Request object.
     */
    case CREATE_REQUEST = 'CREATE_REQUEST';

    /**
     * Does the actual HTTP request. This is done with Guzzle.
     */
    case HTTP_REQUEST = 'HTTP_REQUEST';

    /**
     * Handles the response. This is often done to convert the raw response body into a DTO, entity or value object.
     */
    case HANDLE_RESPONSE = 'HANDLE_RESPONSE';

    /**
     * Returns the created response object.
     */
    case RETURN_RESPONSE = 'RETURN_RESPONSE';

    /**
     * Something went wrong and it handles the error.
     */
    case HANDLE_ERROR_RESPONSE = 'HANDLE_ERROR_RESPONSE';

    /**
     * Returns a response from an error.
     */
    case RETURN_ERROR_RESPONSE = 'RETURN_ERROR_RESPONSE';

    /**
     * Returns enum with next state or null if there is none.
     */
    public function nextState(): self
    {
        $nextStepMapping = [
            self::CREATE_REQUEST->value        => self::HTTP_REQUEST,
            self::HTTP_REQUEST->value          => self::HANDLE_RESPONSE,
            self::HANDLE_RESPONSE->value       => self::RETURN_RESPONSE,
            self::HANDLE_ERROR_RESPONSE->value => self::RETURN_ERROR_RESPONSE,
        ];
        $id = $this->value;
        if (isset($nextStepMapping[$id])) {
            return $nextStepMapping[$id];
        }

        return $this;
    }
}
