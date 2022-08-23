<?php

namespace PaqtCom\Tests\ExternalApiCallClient\Mediators;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PaqtCom\ExternalApiCallClient\Enums\ExternalClientStateEnum;
use PaqtCom\ExternalApiCallClient\Mediators\ExternalClientCall;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Mime\Exception\RuntimeException as MimeException;

class ExternalClientCallTest extends TestCase
{
    public function testInitialState()
    {
        $testItem = new ExternalClientCall(__CLASS__, 'https://example.com/', ExternalClientCall::class);
        $this->assertEquals(null, $testItem->getResponse());
        $this->assertEquals(null, $testItem->getRequest());
        $this->assertEquals(null, $testItem->getThrowable());
        $this->assertEquals(__CLASS__, $testItem->getIdentifier());
        $this->assertEquals(ExternalClientStateEnum::CREATE_REQUEST, $testItem->getState());
        $this->assertEquals('https://example.com/', $testItem->getPath());
        $this->assertEquals(null, $testItem->getErrorResult());
        $this->assertEquals(null, $testItem->getInput());
        $this->assertEquals(ExternalClientCall::class, $testItem->getOutputClass());
        $this->assertEquals(null, $testItem->getResult());

        return $testItem;
    }

    /**
     * @depends clone testInitialState
     */
    public function testWrongMethodCall(ExternalClientCall $testItem)
    {
        $this->expectException(RuntimeException::class);
        $testItem->setResult(1);
    }

    /**
     * @depends clone testInitialState
     */
    public function testErrorAfterInitialState(ExternalClientCall $testItem)
    {
        $exception = new MimeException('Mr. Mime', 1);
        $this->assertEquals(
            ExternalClientStateEnum::CREATE_REQUEST,
            $testItem->runErrorHandler($exception)
        );
        $this->assertEquals(null, $testItem->getResponse());
        $this->assertEquals(null, $testItem->getRequest());
        $this->assertEquals($exception, $testItem->getThrowable());
        $this->assertEquals(__CLASS__, $testItem->getIdentifier());
        $this->assertEquals(ExternalClientStateEnum::HANDLE_ERROR_RESPONSE, $testItem->getState());
        $this->assertEquals('https://example.com/', $testItem->getPath());
        $this->assertEquals(null, $testItem->getErrorResult());
        $this->assertEquals(null, $testItem->getInput());
        $this->assertEquals(ExternalClientCall::class, $testItem->getOutputClass());
        $this->assertEquals(null, $testItem->getResult());

        $this->assertEquals($testItem, $testItem->setErrorResult(666));
        $this->assertEquals(ExternalClientStateEnum::RETURN_ERROR_RESPONSE, $testItem->getState());
        $this->assertEquals(666, $testItem->getErrorResult());

        return $testItem;
    }

    /**
     * @depends clone testInitialState
     */
    public function testRequest(ExternalClientCall $testItem)
    {
        $request = new Request('POST', '/channel/random', [], '{"user":"Pieter"}');
        $this->assertFalse($testItem->isMocked());
        $this->assertEquals($testItem, $testItem->setRequest($request, true, []));
        $this->assertTrue($testItem->isMocked());
        $this->assertEquals(null, $testItem->getResponse());
        $this->assertEquals($request, $testItem->getRequest());
        $this->assertEquals(null, $testItem->getThrowable());
        $this->assertEquals(__CLASS__, $testItem->getIdentifier());
        $this->assertEquals(ExternalClientStateEnum::HTTP_REQUEST, $testItem->getState());
        $this->assertEquals('https://example.com/', $testItem->getPath());
        $this->assertEquals(null, $testItem->getErrorResult());
        $this->assertEquals([], $testItem->getInput());
        $this->assertEquals(ExternalClientCall::class, $testItem->getOutputClass());
        $this->assertEquals(null, $testItem->getResult());

        return $testItem;
    }

    /**
     * @depends clone testRequest
     */
    public function testResponse(ExternalClientCall $testItem)
    {
        $expectedRequest = $testItem->getRequest();
        $response = new Response(200, [], '{}');
        $this->assertEquals($testItem, $testItem->setResponse($response));
        $this->assertEquals($response, $testItem->getResponse());
        $this->assertEquals($expectedRequest, $testItem->getRequest());
        $this->assertEquals(null, $testItem->getThrowable());
        $this->assertEquals(__CLASS__, $testItem->getIdentifier());
        $this->assertEquals(ExternalClientStateEnum::HANDLE_RESPONSE, $testItem->getState());
        $this->assertEquals('https://example.com/', $testItem->getPath());
        $this->assertEquals(null, $testItem->getErrorResult());
        $this->assertEquals([], $testItem->getInput());
        $this->assertEquals(ExternalClientCall::class, $testItem->getOutputClass());
        $this->assertEquals(null, $testItem->getResult());

        return $testItem;
    }

    /**
     * @depends clone testResponse
     */
    public function testResult(ExternalClientCall $testItem)
    {
        $expectedRequest = $testItem->getRequest();
        $expectedResponse = $testItem->getResponse();
        $this->assertEquals($testItem, $testItem->setResult(42));
        $this->assertEquals($expectedResponse, $testItem->getResponse());
        $this->assertEquals($expectedRequest, $testItem->getRequest());
        $this->assertEquals(null, $testItem->getThrowable());
        $this->assertEquals(__CLASS__, $testItem->getIdentifier());
        $this->assertEquals(ExternalClientStateEnum::RETURN_RESPONSE, $testItem->getState());
        $this->assertEquals('https://example.com/', $testItem->getPath());
        $this->assertEquals(null, $testItem->getErrorResult());
        $this->assertEquals([], $testItem->getInput());
        $this->assertEquals(ExternalClientCall::class, $testItem->getOutputClass());
        $this->assertEquals(42, $testItem->getResult());

        return $testItem;
    }
}
